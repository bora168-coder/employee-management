<?php
require_once 'db.php';

$search = trim($_GET['search'] ?? '');

$successMessages = [
    'created' => 'Employee created successfully.',
    'updated' => 'Employee updated successfully.',
    'deleted' => 'Employee deleted successfully.',
];
$errorMessages = [
    'not_found'   => 'Employee was not found.',
    'invalid_id'  => 'Invalid employee ID.',
    'delete_fail' => 'Failed to delete employee.',
];

$successMsg = '';
$errorMsg   = '';

if (!empty($_GET['success']) && isset($successMessages[$_GET['success']])) {
    $successMsg = $successMessages[$_GET['success']];
}
if (!empty($_GET['error']) && isset($errorMessages[$_GET['error']])) {
    $errorMsg = $errorMessages[$_GET['error']];
}

try {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare(
            "SELECT * FROM employees
             WHERE employee_code LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                OR email LIKE ? OR department LIKE ? OR position LIKE ?
             ORDER BY id DESC"
        );
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
    } else {
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
    }
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to load employees: ' . $e->getMessage());
    $employees = [];
    $errorMsg  = 'Unable to load employees.';
}

require_once 'includes/header.php';
?>

<?php if ($successMsg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="page-header">
    <h2>Employee List</h2>
    <a href="create.php" class="btn btn-primary">+ Add Employee</a>
</div>

<form method="GET" action="index.php" class="search-form">
    <input type="text" name="search" placeholder="Search by name, code, email, department..."
           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
    <a href="index.php" class="btn btn-outline">Reset</a>
</form>

<?php if (empty($employees)): ?>
    <div class="empty-state">
        <?php if ($search !== ''): ?>
            <p>No employees found for "<strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>".</p>
        <?php else: ?>
            <p>No employees yet. <a href="create.php">Add the first employee</a>.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Code</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Hire Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $i => $emp): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($emp['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($emp['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($emp['department'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($emp['position'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge badge-<?= $emp['status'] === 'Active' ? 'active' : 'inactive' ?>">
                            <?= htmlspecialchars($emp['status'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($emp['hire_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="actions">
                        <a href="view.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-info">View</a>
                        <a href="edit.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="delete.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
