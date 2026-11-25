<?php
// register.php - creates a users row AND a linked members row in one transaction.
require 'auth.php';
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$platforms = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Mobile'];
$errors = [];

// Remember what was typed so the form isn't wiped on an error (never the passwords)
$old = [
    'username' => '', 'real_name' => '', 'gamertag' => '', 'email' => '',
    'program' => '', 'favorite_game' => '', 'gaming_platform' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation (never trust the browser alone) ----
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $old['username'])) {
        $errors[] = 'Username must be 3-50 characters: letters, numbers and underscores only.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if ($old['real_name'] === '' || strlen($old['real_name']) > 100) {
        $errors[] = 'Please enter your real name (up to 100 characters).';
    }
    if ($old['gamertag'] === '' || strlen($old['gamertag']) > 50) {
        $errors[] = 'Please enter a gamertag (up to 50 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['gaming_platform'] !== '' && !in_array($old['gaming_platform'], $platforms, true)) {
        $errors[] = 'Please choose a platform from the list.';
    }

    // ---- Duplicate checks (friendly messages) ----
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([$old['username']]);
        if ($stmt->fetchColumn()) {
            $errors[] = 'That username is already taken.';
        }

        $stmt = $pdo->prepare('SELECT gamertag, email FROM members WHERE gamertag = ? OR email = ?');
        $stmt->execute([$old['gamertag'], $old['email']]);
        foreach ($stmt->fetchAll() as $row) {
            if (strcasecmp($row['gamertag'], $old['gamertag']) === 0) {
                $errors[] = 'That gamertag is already in use.';
            }
            if (strcasecmp($row['email'], $old['email']) === 0) {
                $errors[] = 'That email is already registered.';
            }
        }
    }

    // ---- Save ----
    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Role is ALWAYS 'member' here. Never read it from the form,
            // or anyone could make themselves an admin.
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $stmt->execute([
                $old['username'],
                password_hash($password, PASSWORD_DEFAULT),
                'member',
            ]);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO members (user_id, real_name, gamertag, email, program, favorite_game, gaming_platform, join_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())'
            );
            $stmt->execute([
                $userId,
                $old['real_name'],
                $old['gamertag'],
                $old['email'],
                $old['program'],
                $old['favorite_game'],
                $old['gaming_platform'],
            ]);

            $pdo->commit();

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();   // undo the users row if the members insert failed
            }
            error_log('Registration failed: ' . $e->getMessage());
            // 23000 = unique constraint hit (e.g. two people registering at once)
            $errors[] = ($e->getCode() === '23000')
                ? 'That username, gamertag or email is already in use.'
                : 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h1 class="h5 mb-0"><i class="bi bi-person-plus"></i> Create an Account</h1>
            </div>
            <div class="card-body">

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php" class="needs-validation" novalidate>
                    <h2 class="h6 text-muted mb-3">Account</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= htmlspecialchars($old['username']) ?>"
                                   pattern="[A-Za-z0-9_]{3,50}" required>
                            <div class="invalid-feedback">3-50 characters: letters, numbers, underscores.</div>
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required>
                            <div class="invalid-feedback">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   minlength="8" required>
                            <div class="invalid-feedback" id="confirmFeedback">Passwords must match.</div>
                        </div>
                    </div>

                    <h2 class="h6 text-muted mb-3">Member Profile</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="real_name" class="form-label">Real Name</label>
                            <input type="text" class="form-control" id="real_name" name="real_name"
                                   value="<?= htmlspecialchars($old['real_name']) ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="gamertag" class="form-label">Gamertag</label>
                            <input type="text" class="form-control" id="gamertag" name="gamertag"
                                   value="<?= htmlspecialchars($old['gamertag']) ?>" maxlength="50" required>
                            <div class="invalid-feedback">Please enter a gamertag.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($old['email']) ?>" maxlength="100" required>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="program" class="form-label">Program</label>
                            <input type="text" class="form-control" id="program" name="program"
                                   value="<?= htmlspecialchars($old['program']) ?>" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="favorite_game" class="form-label">Favourite Game</label>
                            <input type="text" class="form-control" id="favorite_game" name="favorite_game"
                                   value="<?= htmlspecialchars($old['favorite_game']) ?>" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="gaming_platform" class="form-label">Gaming Platform</label>
                            <select class="form-select" id="gaming_platform" name="gaming_platform">
                                <option value="">Choose...</option>
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>"
                                        <?= $old['gaming_platform'] === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap validation + a check that both password boxes match
(function () {
    const form = document.querySelector('.needs-validation');
    const pw = document.getElementById('password');
    const confirmPw = document.getElementById('confirm_password');

    function checkMatch() {
        confirmPw.setCustomValidity(pw.value === confirmPw.value ? '' : 'Passwords do not match');
    }
    pw.addEventListener('input', checkMatch);
    confirmPw.addEventListener('input', checkMatch);

    form.addEventListener('submit', function (e) {
        checkMatch();
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
})();
</script>

<?php require 'footer.php'; ?>
