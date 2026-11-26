<?php
// user_delete.php - deletes a user plus their member profile and registrations. POST only, admin only.
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}
csrf_check();

$userId = (int)($_POST['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT username FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$username = $stmt->fetchColumn();

if ($username === false) {
    flash('danger', 'User not found.');
    header('Location: users.php');
    exit;
}

if ($userId === (int)$_SESSION['user_id']) {
    flash('danger', 'You cannot delete your own account.');
    header('Location: users.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Remove everything that points at this user, child rows first
    $pdo->prepare(
        'DELETE r FROM registrations r
         JOIN members m ON m.member_id = r.member_id
         WHERE m.user_id = ?'
    )->execute([$userId]);
    $pdo->prepare('DELETE FROM members WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$userId]);

    $pdo->commit();
    flash('success', 'Deleted user ' . $username . '.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('User delete failed: ' . $e->getMessage());
    flash('danger', 'Could not delete the user. Please try again.');
}

header('Location: users.php');
exit;
