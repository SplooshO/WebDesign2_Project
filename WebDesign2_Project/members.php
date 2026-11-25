<?php
// members.php - LAYOUT ONLY (admin page). Shows the list, search bar and action buttons.
$pageTitle = 'Members';
require 'header.php';

// Later: admin check, then fetch with PDO. Example sample rows for the layout:
$members = [
    ['member_id' => 1, 'real_name' => 'Alex Kim',   'gamertag' => 'AlexK',   'email' => 'alex@mytru.ca', 'program' => 'Computer Science', 'favorite_game' => 'Valorant', 'gaming_platform' => 'PC'],
    ['member_id' => 2, 'real_name' => 'Sam Lee',    'gamertag' => 'SamLee99','email' => 'sam@mytru.ca',  'program' => 'Business',         'favorite_game' => 'FIFA 25', 'gaming_platform' => 'PlayStation'],
];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Members</h1>
    <a href="member_form.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Add Member
    </a>
</div>

<!-- Flash message area (success / error after add, edit, delete) -->
<div class="alert alert-success alert-dismissible fade show d-none" role="alert" id="flashMsg">
    Member saved.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Search bar (we'll wire this to fetch() for the AJAX requirement) -->
<form class="row g-2 mb-3" onsubmit="return false;">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" id="searchBox" class="form-control"
                   placeholder="Search by name, gamertag, game...">
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <!-- table-responsive makes the table scroll sideways on small screens -->
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Gamertag</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Favourite Game</th>
                    <th>Platform</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="memberTableBody">
                <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['real_name']) ?></td>
                    <td><?= htmlspecialchars($m['gamertag']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['program']) ?></td>
                    <td><?= htmlspecialchars($m['favorite_game']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($m['gaming_platform']) ?></span></td>
                    <td class="text-end table-actions">
                        <a href="member_form.php?id=<?= (int)$m['member_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= (int)$m['member_id'] ?>"
                                data-name="<?= htmlspecialchars($m['real_name']) ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete confirmation modal (Bootstrap pop-up) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="member_delete.php">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="deleteModalLabel">Delete member?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteName"></strong>? This cannot be undone.
                    <input type="hidden" name="member_id" id="deleteId">
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
// Fill the modal with the clicked member's id and name
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('deleteId').value = btn.getAttribute('data-id');
    document.getElementById('deleteName').textContent = btn.getAttribute('data-name');
});
</script>

<?php require 'footer.php'; ?>
