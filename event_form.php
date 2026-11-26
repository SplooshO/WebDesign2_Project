<?php
// event_form.php - one form for Add (no ?id) and Edit (?id=3). Admin only.
require 'auth.php';
require 'db.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$event = [
    'event_name' => '', 'game_title' => '', 'event_date' => '',
    'location' => '', 'max_players' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('danger', 'Event not found.');
        header('Location: events.php');
        exit;
    }
    $event = array_merge($event, $row);
    // <input type="datetime-local"> needs the format 2026-12-05T18:00
    $event['event_date'] = date('Y-m-d\TH:i', strtotime($row['event_date']));
}

// If event_save.php sent us back with errors, show them and keep what was typed
$errors = $_SESSION['event_errors'] ?? [];
if (isset($_SESSION['event_old'])) {
    $event = array_merge($event, $_SESSION['event_old']);
}
unset($_SESSION['event_errors'], $_SESSION['event_old']);

function val(array $a, string $k): string
{
    return htmlspecialchars((string)($a[$k] ?? ''));
}

$pageTitle = $isEdit ? 'Edit Event' : 'Add Event';
require 'header.php';
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

                <form method="post" action="event_save.php" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="event_id" value="<?= $id ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="event_name" class="form-label">Event Name</label>
                            <input type="text" class="form-control" id="event_name" name="event_name"
                                   value="<?= val($event, 'event_name') ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter an event name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="game_title" class="form-label">Game Title</label>
                            <input type="text" class="form-control" id="game_title" name="game_title"
                                   value="<?= val($event, 'game_title') ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter the game title.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="event_date" class="form-label">Date and Time</label>
                            <input type="datetime-local" class="form-control" id="event_date" name="event_date"
                                   value="<?= val($event, 'event_date') ?>" required>
                            <div class="invalid-feedback">Please choose a date and time.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location"
                                   value="<?= val($event, 'location') ?>" maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="max_players" class="form-label">Max Players</label>
                            <input type="number" class="form-control" id="max_players" name="max_players"
                                   value="<?= val($event, 'max_players') ?>" min="1" max="1000" required>
                            <div class="invalid-feedback">Enter a number from 1 to 1000.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="events.php" class="btn btn-outline-secondary">Cancel</a>
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
