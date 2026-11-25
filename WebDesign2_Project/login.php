<?php
// login.php - LAYOUT ONLY for now. We'll add the PDO + password_verify logic later.
$pageTitle = 'Login';
$error = $error ?? ''; // later: set this when login fails
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card shadow-sm login-card mx-auto">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">
                    <i class="bi bi-controller"></i> Member Login
                </h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username"
                               name="username" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password"
                               name="password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Log In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
