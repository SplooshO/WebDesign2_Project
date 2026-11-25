<?php
// logout.php - ends the session completely and returns to the login page.
require 'auth.php';

$_SESSION = [];

// Delete the session cookie in the browser too
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

header('Location: login.php');
exit;
