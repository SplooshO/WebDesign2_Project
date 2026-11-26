<?php
// event_save.php - receives the form from event_form.php. Creates or updates an event.
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php');
    exit;
}
csrf_check();

$id     = (int)($_POST['event_id'] ?? 0);
$isEdit = $id > 0;

$data = [
    'event_name'  => trim($_POST['event_name'] ?? ''),
    'game_title'  => trim($_POST['game_title'] ?? ''),
    'event_date'  => trim($_POST['event_date'] ?? ''),
    'location'    => trim($_POST['location'] ?? ''),
    'max_players' => trim($_POST['max_players'] ?? ''),
];

$errors = [];

// ---------- Validation ----------
if ($data['event_name'] === '' || strlen($data['event_name']) > 100) {
    $errors[] = 'Please enter an event name (up to 100 characters).';
}
if ($data['game_title'] === '' || strlen($data['game_title']) > 100) {
    $errors[] = 'Please enter a game title (up to 100 characters).';
}
if (strlen($data['location']) > 100) {
    $errors[] = 'Location must be 100 characters or fewer.';
}

// datetime-local sends 2026-12-05T18:00
$dt = DateTime::createFromFormat('Y-m-d\TH:i', $data['event_date']);
$dtErrors = DateTime::getLastErrors();
if (!$dt || ($dtErrors && ($dtErrors['warning_count'] || $dtErrors['error_count']))) {
    $errors[] = 'Please choose a valid date and time.';
    $dbDate = null;
} else {
    $dbDate = $dt->format('Y-m-d H:i:s');
}

$maxPlayers = filter_var($data['max_players'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
if ($maxPlayers === false) {
    $errors[] = 'Max players must be a whole number from 1 to 1000.';
}

// When editing, the limit can't drop below the people already registered
if ($isEdit && $maxPlayers !== false) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ?');
    $stmt->execute([$id]);
    $already = (int)$stmt->fetchColumn();
    if ($maxPlayers < $already) {
        $errors[] = "Max players can't be lower than the $already member(s) already registered.";
    }
}

if ($errors) {
    $_SESSION['event_errors'] = $errors;
    $_SESSION['event_old']    = $data;
    header('Location: event_form.php' . ($isEdit ? '?id=' . $id : ''));
    exit;
}

// ---------- Save ----------
try {
    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT event_id FROM events WHERE event_id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            flash('danger', 'Event not found.');
            header('Location: events.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE events
             SET event_name = ?, game_title = ?, event_date = ?, location = ?, max_players = ?
             WHERE event_id = ?'
        );
        $stmt->execute([$data['event_name'], $data['game_title'], $dbDate, $data['location'], $maxPlayers, $id]);
        flash('success', 'Event updated.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO events (event_name, game_title, event_date, location, max_players)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$data['event_name'], $data['game_title'], $dbDate, $data['location'], $maxPlayers]);
        flash('success', 'Event added.');
    }
} catch (PDOException $e) {
    error_log('Event save failed: ' . $e->getMessage());
    $_SESSION['event_errors'] = ['Could not save the event. Please try again.'];
    $_SESSION['event_old']    = $data;
    header('Location: event_form.php' . ($isEdit ? '?id=' . $id : ''));
    exit;
}

header('Location: events.php');
exit;
