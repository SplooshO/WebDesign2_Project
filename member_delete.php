<?php
// member_delete.php - deletes a member, their registrations and their login account.
// Only accepts POST (from the modal in members.php) so a link or image can't trigger it.
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: members.php');
    exit;
}
csrf_check();

$id = (int)($_POST['member_id'] ?? 0);

$stmt = $pdo->prepare('SELECT member_id, user_id, real_name FROM members WHERE member_id = ?');
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    flash('danger', 'Member not found.');
    header('Location: members.php');
    exit;
}

// Don't let an admin delete the account they are logged in with
if ((int)$member['user_id'] === (int)$_SESSION['user_id']) {
    flash('danger', 'You cannot delete your own account.');
    header('Location: members.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare('DELETE FROM registrations WHERE member_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM members WHERE member_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$member['user_id']]);

    $pdo->commit();
    flash('success', 'Deleted ' . $member['real_name'] . '.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Member delete failed: ' . $e->getMessage());
    flash('danger', 'Could not delete the member. Please try again.');
}

header('Location: members.php');
exit;
