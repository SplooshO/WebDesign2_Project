<?php
// auth.php - session start + access guards.
// Put  require 'auth.php';  at the very top of every page, then call a guard.
//
//   require 'auth.php';
//   require_login();   // any logged-in user
//   require_admin();   // admins only (also checks login)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Admins only.');
    }
}
