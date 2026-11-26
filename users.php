<?php
// users.php - Manage Users (admin only): change roles, reset passwords, delete accounts.
require 'auth.php';
require 'db.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $pdo->prepare(
    'SELECT u.user_id, u.username, u.role, u.created_at, m.real_name, m.gamertag
     FROM users u
     LEFT JOIN members m ON m.user_id = u.user_id
     WHERE u.username LIKE ? OR m.real_name LIKE ? OR m.gamertag LIKE ?
     ORDER BY u.username'
);
$stmt->execute([$like, $like, $like]);
$users = $stmt->fetchAll();

$flash = get_flash();
$pageTitle = 'Manage Users';
require 'header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Manage Users</h1>
    <a href="member_form.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add User (via Add Member)</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form class="row g-2 mb-3" method="get" action="users.php">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control"
                   placeholder="Search username or name..." value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </div>
    <?php if ($q !== ''): ?>
        <div class="col-auto"><a href="users.php" class="btn btn-link">Clear</a></div>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Username</th>
                    <th>Member</th>
                    <th>Created</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No users found.</td></tr>
                <?php endif; ?>

                <?php foreach ($users as $u):
                    $isMe = (int)$u['user_id'] === (int)$_SESSION['user_id'];
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($u['username']) ?>
                        <?php if ($isMe): ?><span class="badge text-bg-light border">you</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['real_name']): ?>
                            <?= htmlspecialchars($u['real_name']) ?>
                            <span class="text-muted small">(<?= htmlspecialchars($u['gamertag']) ?>)</span>
                        <?php else: ?>
                            <span class="text-muted">No member profile</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['created_at'] ? htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) : '-' ?></td>
                    <td>
                        <?php if ($isMe): ?>
                            <!-- You can't change your own role: that could lock every admin out -->
                            <span class="badge text-bg-danger"><?= htmlspecialchars($u['role']) ?></span>
                        <?php else: ?>
                            <form method="post" action="user_save.php" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="role">
                                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                <select name="role" class="form-select form-select-sm" style="width: auto;"
                                        aria-label="Role for <?= htmlspecialchars($u['username']) ?>">
                                    <option value="member" <?= $u['role'] === 'member' ? 'selected' : '' ?>>member</option>
                                    <option value="admin"  <?= $u['role'] === 'admin'  ? 'selected' : '' ?>>admin</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="text-end table-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#passwordModal"
                                data-id="<?= (int)$u['user_id'] ?>"
                                data-name="<?= htmlspecialchars($u['username']) ?>">Reset Password</button>
                        <?php if (!$isMe): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-id="<?= (int)$u['user_id'] ?>"
                                    data-name="<?= htmlspecialchars($u['username']) ?>">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset password modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="user_save.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <input type="hidden" name="user_id" id="passwordUserId">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="passwordModalLabel">Reset password</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Set a new password for <strong id="passwordName"></strong>.</p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                    </div>
                    <div class="mb-0">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                    </div>
                    <div class="form-text">At least 8 characters.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete user modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="user_delete.php">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="deleteModalLabel">Delete user?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteName"></strong>?
                    Their member profile and event registrations will be deleted too. This cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fill each modal with the user whose button was clicked
document.getElementById('passwordModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('passwordUserId').value = btn.getAttribute('data-id');
    document.getElementById('passwordName').textContent = btn.getAttribute('data-name');
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
});

document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('deleteUserId').value = btn.getAttribute('data-id');
    document.getElementById('deleteName').textContent = btn.getAttribute('data-name');
});
</script>

<?php require 'footer.php'; ?>
