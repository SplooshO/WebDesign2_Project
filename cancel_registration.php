<?php
// cancel_registration.php - lets a member cancel THEIR OWN registration (from profile.php).
require 'auth.php';
require 'db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}
csrf_check();

$registrationId = (int)($_POST['registration_id'] ?? 0);

$stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$memberId = $stmt->fetchColumn();

if ($memberId) {
    // The member_id condition makes sure you can only cancel your own registration;
    // the date condition stops cancelling events that are already over.
    $stmt = $pdo->prepare(
        'DELETE r FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         WHERE r.registration_id = ? AND r.member_id = ? AND e.event_date > NOW()'
    );
    $stmt->execute([$registrationId, $memberId]);

    if ($stmt->rowCount() > 0) {
        flash('success', 'Registration cancelled.');
    } else {
        flash('danger', 'That registration could not be cancelled.');
    }
} else {
    flash('danger', 'Your account has no member profile.');
}

header('Location: profile.php');
exit;
