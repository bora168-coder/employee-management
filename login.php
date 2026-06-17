<?php
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

$locale = current_locale();

if (is_authenticated()) {
    header('Location: ' . lang_url('dashboard.php'));
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
        $errors[] = ui_text('too_many_attempts');
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = ui_text('invalid_token');
    } elseif ($username === '' || trim($_POST['password'] ?? '') === '') {
        $errors[] = ui_text('required_credentials');
    } else {
        require_once 'db.php';
        if (authenticate_user($username, trim($_POST['password'] ?? ''))) {
            // Reset counter on successful login.
            unset($_SESSION['login_attempts'], $_SESSION['login_window_end']);
            header('Location: ' . lang_url('dashboard.php'));
            exit;
        }
        $loginAttempts++;
        $_SESSION['login_attempts']  = $loginAttempts;
        $_SESSION['login_window_end'] = $loginWindowEnd;
        $errors[] = ui_text('invalid_credentials');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= h($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(ui_text('sign_in')) ?> - <?= h(ui_text('app_name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:12px;">
            <h1 style="margin:0;"><?= h(ui_text('app_name')) ?></h1>
            <a class="language-toggle" href="<?= h(lang_url('login.php', $locale === 'en' ? 'km' : 'en', $_GET)) ?>" title="<?= h(ui_text('language_toggle_title')) ?>"><span class="material-symbols-outlined">language</span><?= h(ui_text('language_toggle')) ?></a>
        </div>
        <p><?= h(ui_text('sign_in_message')) ?></p>

        <?php if (!empty($_GET['expired'])): ?>
            <div class="alert alert-error"><?= h(ui_text('session_expired')) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= h(ensure_csrf_token()) ?>">
            <div class="form-group">
                <label for="username"><?= h(ui_text('username_or_email')) ?></label>
                <input id="username" name="username" type="text" autocomplete="username" required value="<?= h($username) ?>">
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label for="password"><?= h(ui_text('password')) ?></label>
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button type="button" aria-label="<?= h(ui_text('show_password')) ?>" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.setAttribute('aria-label',p.type==='password'?'<?= h(ui_text('show_password')) ?>':'<?= h(ui_text('hide_password')) ?>');">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%;margin-top:24px;"><?= h(ui_text('sign_in')) ?></button>
        </form>
    </main>
</body>
</html>
