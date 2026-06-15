<?php
require_once 'includes/auth.php';

if (is_authenticated()) {
    header('Location: dashboard.php');
    exit;
}

$errors   = [];
$username = trim($_POST['username'] ?? '');

// Brute-force protection: max 10 attempts per 15-minute window per session.
const LOGIN_MAX_ATTEMPTS = 10;
const LOGIN_WINDOW_SECS  = 900;

$now            = time();
$loginAttempts  = $_SESSION['login_attempts']  ?? 0;
$loginWindowEnd = $_SESSION['login_window_end'] ?? ($now + LOGIN_WINDOW_SECS);

if ($now > $loginWindowEnd) {
    $loginAttempts  = 0;
    $loginWindowEnd = $now + LOGIN_WINDOW_SECS;
}

$rateLimited = ($loginAttempts >= LOGIN_MAX_ATTEMPTS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($rateLimited) {
        $errors[] = 'Too many failed attempts. Please wait a few minutes before trying again.';
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif ($username === '' || trim($_POST['password'] ?? '') === '') {
        $errors[] = 'Username and password are required.';
    } else {
        require_once 'db.php';
        if (authenticate_user($username, trim($_POST['password'] ?? ''))) {
            // Reset counter on successful login.
            unset($_SESSION['login_attempts'], $_SESSION['login_window_end']);
            header('Location: dashboard.php');
            exit;
        }
        $loginAttempts++;
        $_SESSION['login_attempts']  = $loginAttempts;
        $_SESSION['login_window_end'] = $loginWindowEnd;
        $errors[] = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GovLink Pro EMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <h1>GovLink Pro EMS</h1>
        <p>Sign in to manage employee records, verification queues, and administrative operations.</p>

        <?php if (!empty($_GET['expired'])): ?>
            <div class="alert alert-error">Your session expired. Please sign in again.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= h(ensure_csrf_token()) ?>">
            <div class="form-group">
                <label for="username">Username or email</label>
                <input id="username" name="username" type="text" autocomplete="username" required value="<?= h($username) ?>">
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label for="password">Password</label>
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button type="button" aria-label="Show password" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password');">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%;margin-top:24px;">Sign In</button>
        </form>
    </main>
</body>
</html>
