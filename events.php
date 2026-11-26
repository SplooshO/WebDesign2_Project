<?php
// events.php - everyone logged in can view events and register; admins also get Add / Edit / Delete.
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

// Which events has the current member already joined?
$stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$memberId = $stmt->fetchColumn() ?: null;

$mine = [];
if ($memberId) {
    $stmt = $pdo->prepare('SELECT event_id FROM registrations WHERE member_id = ?');
    $stmt->execute([$memberId]);
    $mine = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));   // event_id => position, for quick isset()
}

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

<!-- The registration script drops its success / error messages here -->
<div id="alertArea"></div>

<?php if (!$memberId): ?>
    <div class="alert alert-warning">
        Your account has no member profile, so you can browse events but not register for them.
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

<!-- 1 card per row on phones, 2 on tablets, 3 on desktops. data-csrf is read by the script below. -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" id="eventGrid"
     data-csrf="<?= htmlspecialchars(csrf_token()) ?>">
    <?php foreach ($events as $e):
        $registered = (int)$e['registered'];
        $max        = max(1, (int)$e['max_players']);
        $isFull     = $registered >= $max;
        $percent    = min(100, (int)round($registered / $max * 100));
        $ts         = strtotime($e['event_date']);
        $isPast     = $ts < time();
        $joined     = isset($mine[$e['event_id']]);
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

                <div class="small mb-1 reg-count"><?= $registered ?> / <?= (int)$e['max_players'] ?> players</div>
                <div class="progress" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $isFull ? 'bg-danger' : '' ?>" style="width: <?= $percent ?>%"></div>
                </div>
            </div>

            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <?php if ($joined): ?>
                    <button class="btn btn-success btn-sm" disabled><i class="bi bi-check-lg"></i> Registered</button>
                <?php elseif ($isPast): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Closed</button>
                <?php elseif ($isFull): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Full</button>
                <?php elseif (!$memberId): ?>
                    <button class="btn btn-secondary btn-sm" disabled>No member profile</button>
                <?php else: ?>
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

<script>
// ---- AJAX registration using fetch(): no page reload ----
const grid = document.getElementById('eventGrid');
const alertArea = document.getElementById('alertArea');

grid.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-register');
    if (btn) { registerFor(btn); }
});

function showAlert(message, type) {
    alertArea.innerHTML = '';
    const div = document.createElement('div');
    div.className = 'alert alert-' + type + ' alert-dismissible fade show';
    div.setAttribute('role', 'alert');
    div.textContent = message;                       // textContent keeps this XSS-safe
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'alert');
    close.setAttribute('aria-label', 'Close');
    div.appendChild(close);
    alertArea.appendChild(div);
}

function registerFor(btn) {
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Registering...';

    const body = new FormData();
    body.append('csrf_token', grid.dataset.csrf);
    body.append('event_id', btn.dataset.eventId);

    fetch('register_event.php', { method: 'POST', body: body })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (result) {
            const data = result.data;
            if (result.ok && data.ok) {
                // Turn the button into "Registered" and refresh this card's numbers
                btn.classList.remove('btn-primary', 'btn-register');
                btn.classList.add('btn-success');
                btn.textContent = 'Registered';

                const card = btn.closest('.card');
                card.querySelector('.reg-count').textContent = data.registered + ' / ' + data.max + ' players';
                const bar = card.querySelector('.progress-bar');
                const pct = Math.min(100, Math.round(data.registered / data.max * 100));
                bar.style.width = pct + '%';
                bar.parentElement.setAttribute('aria-valuenow', pct);
                if (data.full) { bar.classList.add('bg-danger'); }

                showAlert(data.message, 'success');
            } else {
                btn.disabled = false;
                btn.textContent = original;
                showAlert(data.message || 'Something went wrong.', 'danger');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.textContent = original;
            showAlert('Could not reach the server. Please try again.', 'danger');
        });
}
</script>

<?php require 'footer.php'; ?>
