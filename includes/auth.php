<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ACCESS_TOKEN_TTL = 900;
const REFRESH_TOKEN_TTL = 2592000;

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function ensure_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

function is_authenticated(): bool {
    if (empty($_SESSION['user']) || empty($_SESSION['access_expires_at'])) {
        return false;
    }

    if ((int) $_SESSION['access_expires_at'] >= time()) {
        return true;
    }

    if (!empty($_COOKIE['govlink_refresh']) && !empty($_SESSION['refresh_token_hash']) && !empty($_SESSION['refresh_expires_at'])) {
        $refreshValid = hash_equals($_SESSION['refresh_token_hash'], hash('sha256', $_COOKIE['govlink_refresh']))
            && (int) $_SESSION['refresh_expires_at'] >= time();
        if ($refreshValid) {
            $_SESSION['access_expires_at'] = time() + ACCESS_TOKEN_TTL;
            return true;
        }
    }

    return false;
}

function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: login.php?expired=1');
        exit;
    }
}

function authenticate_user(string $username, string $password): bool {
    global $pdo;
    if (!isset($pdo)) { require_once __DIR__ . '/../db.php'; }
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT id, username, password, full_name, role, status FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }
    if ($user['status'] !== 'Active') {
        return false;
    }
    if (!password_verify($password, $user['password'])) {
        return false;
    }

    $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $upd->execute([$user['id']]);

    login_user_from_record($user);
    return true;
}

function login_user_from_record(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'name'     => $user['full_name'],
        'role'     => $user['role'],
        'username' => $user['username'],
    ];
    $_SESSION['access_expires_at'] = time() + ACCESS_TOKEN_TTL;

    $refreshToken = bin2hex(random_bytes(32));
    $_SESSION['refresh_token_hash']  = hash('sha256', $refreshToken);
    $_SESSION['refresh_expires_at']  = time() + REFRESH_TOKEN_TTL;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (getenv('APP_ENV') === 'production');
    setcookie('govlink_refresh', $refreshToken, [
        'expires'  => time() + REFRESH_TOKEN_TTL,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);
}

// REMOVED: login_user() — that function bypassed all credential checks and
// hardcoded a fixed identity. It has been deleted. All logins must go through
// authenticate_user() which calls login_user_from_record().

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (getenv('APP_ENV') === 'production');
    setcookie('govlink_refresh', '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);
    session_destroy();
}

function current_user(): array {
    return $_SESSION['user'] ?? [
        'id'       => 0,
        'name'     => 'Guest',
        'role'     => 'Read-only',
        'username' => '',
    ];
}

function active_nav(string $route): string {
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $route ? ' active' : '';
}

ensure_csrf_token();
