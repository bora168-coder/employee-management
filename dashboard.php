<?php
require_once 'includes/auth.php';
require_auth();
require_once 'db.php';
require_once 'includes/helpers.php';

$pageTitle = 'Executive Overview';
$pageEyebrow = 'Portal / Dashboard';
$pageActionHtml = '<a class="btn btn-primary" href="index.php"><span class="material-symbols-outlined">download</span>Generate Report</a>';

$totalEmployees = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
$activeEmployees = (int) $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'")->fetchColumn();
$pendingVerification = (int) $pdo->query("SELECT COUNT(*) FROM employees WHERE national_id_number IS NULL OR national_id_number = ''")->fetchColumn();
$expiringDocuments = (int) $pdo->query("SELECT COUNT(*) FROM employee_documents WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

$deptStmt = $pdo->query('SELECT department, COUNT(*) AS total FROM employees GROUP BY department ORDER BY total DESC LIMIT 6');
$departmentCounts = $deptStmt->fetchAll();
$maxDepartmentCount = max(array_column($departmentCounts ?: [['total' => 1]], 'total'));

$recentStmt = $pdo->query('SELECT id, employee_code, family_name_latin, given_name_latin, department, created_at FROM employees ORDER BY created_at DESC LIMIT 5');
$recentEmployees = $recentStmt->fetchAll();

$pendingStmt = $pdo->query("SELECT id, employee_code, family_name_latin, given_name_latin, department, position FROM employees WHERE national_id_number IS NULL OR national_id_number = '' ORDER BY updated_at DESC LIMIT 5");
$pendingRecords = $pendingStmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="dashboard-grid">
    <article class="stat-card">
        <p>Total Employees</p>
        <strong><?= number_format($totalEmployees) ?></strong>
        <span><span class="material-symbols-outlined">trending_up</span>Current registry size</span>
    </article>
    <article class="stat-card">
        <p>Active Employees</p>
        <strong><?= number_format($activeEmployees) ?></strong>
        <span><?= $totalEmployees ? round(($activeEmployees / $totalEmployees) * 100, 1) : 0 ?>% active rate</span>
    </article>
    <article class="stat-card">
        <p>Pending Verification</p>
        <strong><?= number_format($pendingVerification) ?></strong>
        <span style="color:var(--color-danger);"><span class="material-symbols-outlined">warning</span>Requires review</span>
    </article>
    <article class="stat-card">
        <p>Expiring Documents</p>
        <strong><?= number_format($expiringDocuments) ?></strong>
        <span><span class="material-symbols-outlined">schedule</span>Within 30 days</span>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="panel-title">
            <h2>Employees by Department</h2>
            <span class="badge badge-info">Live Data</span>
        </div>
        <?php if (!$departmentCounts): ?>
            <div class="empty-state"><p>No department data yet.</p></div>
        <?php else: ?>
        <div class="bar-chart" aria-label="Employees by department">
            <?php foreach ($departmentCounts as $row): ?>
                <?php $height = max(8, ((int) $row['total'] / max(1, $maxDepartmentCount)) * 100); ?>
                <div class="bar">
                    <div class="bar-fill-wrap"><div class="bar-fill" style="height:<?= (int) $height ?>%;"></div></div>
                    <label><?= h($row['department']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel-title">
            <h2>Verification Status</h2>
        </div>
        <?php $verified = max(0, $totalEmployees - $pendingVerification); ?>
        <div class="stat-card" style="box-shadow:none;">
            <p>Verified Records</p>
            <strong><?= $totalEmployees ? round(($verified / $totalEmployees) * 100) : 0 ?>%</strong>
            <span><?= number_format($verified) ?> verified / <?= number_format($pendingVerification) ?> pending</span>
        </div>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="panel-title"><h2>Recent Activities</h2><a href="index.php" class="btn btn-sm btn-outline">View All</a></div>
        <?php if (!$recentEmployees): ?>
            <div class="empty-state"><p>No recent employee activity.</p></div>
        <?php else: ?>
        <ul class="activity-list">
            <?php foreach ($recentEmployees as $employee): ?>
                <li class="activity-item">
                    <span class="icon-tile"><span class="material-symbols-outlined">person_add</span></span>
                    <div>
                        <strong><?= h($employee['family_name_latin'] . ' ' . $employee['given_name_latin']) ?></strong>
                        <p class="text-muted">Record <?= h($employee['employee_code']) ?> added for <?= h($employee['department']) ?>.</p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel-title"><h2>Pending Approvals</h2><span class="badge badge-pending">High Priority</span></div>
        <?php if (!$pendingRecords): ?>
            <div class="empty-state"><p>No pending verification records.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Subject</th><th>Department</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($pendingRecords as $record): ?>
                    <tr>
                        <td><?= h($record['family_name_latin'] . ' ' . $record['given_name_latin']) ?><br><span class="text-muted"><?= h($record['employee_code']) ?></span></td>
                        <td><?= h($record['department']) ?></td>
                        <td><a class="btn btn-sm btn-outline" href="view.php?id=<?= (int) $record['id'] ?>">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </article>
</section>

<?php require_once 'includes/footer.php'; ?>
