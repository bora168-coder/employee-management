<?php
require_once 'includes/auth.php';
require_auth();
require_once 'db.php';

$pageTitle   = 'System Deployment Status';
$pageEyebrow = 'Portal / Admin Panel';

// Real environment detection
$phpVersion   = phpversion();
$mysqlVersion = 'Unknown';
try {
    $verStmt      = $pdo->query('SELECT VERSION() AS v');
    $mysqlVersion = $verStmt->fetchColumn();
} catch (Exception $e) {
    $mysqlVersion = 'Connection error';
}

$uploadsDir   = __DIR__ . '/uploads';
$uploadsExists = is_dir($uploadsDir);
$uploadsWrite  = $uploadsExists && is_writable($uploadsDir);

$isDocker = file_exists('/.dockerenv') || (
    file_exists('/proc/1/cgroup') &&
    str_contains((string) @file_get_contents('/proc/1/cgroup'), 'docker')
);
$isKubernetes = !empty(getenv('KUBERNETES_SERVICE_HOST'));
$environment  = $isKubernetes ? 'Kubernetes' : ($isDocker ? 'Docker' : 'Local');

$dbStatus      = ($mysqlVersion !== 'Unknown' && $mysqlVersion !== 'Connection error') ? 'Online' : 'Error';
$uploadsStatus = $uploadsWrite ? 'Online' : ($uploadsExists ? 'Warning' : 'Offline');

$services = [
    [
        'name'   => 'PHP Apache App',
        'target' => 'php-service',
        'status' => 'Online',
        'detail' => 'PHP ' . $phpVersion . ' — ' . $environment . ' runtime',
    ],
    [
        'name'   => 'MySQL Database',
        'target' => 'mysql-service',
        'status' => $dbStatus,
        'detail' => 'MySQL ' . $mysqlVersion . ' — exam_db schema',
    ],
    [
        'name'   => 'Uploads Volume',
        'target' => 'uploads-pvc',
        'status' => $uploadsStatus,
        'detail' => $uploadsWrite
            ? 'Photo storage writable'
            : ($uploadsExists ? 'Directory exists but not writable' : 'Directory not found'),
    ],
    [
        'name'   => 'Cloudflared Tunnel',
        'target' => 'external',
        'status' => 'Pending',
        'detail' => 'Requires operator credentials',
    ],
];

require_once 'includes/header.php';
?>

<section class="dashboard-grid">
    <article class="stat-card"><p>Environment</p><strong><?= h($environment) ?></strong><span>Detected at runtime</span></article>
    <article class="stat-card"><p>Application</p><strong>PHP <?= h($phpVersion) ?></strong><span>Apache runtime</span></article>
    <article class="stat-card"><p>Database</p><strong>MySQL <?= h($mysqlVersion) ?></strong><span>exam_db schema</span></article>
    <article class="stat-card"><p>Storage</p><strong>Uploads</strong><span><?= $uploadsWrite ? 'Writable' : ($uploadsExists ? 'Not writable' : 'Missing') ?></span></article>
</section>

<section class="panel">
    <div class="panel-title"><h2>Service Health</h2><span class="badge badge-info">Admin Panel</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Service</th><th>Target</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= h($service['name']) ?></td>
                    <td><?= h($service['target']) ?></td>
                    <td><span class="<?= h(status_badge_class($service['status'])) ?>"><?= h($service['status']) ?></span></td>
                    <td><?= h($service['detail']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
