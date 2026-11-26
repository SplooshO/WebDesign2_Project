<?php
// register_event.php - AJAX endpoint. Registers the logged-in member for an event.
// Called from events.php with fetch(). Always answers in JSON.
require 'auth.php';
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

// Stop with a JSON error (and undo any half-finished database work)
function fail(PDO $pdo, int $code, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    fail($pdo, 401, 'Please log in first.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail($pdo, 405, 'Invalid request.');
}
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
    fail($pdo, 403, 'Security token expired. Refresh the page and try again.');
}

$eventId = (int)($_POST['event_id'] ?? 0);

// The member is taken from the session, never from the request, so nobody
// can register somebody else.
$stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$memberId = $stmt->fetchColumn();
if (!$memberId) {
    fail($pdo, 400, 'Your account has no member profile, so it cannot register for events.');
}

try {
    $pdo->beginTransaction();

    // FOR UPDATE locks this event row so two people can't grab the last spot at once
    $stmt = $pdo->prepare('SELECT event_name, event_date, max_players FROM events WHERE event_id = ? FOR UPDATE');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        fail($pdo, 404, 'That event no longer exists.');
    }
    if (strtotime($event['event_date']) < time()) {
        fail($pdo, 400, 'That event has already taken place.');
    }

    $stmt = $pdo->prepare('SELECT 1 FROM registrations WHERE member_id = ? AND event_id = ?');
    $stmt->execute([$memberId, $eventId]);
    if ($stmt->fetchColumn()) {
        fail($pdo, 409, 'You are already registered for this event.');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ?');
    $stmt->execute([$eventId]);
    $count = (int)$stmt->fetchColumn();
    $max   = (int)$event['max_players'];

    if ($count >= $max) {
        fail($pdo, 409, 'Sorry, this event is full.');
    }

    $stmt = $pdo->prepare('INSERT INTO registrations (member_id, event_id) VALUES (?, ?)');
    $stmt->execute([$memberId, $eventId]);

    $pdo->commit();

    $count++;
    echo json_encode([
        'ok'         => true,
        'message'    => 'You are registered for ' . $event['event_name'] . '!',
        'registered' => $count,
        'max'        => $max,
        'full'       => $count >= $max,
    ]);
} catch (PDOException $e) {
    error_log('Event registration failed: ' . $e->getMessage());
    // 23000 = the UNIQUE(member_id, event_id) constraint caught a duplicate
    if ($e->getCode() === '23000') {
        fail($pdo, 409, 'You are already registered for this event.');
    }
    fail($pdo, 500, 'Something went wrong. Please try again.');
}
