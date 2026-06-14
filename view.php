<?php
require_once 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: index.php?error=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Failed to load employee: ' . $e->getMessage());
    $employee = null;
}

if (!$employee) {
    header('Location: index.php?error=not_found');
    exit;
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Employee Details</h2>
    <div class="header-actions">
        <a href="index.php" class="btn btn-outline">&larr; Back to List</a>
        <a href="edit.php?id=<?= (int)$employee['id'] ?>" class="btn btn-warning">Edit</a>
        <a href="delete.php?id=<?= (int)$employee['id'] ?>" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="detail-card">
    <div class="detail-grid">
        <div class="detail-row">
            <span class="detail-label">Employee ID</span>
            <span class="detail-value"><?= (int)$employee['id'] ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Employee Code</span>
            <span class="detail-value"><?= htmlspecialchars($employee['employee_code'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Full Name</span>
            <span class="detail-value">
                <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?= htmlspecialchars($employee['email'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value">
                <?php if ($employee['phone']): ?>
                    <?= htmlspecialchars($employee['phone'], ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                    <em class="text-muted">Not provided</em>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Department</span>
            <span class="detail-value"><?= htmlspecialchars($employee['department'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Position</span>
            <span class="detail-value"><?= htmlspecialchars($employee['position'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Salary</span>
            <span class="detail-value">$<?= number_format((float)$employee['salary'], 2) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Hire Date</span>
            <span class="detail-value"><?= htmlspecialchars($employee['hire_date'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <span class="badge badge-<?= $employee['status'] === 'Active' ? 'active' : 'inactive' ?>">
                    <?= htmlspecialchars($employee['status'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Created At</span>
            <span class="detail-value"><?= htmlspecialchars($employee['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Updated At</span>
            <span class="detail-value"><?= htmlspecialchars($employee['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
