<?php
// dashboard.php - LAYOUT ONLY. The numbers are placeholders until we add PDO queries.
$pageTitle = 'Dashboard';
require 'header.php';

// Later: redirect to login.php if not logged in, then run COUNT(*) queries.
$totalMembers = 0;
$totalEvents  = 0;
$myRegs       = 0;
?>

<h1 class="h3 mb-4">Dashboard</h1>

<!-- Row of stat cards. col-12 = full width on phones, col-md-4 = 3 across on tablets and up -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total Members</div>
                <div class="display-6"><?= (int)$totalMembers ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Upcoming Events</div>
                <div class="display-6"><?= (int)$totalEvents ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card stat-card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">My Registrations</div>
                <div class="display-6"><?= (int)$myRegs ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming events table -->
<div class="card shadow-sm">
    <div class="card-header">Upcoming Events</div>
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Event</th>
                    <th>Game</th>
                    <th>Date</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">No events yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require 'footer.php'; ?>
