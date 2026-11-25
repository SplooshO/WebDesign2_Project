<?php
// member_form.php - one form used for both Add and Edit. LAYOUT ONLY for now.
// Later: if ?id= is present, load that member with PDO and fill $member.
$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? 'Edit Member' : 'Add Member';

$member = [
    'real_name' => '', 'gamertag' => '', 'email' => '', 'program' => '',
    'favorite_game' => '', 'gaming_platform' => '',
];
$platforms = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Mobile'];

require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h1 class="h5 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="card-body">
                <form method="post" action="member_save.php" class="needs-validation" novalidate>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="member_id" value="<?= (int)$_GET['id'] ?>">
                    <?php endif; ?>

                    <!-- row + col-md-6 = two fields side by side on tablets and up, stacked on phones -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="real_name" class="form-label">Real Name</label>
                            <input type="text" class="form-control" id="real_name" name="real_name"
                                   value="<?= htmlspecialchars($member['real_name']) ?>" required>
                            <div class="invalid-feedback">Please enter a name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="gamertag" class="form-label">Gamertag</label>
                            <input type="text" class="form-control" id="gamertag" name="gamertag"
                                   value="<?= htmlspecialchars($member['gamertag']) ?>" required>
                            <div class="invalid-feedback">Please enter a gamertag.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($member['email']) ?>" required>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="program" class="form-label">Program</label>
                            <input type="text" class="form-control" id="program" name="program"
                                   value="<?= htmlspecialchars($member['program']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="favorite_game" class="form-label">Favourite Game</label>
                            <input type="text" class="form-control" id="favorite_game" name="favorite_game"
                                   value="<?= htmlspecialchars($member['favorite_game']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="gaming_platform" class="form-label">Gaming Platform</label>
                            <select class="form-select" id="gaming_platform" name="gaming_platform" required>
                                <option value="">Choose...</option>
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>"
                                        <?= $member['gaming_platform'] === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please choose a platform.</div>
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
// Bootstrap's built-in form validation: shows red/green feedback before submitting
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
