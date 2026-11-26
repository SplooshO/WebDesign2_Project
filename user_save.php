<?php
// user_save.php - handles two admin actions from users.php:
//   action=role      change a user's role
//   action=password  reset a user's password
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}
csrf_check();

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);

$stmt = $pdo->prepare('SELECT user_id, username FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    flash('danger', 'User not found.');
    header('Location: users.php');
    exit;
}

try {
    if ($action === 'role') {
        $role = $_POST['role'] ?? '';

        if (!in_array($role, ['admin', 'member'], true)) {
            flash('danger', 'Invalid role.');
        } elseif ($userId === (int)$_SESSION['user_id']) {
            // Stops the last admin from demoting themselves and locking everyone out
            flash('danger', 'You cannot change your own role.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?');
            $stmt->execute([$role, $userId]);
            flash('success', $user['username'] . ' is now ' . $role . '.');
        }

    } elseif ($action === 'password') {
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            flash('danger', 'Password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash('danger', 'Passwords do not match.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            flash('success', 'Password updated for ' . $user['username'] . '.');
        }

    } else {
        flash('danger', 'Unknown action.');
    }
} catch (PDOException $e) {
    error_log('User update failed: ' . $e->getMessage());
    flash('danger', 'Could not save the change. Please try again.');
}

header('Location: users.php');
exit;
