<?php
// header.php - shared top of every page (navbar + opening <main>)
// Usage on each page:  $pageTitle = 'Members'; require 'header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'TRU Gaming Club';
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin    = $isLoggedIn && ($_SESSION['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- This line makes the page scale properly on phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | TRU Gaming Club</title>

    <!-- Bootstrap 5 CSS from CDN (no install needed) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (optional, for small icons) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Your own small tweaks -->
    <link href="style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-controller"></i> TRU Gaming Club
        </a>

        <!-- Hamburger button that appears on small screens -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php if ($isLoggedIn): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>

                <?php if ($isAdmin): ?>
                <!-- Admin-only links. Still protect these pages on the server too! -->
                <li class="nav-item"><a class="nav-link" href="members.php">Members</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
                <?php endif; ?>
            </ul>

            <span class="navbar-text me-3">
                <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                <span class="badge text-bg-info"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
            </span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">
