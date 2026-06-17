<?php
// Health check endpoint — no authentication required.
// Used by Kubernetes readiness and liveness probes.
// Readiness: checks DB connectivity so Kubernetes only routes traffic when the app can serve requests.
// Liveness:  same endpoint; a PHP process that cannot reach the DB should be restarted.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dbOk = false;
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: 'mysql',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: 'exam_db'
    );
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'app_user', getenv('DB_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT            => 2,
    ]);
    $pdo->query('SELECT 1');
    $dbOk = true;
} catch (Throwable $e) {
    // Do not expose connection details in the response body.
}

if ($dbOk) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'service' => 'govlink-ems', 'db' => 'ok']);
} else {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'service' => 'govlink-ems', 'db' => 'unavailable']);
}
exit;
