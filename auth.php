<?php
// auth.php - session start, access guards, CSRF helpers and flash messages.
// Put  require 'auth.php';  at the very top of every page, then call a guard.
//
//   require 'auth.php';
//   require_login();   // any logged-in user
//   require_admin();   // admins only (also checks login)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Access guards ----------

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

// ---------- CSRF protection ----------
// Put <?= csrf_field()  inside every POST form, and call csrf_check()
// at the top of the script that receives the form.

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Invalid request token. Go back, refresh the page and try again.');
    }
}

// ---------- Flash messages (one-time notices shown after a redirect) ----------

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
