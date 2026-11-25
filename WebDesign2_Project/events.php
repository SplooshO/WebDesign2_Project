<?php
// events.php - LAYOUT ONLY. Events shown as a responsive grid of cards.
// Admins see Edit/Delete buttons; everyone sees a Register button.
$pageTitle = 'Events';
require 'header.php';

// Sample data for layout. Later: fetch with PDO.
$events = [
    ['event_id' => 1, 'event_name' => 'Valorant Showdown', 'game_title' => 'Valorant', 'event_date' => '2026-12-05 18:00', 'location' => 'HU 110', 'max_players' => 16, 'registered' => 9],
    ['event_id' => 2, 'event_name' => 'Smash Bros Night', 'game_title' => 'Super Smash Bros', 'event_date' => '2026-12-12 19:00', 'location' => 'Student Union', 'max_players' => 8, 'registered' => 8],
];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Events</h1>
    <?php if ($isAdmin): ?>
        <a href="event_form.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Event</a>
    <?php endif; ?>
</div>

<!-- 1 card per row on phones, 2 on tablets, 3 on desktops -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
    <?php foreach ($events as $e):
        $isFull = $e['registered'] >= $e['max_players'];
        $percent = (int) round($e['registered'] / $e['max_players'] * 100);
    ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title"><?= htmlspecialchars($e['event_name']) ?></h2>
                <h3 class="h6 card-subtitle mb-3 text-muted"><?= htmlspecialchars($e['game_title']) ?></h3>
                <p class="mb-1"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($e['event_date']) ?></p>
                <p class="mb-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['location']) ?></p>

                <!-- Progress bar showing how full the event is -->
                <div class="small mb-1"><?= (int)$e['registered'] ?> / <?= (int)$e['max_players'] ?> players</div>
                <div class="progress" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $isFull ? 'bg-danger' : '' ?>" style="width: <?= $percent ?>%"></div>
                </div>
            </div>

            <div class="card-footer bg-white d-flex gap-2">
                <?php if ($isFull): ?>
                    <button class="btn btn-secondary btn-sm" disabled>Full</button>
                <?php else: ?>
                    <!-- data-event-id lets our fetch() code know which event to register for -->
                    <button class="btn btn-primary btn-sm btn-register" data-event-id="<?= (int)$e['event_id'] ?>">Register</button>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <a href="event_form.php?id=<?= (int)$e['event_id'] ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require 'footer.php'; ?>
