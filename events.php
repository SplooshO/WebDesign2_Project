<?php
// events.php - everyone logged in can view events; admins also get Add / Edit / Delete.
require 'auth.php';
require 'db.php';
require_login();

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

// LEFT JOIN so events with zero registrations still appear (count = 0)
$stmt = $pdo->prepare(
    'SELECT e.event_id, e.event_name, e.game_title, e.event_date, e.location, e.max_players,
            COUNT(r.registration_id) AS registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.event_id
     WHERE e.event_name LIKE ? OR e.game_title LIKE ? OR e.location LIKE ?
     GROUP BY e.event_id
     ORDER BY e.event_date ASC'
);
$stmt->execute([$like, $like, $like]);
$events = $stmt->fetchAll();

$flash = get_flash();
$pageTitle = 'Events';
require 'header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Events</h1>
    <?php if ($isAdmin): ?>
        <a href="event_form.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Event</a>
    <?php endif; ?>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Search by event name, game title or location -->
<form class="row g-2 mb-3" method="get" action="events.php">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control"
                   placeholder="Search events or games..." value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </div>
    <?php if ($q !== ''): ?>
        <div class="col-auto"><a href="events.php" class="btn btn-link">Clear</a></div>
    <?php endif; ?>
</form>

<?php if (!$events): ?>
    <div class="alert alert-info">No events found.</div>
<?php endif; ?>

<!-- 1 card per row on phones, 2 on tablets, 3 on desktops -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    <?php foreach ($events as $e):
        $registered = (int)$e['registered'];
        $max        = max(1, (int)$e['max_players']);
        $isFull     = $registered >= $max;
        $percent    = min(100, (int)round($registered / $max * 100));
        $ts         = strtotime($e['event_date']);
        $isPast     = $ts < time();
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h2 class="h5 card-title"><?= htmlspecialchars($e['event_name']) ?></h2>
                    <?php if ($isPast): ?><span class="badge text-bg-secondary">Past</span><?php endif; ?>
                </div>
                <h3 class="h6 card-subtitle mb-3 text-muted"><?= htmlspecialchars($e['game_title']) ?></h3>
                <p class="mb-1"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars(date('D, M j, Y \a\t g:i A', $ts)) ?></p>
                <p class="mb-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['location'] ?? '') ?></p>

                <div class="small mb-1"><?= $registered ?> / <?= (int)$e['max_players'] ?> players</div>
                <div class="progress" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $isFull ? 'bg-danger' : '' ?>" style="width: <?= $percent ?>%"></div>
                </div>
            </div>

            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <?php if ($isPast): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Closed</button>
                <?php elseif ($isFull): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Full</button>
                <?php else: ?>
                    <!-- The registration feature will hook into this button next -->
                    <button class="btn btn-primary btn-sm btn-register" data-event-id="<?= (int)$e['event_id'] ?>">Register</button>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <a href="event_form.php?id=<?= (int)$e['event_id'] ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                    <button type="button" class="btn btn-outline-danger btn-sm"
                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                            data-id="<?= (int)$e['event_id'] ?>"
                            data-name="<?= htmlspecialchars($e['event_name']) ?>">Delete</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Delete confirmation modal (admins only) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="event_delete.php">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="deleteModalLabel">Delete event?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteName"></strong>?
                    All registrations for it will be removed too. This cannot be undone.
                    <input type="hidden" name="event_id" id="deleteId">
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
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('deleteId').value = btn.getAttribute('data-id');
    document.getElementById('deleteName').textContent = btn.getAttribute('data-name');
});
</script>
<?php endif; ?>

<?php require 'footer.php'; ?>
