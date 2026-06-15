<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/auth.php';
require_auth();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid employee ID.'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, employee_code, family_name_kh, given_name_kh,
            family_name_latin, given_name_latin, department, position, photo_path
     FROM employees WHERE id = ? LIMIT 1'
);
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: index.php?error=' . urlencode('Employee not found.'));
    exit;
}

$deleteError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $deleteError = 'Invalid request token. Please try again.';
    } else {
        $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$postId || $postId !== $id) {
            $deleteError = 'Invalid employee ID.';
        } else {
            try {
                $photoPath = $employee['photo_path'];
                $stmt = $pdo->prepare('DELETE FROM employees WHERE id = ?');
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) {
                    // Remove photo file after successful delete (CASCADE handles child rows)
                    if ($photoPath && file_exists($photoPath)) {
                        @unlink($photoPath);
                    }
                    header('Location: index.php?success=' . urlencode('Employee deleted successfully.'));
                    exit;
                }
                $deleteError = 'Employee could not be deleted.';
            } catch (PDOException $e) {
                error_log('Failed to delete employee: ' . $e->getMessage());
                $deleteError = 'Unable to delete employee. Please try again.';
            }
        }
    }
}

$pageTitle = 'Delete Employee';
$pageEyebrow = 'Portal / Employees / Delete';
$pageActionHtml = '<a href="index.php" class="btn btn-outline">Back to List</a>';
require_once 'includes/header.php';
?>

<?php if ($deleteError): ?>
    <div class="alert alert-error"><?= htmlspecialchars($deleteError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="delete-card">
    <div class="delete-warning">
        <h3>&#9888; Are you sure you want to delete this employee?</h3>
        <p>This action cannot be undone. All addresses and documents for this officer will also be permanently removed.</p>
    </div>

    <div class="detail-grid">
        <div class="detail-row">
            <span class="detail-label">Employee Code</span>
            <span class="detail-value"><?= htmlspecialchars($employee['employee_code'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Khmer Name</span>
            <span class="detail-value">
                <?= htmlspecialchars($employee['family_name_kh'] . ' ' . $employee['given_name_kh'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Latin Name</span>
            <span class="detail-value">
                <?= htmlspecialchars($employee['family_name_latin'] . ' ' . $employee['given_name_latin'], ENT_QUOTES, 'UTF-8') ?>
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
    </div>

    <div class="delete-actions">
        <form method="POST" action="delete.php?id=<?= (int)$id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <button type="submit" name="confirm_delete" class="btn btn-danger">Confirm Delete</button>
        </form>
        <a href="view.php?id=<?= (int)$id ?>" class="btn btn-outline">Cancel</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
