<?php
/**
 * One-time admin seeder.
 * Run via CLI ONLY:  php scripts/create_admin.php
 *
 * SECURITY: This script is restricted to CLI execution.
 * It will refuse to run when accessed through a web server.
 * Remove or move this file outside the web root after first use.
 */

// FIX: block web execution — this script must only run from the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. This script may only be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../db.php';

$username = 'admin';
$email    = 'admin@govlink.local';
// FIX: password is no longer hardcoded. Generate a random password at seed time.
$password = bin2hex(random_bytes(16));   // 32-character random hex string
$fullName = 'System Administrator';
$role     = 'Administrator';
$status   = 'Active';

// Check for existing username or email before inserting so we can give a precise message.
$checkU = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$checkU->execute([$username]);
if ($checkU->fetch()) {
    echo "Error: username '$username' already exists. No changes made.\n";
    echo "Use users.php in the application to manage existing accounts.\n";
    exit(1);
}
$checkE = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$checkE->execute([$email]);
if ($checkE->fetch()) {
    echo "Error: email '$email' already in use. No changes made.\n";
    echo "Use users.php in the application to manage existing accounts.\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password, full_name, role, status)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$username, $email, $hash, $fullName, $role, $status]);

// Safe to print credentials only in CLI; the web guard above prevents browser access.
echo "Admin user created.\n";
echo "Username: $username\n";
echo "Password: $password\n";
echo "IMPORTANT: Copy the password now. It is not stored in plain text anywhere.\n";
echo "Change the password after first login.\n";
