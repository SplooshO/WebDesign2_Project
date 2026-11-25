<?php
// member_save.php - receives the form from member_form.php.
// Creates a new member (plus login account) or updates an existing member.
require 'auth.php';
require 'db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: members.php');
    exit;
}
csrf_check();

$platforms = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Mobile'];

$id     = (int)($_POST['member_id'] ?? 0);
$isEdit = $id > 0;

$data = [];
foreach (['real_name', 'gamertag', 'email', 'program', 'favorite_game', 'gaming_platform'] as $f) {
    $data[$f] = trim($_POST[$f] ?? '');
}

$errors = [];

// ---------- Validation ----------
if ($data['real_name'] === '' || strlen($data['real_name']) > 100) {
    $errors[] = 'Please enter a real name (up to 100 characters).';
}
if ($data['gamertag'] === '' || strlen($data['gamertag']) > 50) {
    $errors[] = 'Please enter a gamertag (up to 50 characters).';
}
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) {
    $errors[] = 'Please enter a valid email address.';
}
if ($data['gaming_platform'] !== '' && !in_array($data['gaming_platform'], $platforms, true)) {
    $errors[] = 'Please choose a platform from the list.';
}

$username = '';
$password = '';
$role     = 'member';
if (!$isEdit) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = ($_POST['role'] ?? 'member') === 'admin' ? 'admin' : 'member';

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters: letters, numbers and underscores only.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
}

// ---------- Duplicate checks (ignore the member being edited) ----------
if (!$errors) {
    $stmt = $pdo->prepare('SELECT gamertag, email FROM members WHERE (gamertag = ? OR email = ?) AND member_id <> ?');
    $stmt->execute([$data['gamertag'], $data['email'], $id]);
    foreach ($stmt->fetchAll() as $row) {
        if (strcasecmp($row['gamertag'], $data['gamertag']) === 0) {
            $errors[] = 'That gamertag is already in use.';
        }
        if (strcasecmp($row['email'], $data['email']) === 0) {
            $errors[] = 'That email is already registered.';
        }
    }

    if (!$isEdit) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'That username is already taken.';
        }
    }
}

// Send the admin back to the form with their input and the problems found
function back_to_form(array $errors, array $old, int $id): void
{
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $old;   // passwords are never included
    header('Location: member_form.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}

if ($errors) {
    $old = $data;
    if (!$isEdit) {
        $old['username'] = $username;
        $old['role']     = $role;
    }
    back_to_form($errors, $old, $id);
}

// ---------- Save ----------
try {
    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT member_id FROM members WHERE member_id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            flash('danger', 'Member not found.');
            header('Location: members.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE members
             SET real_name = ?, gamertag = ?, email = ?, program = ?, favorite_game = ?, gaming_platform = ?
             WHERE member_id = ?'
        );
        $stmt->execute([
            $data['real_name'], $data['gamertag'], $data['email'],
            $data['program'], $data['favorite_game'], $data['gaming_platform'],
            $id,
        ]);
        flash('success', 'Member updated.');
    } else {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO members (user_id, real_name, gamertag, email, program, favorite_game, gaming_platform, join_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())'
        );
        $stmt->execute([
            $userId, $data['real_name'], $data['gamertag'], $data['email'],
            $data['program'], $data['favorite_game'], $data['gaming_platform'],
        ]);

        $pdo->commit();
        flash('success', 'Member added.');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Member save failed: ' . $e->getMessage());
    $old = $data;
    if (!$isEdit) {
        $old['username'] = $username;
        $old['role']     = $role;
    }
    back_to_form(['Could not save the member. Please try again.'], $old, $id);
}

header('Location: members.php');
exit;
