<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';
require_once 'includes/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid employee ID.'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    header('Location: index.php?error=' . urlencode('Employee not found.'));
    exit;
}

// Load addresses
$addrStmt = $pdo->prepare('SELECT * FROM employee_addresses WHERE employee_id = ?');
$addrStmt->execute([$id]);
$addresses = [];
foreach ($addrStmt->fetchAll() as $addr) {
    $addresses[$addr['address_type']] = $addr;
}
$bp = $addresses['birthplace'] ?? null;
$pa = $addresses['permanent']  ?? null;

// Load documents
$docStmt = $pdo->prepare('SELECT * FROM employee_documents WHERE employee_id = ? ORDER BY id');
$docStmt->execute([$id]);
$documents = $docStmt->fetchAll();

// Store raw; escape only at output.
$successMsg = $_GET['success'] ?? '';

$pageTitle = $emp['family_name_latin'] . ' ' . $emp['given_name_latin'];
$pageEyebrow = 'Portal / Employees / Profile';
$pageActionHtml = '<a href="index.php" class="btn btn-outline">Back to List</a><a href="edit.php?id=' . (int) $id . '" class="btn btn-secondary">Edit</a><a href="delete.php?id=' . (int) $id . '" class="btn btn-danger">Delete</a>';

function val(string $v): string {
    return $v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : '<em class="text-muted">—</em>';
}

require_once 'includes/header.php';
?>

<?php if ($successMsg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="detail-card" style="max-width:900px;">

    <!-- Profile header -->
    <div class="profile-header">
        <div>
            <?php
            // Validate photo path is within uploads/photos/ before rendering.
            $photoSrc = null;
            if (!empty($emp['photo_path'])) {
                $norm = str_replace('\\', '/', ltrim($emp['photo_path'], './'));
                $photoSrc = safe_photo_src($emp['photo_path']);
            }
            ?>
            <?php if ($photoSrc !== null): ?>
                <img src="<?= htmlspecialchars($photoSrc, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Profile Photo" class="emp-photo-large">
            <?php else: ?>
                <div class="emp-photo-placeholder">?</div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h3><?= htmlspecialchars($emp['family_name_kh'] . ' ' . $emp['given_name_kh'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="sub"><?= htmlspecialchars($emp['family_name_latin'] . ' ' . $emp['given_name_latin'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="sub"><?= htmlspecialchars($emp['position'], ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars($emp['department'], ENT_QUOTES, 'UTF-8') ?></p>
            <p style="margin-top:8px;">
                <span class="badge badge-<?= htmlspecialchars(strtolower($emp['status']), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($emp['status'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </p>
        </div>
    </div>

    <!-- Identity -->
    <div class="profile-section">
        <h4>Identity Numbers</h4>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Employee Code</span><span class="detail-value"><?= val($emp['employee_code']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Officer Number</span><span class="detail-value"><?= val($emp['officer_number'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Civil Servant Number</span><span class="detail-value"><?= val($emp['civil_servant_number'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">National ID Number</span><span class="detail-value"><?= val($emp['national_id_number'] ?? '') ?></span></div>
        </div>
    </div>

    <!-- Personal -->
    <div class="profile-section">
        <h4>Personal Information</h4>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Gender</span><span class="detail-value"><?= val($emp['gender']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Date of Birth</span><span class="detail-value"><?= val($emp['date_of_birth']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Nationality</span><span class="detail-value"><?= val($emp['nationality'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Phone</span><span class="detail-value"><?= val($emp['phone'] ?? '') ?></span></div>
        </div>
    </div>

    <!-- Employment -->
    <div class="profile-section">
        <h4>Employment</h4>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Department</span><span class="detail-value"><?= val($emp['department']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Position</span><span class="detail-value"><?= val($emp['position']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Status</span><span class="detail-value"><span class="badge badge-<?= htmlspecialchars(strtolower($emp['status']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($emp['status'], ENT_QUOTES, 'UTF-8') ?></span></span></div>
            <div class="detail-item"><span class="detail-label">Created</span><span class="detail-value"><?= val($emp['created_at']) ?></span></div>
            <div class="detail-item"><span class="detail-label">Last Updated</span><span class="detail-value"><?= val($emp['updated_at']) ?></span></div>
        </div>
    </div>

    <!-- Birthplace -->
    <?php if ($bp): ?>
    <div class="profile-section">
        <h4>Birthplace</h4>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">Village</span><span class="detail-value"><?= val($bp['village'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Commune / Sangkat</span><span class="detail-value"><?= val($bp['commune'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">District / Khan</span><span class="detail-value"><?= val($bp['district'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Province / Capital</span><span class="detail-value"><?= val($bp['province'] ?? '') ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Permanent Address -->
    <?php if ($pa): ?>
    <div class="profile-section">
        <h4>Permanent Address</h4>
        <div class="detail-grid">
            <div class="detail-item"><span class="detail-label">House Number</span><span class="detail-value"><?= val($pa['house_number'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Street Number</span><span class="detail-value"><?= val($pa['street_number'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Village</span><span class="detail-value"><?= val($pa['village'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Commune / Sangkat</span><span class="detail-value"><?= val($pa['commune'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">District / Khan</span><span class="detail-value"><?= val($pa['district'] ?? '') ?></span></div>
            <div class="detail-item"><span class="detail-label">Province / Capital</span><span class="detail-value"><?= val($pa['province'] ?? '') ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ID Documents -->
    <?php if ($documents): ?>
    <div class="profile-section">
        <h4>ID Documents</h4>
        <?php foreach ($documents as $doc): ?>
        <div style="margin-bottom:16px;padding:12px;background:#f8f9fa;border-radius:4px;border-left:4px solid #2c3e50;">
            <strong><?= htmlspecialchars($doc['document_type'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="detail-grid" style="margin-top:8px;">
                <div class="detail-item"><span class="detail-label">Number</span><span class="detail-value"><?= val($doc['document_number'] ?? '') ?></span></div>
                <div class="detail-item"><span class="detail-label">Issue Date</span><span class="detail-value"><?= val($doc['issue_date'] ?? '') ?></span></div>
                <div class="detail-item"><span class="detail-label">Expiry Date</span><span class="detail-value"><?= val($doc['expiry_date'] ?? '') ?></span></div>
                <div class="detail-item"><span class="detail-label">Issuing Authority</span><span class="detail-value"><?= val($doc['issuing_authority'] ?? '') ?></span></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
