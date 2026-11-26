<?php
// profile.php - every logged-in user sees their OWN details and registrations.
require 'auth.php';
require 'db.php';
require_login();

// The user id always comes from the session, never from the URL
$stmt = $pdo->prepare(
    'SELECT u.username, u.role, u.created_at,
            m.member_id, m.real_name, m.gamertag, m.email, m.program,
            m.favorite_game, m.gaming_platform, m.join_date
     FROM users u
     LEFT JOIN members m ON m.user_id = u.user_id
     WHERE u.user_id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();

if (!$me) {
    // The account was deleted while logged in
    header('Location: logout.php');
    exit;
}

$registrations = [];
if ($me['member_id']) {
    $stmt = $pdo->prepare(
        'SELECT r.registration_id, e.event_name, e.game_title, e.event_date, e.location
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         WHERE r.member_id = ?
         ORDER BY e.event_date ASC'
    );
    $stmt->execute([$me['member_id']]);
    $registrations = $stmt->fetchAll();
}

$flash = get_flash();
$pageTitle = 'My Profile';
require 'header.php';

function show(?string $v): string
{
    return ($v === null || $v === '') ? '-' : htmlspecialchars($v);
}
?>

<h1 class="h3 mb-4">My Profile</h1>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <!-- Account card -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">Account</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Username</span>
                    <span><?= show($me['username']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Role</span>
                    <span class="badge text-bg-info align-self-center"><?= show($me['role']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Account created</span>
                    <span><?= $me['created_at'] ? htmlspecialchars(date('M j, Y', strtotime($me['created_at']))) : '-' ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Member details card -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">Member Details</div>
            <?php if ($me['member_id']): ?>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Real name</dt>
                    <dd class="col-sm-8"><?= show($me['real_name']) ?></dd>

                    <dt class="col-sm-4">Gamertag</dt>
                    <dd class="col-sm-8"><?= show($me['gamertag']) ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= show($me['email']) ?></dd>

                    <dt class="col-sm-4">Program</dt>
                    <dd class="col-sm-8"><?= show($me['program']) ?></dd>

                    <dt class="col-sm-4">Favourite game</dt>
                    <dd class="col-sm-8"><?= show($me['favorite_game']) ?></dd>

                    <dt class="col-sm-4">Platform</dt>
                    <dd class="col-sm-8"><?= show($me['gaming_platform']) ?></dd>

                    <dt class="col-sm-4">Member since</dt>
                    <dd class="col-sm-8 mb-0"><?= $me['join_date'] ? htmlspecialchars(date('M j, Y', strtotime($me['join_date']))) : '-' ?></dd>
                </dl>
            </div>
            <?php else: ?>
            <div class="card-body text-muted">
                This account has no member profile yet, so it can't register for events.
                <?php if (($me['role'] ?? '') === 'admin'): ?>
                    Add a member row linked to this user to enable registration.
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- My event registrations -->
<div class="card shadow-sm">
    <div class="card-header">My Event Registrations</div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Event</th>
                    <th>Game</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$registrations): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            You haven't registered for any events yet. <a href="events.php">Browse events</a>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($registrations as $r):
                    $ts = strtotime($r['event_date']);
                    $isPast = $ts < time();
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['event_name']) ?></td>
                    <td><?= htmlspecialchars($r['game_title']) ?></td>
                    <td><?= htmlspecialchars(date('M j, Y g:i A', $ts)) ?></td>
                    <td><?= show($r['location']) ?></td>
                    <td class="text-end">
                        <?php if ($isPast): ?>
                            <span class="badge text-bg-secondary">Past</span>
                        <?php else: ?>
                            <form method="post" action="cancel_registration.php" class="d-inline"
                                  onsubmit="return confirm('Cancel your registration?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="registration_id" value="<?= (int)$r['registration_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
