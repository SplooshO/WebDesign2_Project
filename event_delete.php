<?php
// event_delete.php - deletes an event and its registrations. POST only, admin only.
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit;
}
csrf_check();

$id = (int)($_POST['event_id'] ?? 0);

$stmt = $pdo->prepare('SELECT event_name FROM events WHERE event_id = ?');
$stmt->execute([$id]);
$name = $stmt->fetchColumn();

if ($name === false) {
    flash('danger', 'Event not found.');
    header('Location: events.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM registrations WHERE event_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM events WHERE event_id = ?')->execute([$id]);
    $pdo->commit();
    flash('success', 'Deleted event "' . $name . '".');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Event delete failed: ' . $e->getMessage());
    flash('danger', 'Could not delete the event. Please try again.');
}

header('Location: events.php');
exit;
