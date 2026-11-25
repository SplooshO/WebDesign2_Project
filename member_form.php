<?php
// member_form.php - one form for both Add (no ?id) and Edit (?id=5). Admin only.
require 'auth.php';
require 'db.php';
require_admin();

$platforms = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Mobile'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$member = [
    'real_name' => '', 'gamertag' => '', 'email' => '', 'program' => '',
    'favorite_game' => '', 'gaming_platform' => '',
    'username' => '', 'role' => 'member',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE member_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('danger', 'Member not found.');
        header('Location: members.php');
        exit;
    }
    $member = array_merge($member, $row);
}

// If member_save.php sent us back with errors, show them and keep what was typed
$errors = $_SESSION['form_errors'] ?? [];
if (isset($_SESSION['form_old'])) {
    $member = array_merge($member, $_SESSION['form_old']);
}
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$pageTitle = $isEdit ? 'Edit Member' : 'Add Member';
require 'header.php';

function val(array $a, string $k): string
{
    return htmlspecialchars((string)($a[$k] ?? ''));
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h1 class="h5 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="card-body">

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="member_save.php" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="member_id" value="<?= $id ?>">
                    <?php endif; ?>

                    <?php if (!$isEdit): ?>
                    <!-- Login account details: only needed when creating a new member -->
                    <h2 class="h6 text-muted mb-3">Login Account</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= val($member, 'username') ?>" pattern="[A-Za-z0-9_]{3,50}" required>
                            <div class="invalid-feedback">3-50 characters: letters, numbers, underscores.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Temporary Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required>
                            <div class="invalid-feedback">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="member" <?= $member['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                                <option value="admin"  <?= $member['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <h2 class="h6 text-muted mb-3">Member Profile</h2>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="real_name" class="form-label">Real Name</label>
                            <input type="text" class="form-control" id="real_name" name="real_name"
                                   value="<?= val($member, 'real_name') ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter a name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="gamertag" class="form-label">Gamertag</label>
                            <input type="text" class="form-control" id="gamertag" name="gamertag"
                                   value="<?= val($member, 'gamertag') ?>" maxlength="50" required>
                            <div class="invalid-feedback">Please enter a gamertag.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= val($member, 'email') ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="program" class="form-label">Program</label>
                            <input type="text" class="form-control" id="program" name="program"
                                   value="<?= val($member, 'program') ?>" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="favorite_game" class="form-label">Favourite Game</label>
                            <input type="text" class="form-control" id="favorite_game" name="favorite_game"
                                   value="<?= val($member, 'favorite_game') ?>" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="gaming_platform" class="form-label">Gaming Platform</label>
                            <select class="form-select" id="gaming_platform" name="gaming_platform">
                                <option value="">Choose...</option>
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>"
                                        <?= ($member['gaming_platform'] ?? '') === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="members.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap's built-in form validation (browser-side; the server validates again)
(function () {
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
})();
</script>

<?php require 'footer.php'; ?>
