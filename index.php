<?php
require_once 'db.php';
require_once 'includes/header.php';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$params = [];
$where  = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(e.employee_code LIKE ? OR e.family_name_kh LIKE ? OR e.given_name_kh LIKE ?
                 OR e.family_name_latin LIKE ? OR e.given_name_latin LIKE ?
                 OR e.national_id_number LIKE ? OR e.phone LIKE ?
                 OR e.department LIKE ? OR e.position LIKE ?)';
    for ($i = 0; $i < 9; $i++) { $params[] = $like; }
}

if ($statusFilter !== '') {
    $where[] = 'e.status = ?';
    $params[] = $statusFilter;
}

$sql = 'SELECT e.id, e.employee_code, e.family_name_kh, e.given_name_kh,
               e.family_name_latin, e.given_name_latin, e.gender,
               e.department, e.position, e.phone, e.status, e.photo_path
        FROM employees e';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY e.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Store raw values; escape only at the point of output.
$successMsg = $_GET['success'] ?? '';
$errorMsg   = $_GET['error']   ?? '';

$statusOptions = ['Active','Inactive','Retired','Suspended','Transferred','Deceased'];

/**
 * Return a safe relative URL for a stored photo path.
 * The path must start with uploads/photos/ and must not contain traversal
 * sequences. Returns null when the path is invalid or the file is missing.
 */
function safePhotoSrc(?string $path): ?string {
    if ($path === null || $path === '') { return null; }
    // Normalise to forward slashes and remove any leading ./
    $norm = str_replace('\\', '/', ltrim($path, './'));
    // Must be rooted inside uploads/photos/ with no traversal
    if (strpos($norm, '..') !== false || strpos($norm, 'uploads/photos/') !== 0) {
        return null;
    }
    if (!file_exists($path)) { return null; }
    return $path;
}
?>

<div class="page-header">
    <h2>Employee List</h2>
    <a href="create.php" class="btn btn-primary">+ Add Employee</a>
</div>

<?php if ($successMsg !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($errorMsg !== ''): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search by code, name, ID, phone, department…"
           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="form-control">
    <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $statusFilter === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="index.php" class="btn btn-outline">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($employees)): ?>
    <div class="empty-state">
        <p>No employees found.</p>
        <a href="create.php" class="btn btn-primary">Add First Employee</a>
    </div>
<?php else: ?>
<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>Photo</th>
            <th>Code</th>
            <th>Khmer Name</th>
            <th>Latin Name</th>
            <th>Gender</th>
            <th>Department</th>
            <th>Position</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($employees as $emp): ?>
        <?php $photoSrc = safePhotoSrc($emp['photo_path']); ?>
        <tr>
            <td class="td-photo">
                <?php if ($photoSrc !== null): ?>
                    <img src="<?= htmlspecialchars($photoSrc, ENT_QUOTES, 'UTF-8') ?>"
                         alt="Photo" class="thumb">
                <?php else: ?>
                    <div class="thumb-placeholder">?</div>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($emp['employee_code'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['family_name_kh'] . ' ' . $emp['given_name_kh'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['family_name_latin'] . ' ' . $emp['given_name_latin'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['gender'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['department'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['position'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($emp['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge badge-<?= htmlspecialchars(strtolower($emp['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($emp['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="td-actions">
                <a href="view.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline">View</a>
                <a href="edit.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                <a href="delete.php?id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
