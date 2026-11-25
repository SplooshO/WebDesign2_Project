<?php
// login.php - checks the username/password with password_verify() and starts the session.
require 'auth.php';
require 'db.php';

// Already logged in? Skip the form.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$notice = isset($_GET['registered']) ? 'Registration successful. You can log in now.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // New session id after login prevents session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']  = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Remember the linked member row (needed for event registration)
            $m = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ?');
            $m->execute([$user['user_id']]);
            $_SESSION['member_id'] = $m->fetchColumn() ?: null;

            header('Location: dashboard.php');
            exit;
        }

        // Same message for "no such user" and "wrong password" so attackers learn nothing
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card shadow-sm login-card mx-auto">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">
                    <i class="bi bi-controller"></i> Member Login
                </h1>

                <?php if ($notice): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($notice) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Log In</button>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0 small">
                    New to the club? <a href="register.php">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
