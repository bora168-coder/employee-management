<?php
require_once 'includes/auth.php';
require_auth();
require_once 'db.php';
require_once 'includes/helpers.php';

$pageTitle = ui_text('verification');
$pageEyebrow = ui_text('portal') . ' / ' . ui_text('verification');

$stmt = $pdo->query("SELECT e.id, e.employee_code, e.family_name_latin, e.given_name_latin, e.department, e.position, e.status,
    SUM(CASE WHEN d.expiry_date IS NOT NULL AND d.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_docs,
    SUM(CASE WHEN d.expiry_date IS NOT NULL AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_docs
    FROM employees e
    LEFT JOIN employee_documents d ON d.employee_id = e.id
    WHERE e.national_id_number IS NULL OR e.national_id_number = '' OR d.expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    GROUP BY e.id
    ORDER BY expired_docs DESC, expiring_docs DESC, e.updated_at DESC");
$records = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="filter-bar">
    <span class="badge badge-pending"><?= count($records) ?> <?= h(ui_text('pending_verification')) ?></span>
    <a href="<?= h(lang_url('index.php')) ?>" class="btn btn-outline"><span class="material-symbols-outlined">badge</span><?= h(ui_text('employee_registry')) ?></a>
</section>

<?php if (!$records): ?>
    <div class="empty-state"><p><?= h(ui_text('all_verified')) ?></p></div>
<?php else: ?>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr><th><?= h(ui_text('employee')) ?></th><th><?= h(ui_text('department')) ?></th><th><?= h(ui_text('status')) ?></th><th><?= h(ui_text('issue')) ?></th><th><?= h(ui_text('action')) ?></th></tr>
        </thead>
        <tbody>
        <?php foreach ($records as $record): ?>
            <?php
            $issues = [];
            if ((int) $record['expired_docs'] > 0) { $issues[] = 'Expired document'; }
            if ((int) $record['expiring_docs'] > 0) { $issues[] = 'Document expiring soon'; }
            if (!$issues) { $issues[] = 'Missing national ID'; }
            ?>
            <tr>
                <td><strong><?= h($record['family_name_latin'] . ' ' . $record['given_name_latin']) ?></strong><br><span class="text-muted"><?= h($record['employee_code']) ?></span></td>
                <td><?= h($record['department']) ?><br><span class="text-muted"><?= h($record['position']) ?></span></td>
                <td><span class="<?= h(status_badge_class($record['status'])) ?>"><?= h($record['status']) ?></span></td>
                <td><?= h(implode(', ', $issues)) ?></td>
                <td><a class="btn btn-sm btn-primary" href="<?= h(lang_url('view.php?id=' . (int) $record['id'])) ?>"><?= h(ui_text('review')) ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="mobile-cards">
    <?php foreach ($records as $record): ?>
    <article class="mobile-card">
        <span class="icon-tile"><span class="material-symbols-outlined">verified_user</span></span>
        <div>
            <strong><?= h($record['family_name_latin'] . ' ' . $record['given_name_latin']) ?></strong>
            <p class="text-muted"><?= h($record['department']) ?> / <?= h($record['employee_code']) ?></p>
            <a class="btn btn-sm btn-outline" href="<?= h(lang_url('view.php?id=' . (int) $record['id'])) ?>"><?= h(ui_text('review')) ?></a>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
