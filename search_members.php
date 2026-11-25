<?php
// search_members.php - AJAX endpoint. Returns matching members as JSON.
// Called by members.php with fetch('search_members.php?q=...')
require 'auth.php';
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

// For AJAX we return JSON errors instead of redirecting
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admins only']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $pdo->prepare(
    'SELECT member_id, real_name, gamertag, email, program, favorite_game, gaming_platform
     FROM members
     WHERE real_name LIKE ? OR gamertag LIKE ? OR email LIKE ?
        OR program LIKE ? OR favorite_game LIKE ?
     ORDER BY real_name
     LIMIT 100'
);
$stmt->execute([$like, $like, $like, $like, $like]);

echo json_encode($stmt->fetchAll());
