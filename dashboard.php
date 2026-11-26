<?php
// dashboard.php - live stats for everyone; admins get extra cards.
require 'auth.php';
require 'db.php';
require_login();

// Current member (admins without a members row just see 0 registrations)
$stmt = $pdo->prepare('SELECT member_id, real_name FROM members WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();
$memberId = $me ? (int)$me['member_id'] : 0;

// ---- Stat numbers ----
$totalMembers   = (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$upcomingEvents = (int)$pdo->query('SELECT COUNT(*) FROM events WHERE event_date > NOW()')->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE member_id = ?');
$stmt->execute([$memberId]);
$myRegs = (int)$stmt->fetchColumn();

$totalRegs = 0;
$newMembers = [];
if ($isAdmin ?? (($_SESSION['role'] ?? '') === 'admin')) {
    $totalRegs = (int)$pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
    $newMembers = $pdo->query(
        'SELECT real_name, gamertag, join_date FROM members ORDER BY join_date DESC, member_id DESC LIMIT 5'
    )->fetchAll();
}

// ---- Next 5 upcoming events, with player counts and whether I'm in ----
$stmt = $pdo->prepare(
    'SELECT e.event_id, e.event_name, e.game_title, e.event_date, e.location, e.max_players,
            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) AS registered,
            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.member_id = ?) AS joined
     FROM events e
     WHERE e.event_date > NOW()
     ORDER BY e.event_date ASC
     LIMIT 5'
);
$stmt->execute([$memberId]);
$upcoming = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require 'header.php';   // defines $isAdmin for the rest of the page
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <h1 class="h3 mb-0">
        Welcome back, <?= htmlspecialchars($me['real_name'] ?? $_SESSION['username']) ?>
    </h1>
    <a href="events.php" class="btn btn-primary"><i class="bi bi-calendar-event"></i> Browse Events</a>
</div>

<!-- Stat cards: full width on phones, 2 across on tablets, 4 across on desktops -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Members</div>
                <div class="display-6"><?= $totalMembers ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Upcoming Events</div>
                <div class="display-6"><?= $upcomingEvents ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">My Registrations</div>
                <div class="display-6"><?= $myRegs ?></div>
            </div>
        </div>
    </div>
    <?php if ($isAdmin): ?>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">All Registrations</div>
                <div class="display-6"><?= $totalRegs ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Upcoming events table -->
    <div class="col-12 <?= $isAdmin ? 'col-xl-8' : '' ?>">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Upcoming Events</span>
                <a href="events.php" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Event</th>
                            <th>Game</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Players</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$upcoming): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No upcoming events.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($upcoming as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['event_name']) ?></td>
                            <td><?= htmlspecialchars($e['game_title']) ?></td>
                            <td><?= htmlspecialchars(date('M j, g:i A', strtotime($e['event_date']))) ?></td>
                            <td><?= htmlspecialchars($e['location'] ?? '') ?></td>
                            <td><?= (int)$e['registered'] ?> / <?= (int)$e['max_players'] ?></td>
                            <td class="text-end">
                                <?php if ((int)$e['joined'] > 0): ?>
                                    <span class="badge text-bg-success">Registered</span>
                                <?php elseif ((int)$e['registered'] >= (int)$e['max_players']): ?>
                                    <span class="badge text-bg-secondary">Full</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin-only: newest members -->
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm">
            <div class="card-header">Newest Members</div>
            <ul class="list-group list-group-flush">
                <?php if (!$newMembers): ?>
                    <li class="list-group-item text-muted">No members yet.</li>
                <?php endif; ?>
                <?php foreach ($newMembers as $m): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>
                            <?= htmlspecialchars($m['real_name']) ?>
                            <span class="text-muted small">(<?= htmlspecialchars($m['gamertag']) ?>)</span>
                        </span>
                        <span class="text-muted small">
                            <?= $m['join_date'] ? htmlspecialchars(date('M j', strtotime($m['join_date']))) : '' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
