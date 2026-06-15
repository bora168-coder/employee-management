<?php
require_once 'includes/auth.php';
require_auth();
$pageTitle = 'System Deployment Status';
$pageEyebrow = 'Portal / Admin Panel';

// TODO: Replace this isolated status summary with real container/Kubernetes health endpoints.
$services = [
    ['name' => 'PHP Apache App', 'target' => 'php-service', 'status' => 'Online', 'detail' => 'Serving GovLink frontend'],
    ['name' => 'MySQL Database', 'target' => 'mysql-service', 'status' => 'Online', 'detail' => 'Persistent employee registry'],
    ['name' => 'Uploads Volume', 'target' => 'uploads-pvc', 'status' => 'Online', 'detail' => 'Photo storage mounted'],
    ['name' => 'Cloudflared Tunnel', 'target' => 'external', 'status' => 'Pending', 'detail' => 'Requires operator credentials'],
];

require_once 'includes/header.php';
?>

<section class="dashboard-grid">
    <article class="stat-card"><p>Environment</p><strong>Docker/K8s</strong><span>Configured for Minikube</span></article>
    <article class="stat-card"><p>Application</p><strong>PHP 8.2</strong><span>Apache runtime</span></article>
    <article class="stat-card"><p>Database</p><strong>MySQL 8</strong><span>exam_db schema</span></article>
    <article class="stat-card"><p>Storage</p><strong>Uploads</strong><span>Photo volume enabled</span></article>
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
