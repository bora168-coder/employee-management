<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
require_once 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid employee ID.'));
    exit;
}

// Load employee
$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$employee = $stmt->fetch();
if (!$employee) {
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

// Load documents
$docStmt = $pdo->prepare('SELECT * FROM employee_documents WHERE employee_id = ? ORDER BY id');
$docStmt->execute([$id]);
$documents = [];
$docLabelToKey = [
    'Government Officer ID Card'   => 'doc_officer',
    'Khmer National Identity Card' => 'doc_national',
    'Civil Servant Identity Card'  => 'doc_civil',
];
foreach ($docStmt->fetchAll() as $doc) {
    $key = $docLabelToKey[$doc['document_type']] ?? null;
    if ($key) { $documents[$key] = $doc; }
}

$bp = $addresses['birthplace'] ?? [];
$pa = $addresses['permanent']  ?? [];

$errors = [];
$genderOptions = ['Male', 'Female', 'Other'];
$statusOptions = ['Active','Inactive','Retired','Suspended','Transferred','Deceased'];
$provinces     = [
    'Phnom Penh','Siem Reap','Battambang','Kampong Cham','Kampong Chhnang',
    'Kampong Speu','Kampong Thom','Kampot','Kandal','Kep','Koh Kong',
    'Kratié','Mondulkiri','Oddar Meanchey','Pailin','Preah Sihanouk',
    'Preah Vihear','Prey Veng','Pursat','Ratanakiri','Stung Treng',
    'Svay Rieng','Takéo','Tboung Khmum'
];

// Pre-fill old values from DB
$old = [
    'employee_code'        => $employee['employee_code'],
    'officer_number'       => $employee['officer_number'] ?? '',
    'civil_servant_number' => $employee['civil_servant_number'] ?? '',
    'national_id_number'   => $employee['national_id_number'] ?? '',
    'family_name_kh'       => $employee['family_name_kh'],
    'given_name_kh'        => $employee['given_name_kh'],
    'family_name_latin'    => $employee['family_name_latin'],
    'given_name_latin'     => $employee['given_name_latin'],
    'gender'               => $employee['gender'],
    'date_of_birth'        => $employee['date_of_birth'],
    'nationality'          => $employee['nationality'] ?? 'Cambodian',
    'phone'                => $employee['phone'] ?? '',
    'department'           => $employee['department'],
    'position'             => $employee['position'],
    'status'               => $employee['status'],
    'bp_village'    => $bp['village']       ?? '',
    'bp_commune'    => $bp['commune']       ?? '',
    'bp_district'   => $bp['district']      ?? '',
    'bp_province'   => $bp['province']      ?? '',
    'pa_house'      => $pa['house_number']  ?? '',
    'pa_street'     => $pa['street_number'] ?? '',
    'pa_village'    => $pa['village']       ?? '',
    'pa_commune'    => $pa['commune']       ?? '',
    'pa_district'   => $pa['district']      ?? '',
    'pa_province'   => $pa['province']      ?? '',
    'doc_officer_number'       => $documents['doc_officer']['document_number']   ?? '',
    'doc_officer_issue_date'   => $documents['doc_officer']['issue_date']        ?? '',
    'doc_officer_expiry_date'  => $documents['doc_officer']['expiry_date']       ?? '',
    'doc_officer_authority'    => $documents['doc_officer']['issuing_authority'] ?? '',
    'doc_national_number'      => $documents['doc_national']['document_number']  ?? '',
    'doc_national_issue_date'  => $documents['doc_national']['issue_date']       ?? '',
    'doc_national_expiry_date' => $documents['doc_national']['expiry_date']      ?? '',
    'doc_national_authority'   => $documents['doc_national']['issuing_authority']?? '',
    'doc_civil_number'         => $documents['doc_civil']['document_number']     ?? '',
    'doc_civil_issue_date'     => $documents['doc_civil']['issue_date']          ?? '',
    'doc_civil_expiry_date'    => $documents['doc_civil']['expiry_date']         ?? '',
    'doc_civil_authority'      => $documents['doc_civil']['issuing_authority']   ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $old = [
            'employee_code'        => trim($_POST['employee_code']        ?? ''),
            'officer_number'       => trim($_POST['officer_number']        ?? ''),
            'civil_servant_number' => trim($_POST['civil_servant_number']  ?? ''),
            'national_id_number'   => trim($_POST['national_id_number']    ?? ''),
            'family_name_kh'       => trim($_POST['family_name_kh']        ?? ''),
            'given_name_kh'        => trim($_POST['given_name_kh']         ?? ''),
            'family_name_latin'    => trim($_POST['family_name_latin']     ?? ''),
            'given_name_latin'     => trim($_POST['given_name_latin']      ?? ''),
            'gender'               => trim($_POST['gender']                ?? ''),
            'date_of_birth'        => trim($_POST['date_of_birth']         ?? ''),
            'nationality'          => trim($_POST['nationality']           ?? 'Cambodian'),
            'phone'                => trim($_POST['phone']                 ?? ''),
            'department'           => trim($_POST['department']            ?? ''),
            'position'             => trim($_POST['position']              ?? ''),
            'status'               => trim($_POST['status']                ?? 'Active'),
            'bp_village'    => trim($_POST['bp_village']   ?? ''),
            'bp_commune'    => trim($_POST['bp_commune']   ?? ''),
            'bp_district'   => trim($_POST['bp_district']  ?? ''),
            'bp_province'   => trim($_POST['bp_province']  ?? ''),
            'pa_house'      => trim($_POST['pa_house']     ?? ''),
            'pa_street'     => trim($_POST['pa_street']    ?? ''),
            'pa_village'    => trim($_POST['pa_village']   ?? ''),
            'pa_commune'    => trim($_POST['pa_commune']   ?? ''),
            'pa_district'   => trim($_POST['pa_district']  ?? ''),
            'pa_province'   => trim($_POST['pa_province']  ?? ''),
            'doc_officer_number'       => trim($_POST['doc_officer_number']      ?? ''),
            'doc_officer_issue_date'   => trim($_POST['doc_officer_issue_date']  ?? ''),
            'doc_officer_expiry_date'  => trim($_POST['doc_officer_expiry_date'] ?? ''),
            'doc_officer_authority'    => trim($_POST['doc_officer_authority']   ?? ''),
            'doc_national_number'      => trim($_POST['doc_national_number']     ?? ''),
            'doc_national_issue_date'  => trim($_POST['doc_national_issue_date'] ?? ''),
            'doc_national_expiry_date' => trim($_POST['doc_national_expiry_date']?? ''),
            'doc_national_authority'   => trim($_POST['doc_national_authority']  ?? ''),
            'doc_civil_number'         => trim($_POST['doc_civil_number']        ?? ''),
            'doc_civil_issue_date'     => trim($_POST['doc_civil_issue_date']    ?? ''),
            'doc_civil_expiry_date'    => trim($_POST['doc_civil_expiry_date']   ?? ''),
            'doc_civil_authority'      => trim($_POST['doc_civil_authority']     ?? ''),
        ];

        // Validation
        if ($old['employee_code'] === '')     { $errors[] = 'Employee code is required.'; }
        if (strlen($old['employee_code']) > 30) { $errors[] = 'Employee code must be 30 characters or less.'; }
        if ($old['family_name_kh'] === '')    { $errors[] = 'Khmer family name is required.'; }
        if ($old['given_name_kh'] === '')     { $errors[] = 'Khmer given name is required.'; }
        if ($old['family_name_latin'] === '') { $errors[] = 'Latin family name is required.'; }
        if ($old['given_name_latin'] === '')  { $errors[] = 'Latin given name is required.'; }
        if (!in_array($old['gender'], $genderOptions, true)) { $errors[] = 'Gender is required.'; }
        if ($old['date_of_birth'] === '')     { $errors[] = 'Date of birth is required.'; }
        if ($old['department'] === '')        { $errors[] = 'Department is required.'; }
        if ($old['position'] === '')          { $errors[] = 'Position is required.'; }
        if (!in_array($old['status'], $statusOptions, true)) { $errors[] = 'Invalid status.'; }
        if ($old['pa_district'] === '')       { $errors[] = 'Permanent address district is required.'; }
        if ($old['pa_province'] === '')       { $errors[] = 'Permanent address province is required.'; }

        if ($old['date_of_birth'] !== '') {
            $dobObj = DateTime::createFromFormat('Y-m-d', $old['date_of_birth']);
            if (!$dobObj || $dobObj->format('Y-m-d') !== $old['date_of_birth']) {
                $errors[] = 'Date of birth must be a valid date (YYYY-MM-DD).';
            } elseif ($dobObj > new DateTime()) {
                $errors[] = 'Date of birth cannot be in the future.';
            }
        }

        $phoneVal = $old['phone'] ?: null;
        if ($phoneVal !== null && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phoneVal)) {
            $errors[] = 'Phone number format is invalid.';
        }

        // Unique checks — exclude current employee
        if ($old['employee_code'] !== '') {
            $s = $pdo->prepare('SELECT id FROM employees WHERE employee_code = ? AND id != ?');
            $s->execute([$old['employee_code'], $id]);
            if ($s->fetch()) { $errors[] = 'Employee code already exists.'; }
        }
        $officerNum = $old['officer_number'] ?: null;
        if ($officerNum !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE officer_number = ? AND id != ?');
            $s->execute([$officerNum, $id]);
            if ($s->fetch()) { $errors[] = 'Officer number already exists.'; }
        }
        $civilNum = $old['civil_servant_number'] ?: null;
        if ($civilNum !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE civil_servant_number = ? AND id != ?');
            $s->execute([$civilNum, $id]);
            if ($s->fetch()) { $errors[] = 'Civil servant number already exists.'; }
        }
        $natIdNum = $old['national_id_number'] ?: null;
        if ($natIdNum !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE national_id_number = ? AND id != ?');
            $s->execute([$natIdNum, $id]);
            if ($s->fetch()) { $errors[] = 'National ID number already exists.'; }
        }

        // Document date validation
        $docTypes2 = [
            ['label' => 'Government Officer ID Card',   'key' => 'doc_officer'],
            ['label' => 'Khmer National Identity Card', 'key' => 'doc_national'],
            ['label' => 'Civil Servant Identity Card',  'key' => 'doc_civil'],
        ];
        foreach ($docTypes2 as $dt) {
            $issDate = $old[$dt['key'].'_issue_date']  ?: null;
            $expDate = $old[$dt['key'].'_expiry_date'] ?: null;
            if ($issDate && $expDate && $issDate > $expDate) {
                $errors[] = $dt['label'] . ': Issue date must be before expiry date.';
            }
        }

        // Photo upload (optional replacement)
        $newPhotoPath = null;
        $removeOldPhoto = false;
        if (!empty($_FILES['photo']['name'])) {
            $allowed     = ['image/jpeg','image/png','image/gif','image/webp'];
            $allowedExts = ['jpg','jpeg','png','gif','webp'];
            $finfo       = new finfo(FILEINFO_MIME_TYPE);
            $mime        = $finfo->file($_FILES['photo']['tmp_name']);
            $ext         = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Photo upload failed. Error code: ' . $_FILES['photo']['error'];
            } elseif (!in_array($mime, $allowed, true) || !in_array($ext, $allowedExts, true)) {
                $errors[] = 'Photo must be JPG, PNG, GIF, or WEBP.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo must be 2 MB or smaller.';
            } else {
                $uploadDir = 'uploads/photos/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $fileName  = bin2hex(random_bytes(16)) . '.' . $ext;
                $destPath  = $uploadDir . $fileName;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                    $errors[] = 'Failed to save photo. Check server permissions.';
                } else {
                    $newPhotoPath    = $destPath;
                    $removeOldPhoto  = true;
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $photoToSave = $newPhotoPath ?? $employee['photo_path'];

                // Update employees
                $upd = $pdo->prepare(
                    'UPDATE employees SET
                        employee_code = ?, officer_number = ?, civil_servant_number = ?,
                        national_id_number = ?, family_name_kh = ?, given_name_kh = ?,
                        family_name_latin = ?, given_name_latin = ?, gender = ?,
                        date_of_birth = ?, nationality = ?, phone = ?,
                        department = ?, position = ?, photo_path = ?, status = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $old['employee_code'], $officerNum, $civilNum, $natIdNum,
                    $old['family_name_kh'], $old['given_name_kh'],
                    $old['family_name_latin'], $old['given_name_latin'],
                    $old['gender'], $old['date_of_birth'], $old['nationality'],
                    $phoneVal, $old['department'], $old['position'],
                    $photoToSave, $old['status'], $id
                ]);

                // Upsert birthplace address
                if ($old['bp_village'] || $old['bp_commune'] || $old['bp_district'] || $old['bp_province']) {
                    $upsertBp = $pdo->prepare(
                        'INSERT INTO employee_addresses (employee_id, address_type, village, commune, district, province)
                         VALUES (?,?,?,?,?,?)
                         ON DUPLICATE KEY UPDATE
                             village = VALUES(village), commune = VALUES(commune),
                             district = VALUES(district), province = VALUES(province)'
                    );
                    $upsertBp->execute([
                        $id, 'birthplace',
                        $old['bp_village'] ?: null, $old['bp_commune'] ?: null,
                        $old['bp_district'] ?: null, $old['bp_province'] ?: null
                    ]);
                }

                // Upsert permanent address
                $upsertPa = $pdo->prepare(
                    'INSERT INTO employee_addresses
                     (employee_id, address_type, house_number, street_number, village, commune, district, province)
                     VALUES (?,?,?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                         house_number = VALUES(house_number), street_number = VALUES(street_number),
                         village = VALUES(village), commune = VALUES(commune),
                         district = VALUES(district), province = VALUES(province)'
                );
                $upsertPa->execute([
                    $id, 'permanent',
                    $old['pa_house'] ?: null, $old['pa_street'] ?: null,
                    $old['pa_village'] ?: null, $old['pa_commune'] ?: null,
                    $old['pa_district'], $old['pa_province']
                ]);

                // Update / insert documents
                foreach ($docTypes2 as $dt) {
                    $docNum  = $old[$dt['key'].'_number']      ?: null;
                    $issDate = $old[$dt['key'].'_issue_date']   ?: null;
                    $expDate = $old[$dt['key'].'_expiry_date']  ?: null;
                    $auth    = $old[$dt['key'].'_authority']    ?: null;

                    if (isset($documents[$dt['key']])) {
                        // Update existing
                        $updDoc = $pdo->prepare(
                            'UPDATE employee_documents
                             SET document_number = ?, issue_date = ?, expiry_date = ?, issuing_authority = ?
                             WHERE id = ?'
                        );
                        $updDoc->execute([
                            $docNum, $issDate, $expDate, $auth,
                            $documents[$dt['key']]['id']
                        ]);
                    } elseif ($docNum || $issDate || $expDate || $auth) {
                        // Insert new
                        $insDoc = $pdo->prepare(
                            'INSERT INTO employee_documents
                             (employee_id, document_type, document_number, issue_date, expiry_date, issuing_authority)
                             VALUES (?,?,?,?,?,?)'
                        );
                        $insDoc->execute([$id, $dt['label'], $docNum, $issDate, $expDate, $auth]);
                    }
                }

                $pdo->commit();

                // Remove old photo after successful commit
                if ($removeOldPhoto && $employee['photo_path'] && file_exists($employee['photo_path'])) {
                    @unlink($employee['photo_path']);
                }

                header('Location: view.php?id=' . $id . '&success=' . urlencode('Employee updated successfully.'));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($newPhotoPath && file_exists($newPhotoPath)) { @unlink($newPhotoPath); }
                error_log('Employee update failed: ' . $e->getMessage());
                $errors[] = 'Failed to update employee. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Employee';
$pageEyebrow = 'Portal / Employees / Edit';
$pageActionHtml = '<a href="view.php?id=' . (int) $id . '" class="btn btn-outline">Back to Profile</a><a href="index.php" class="btn btn-outline">Employee List</a>';
require_once 'includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-card" novalidate
      action="edit.php?id=<?= $id ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <div class="tabs">
        <button type="button" class="tab-btn active" data-tab="personal">Personal</button>
        <button type="button" class="tab-btn" data-tab="address">Address</button>
        <button type="button" class="tab-btn" data-tab="ids">ID Documents</button>
    </div>

    <!-- TAB: Personal -->
    <div class="tab-content active" id="tab-personal">
        <div class="form-section-title">Identity</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="employee_code">Employee Code <span class="required">*</span></label>
                <input type="text" id="employee_code" name="employee_code" maxlength="30" required
                       value="<?= htmlspecialchars($old['employee_code'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="officer_number">Officer Number</label>
                <input type="text" id="officer_number" name="officer_number" maxlength="50"
                       value="<?= htmlspecialchars($old['officer_number'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="civil_servant_number">Civil Servant Number</label>
                <input type="text" id="civil_servant_number" name="civil_servant_number" maxlength="50"
                       value="<?= htmlspecialchars($old['civil_servant_number'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="national_id_number">National ID Number</label>
                <input type="text" id="national_id_number" name="national_id_number" maxlength="50"
                       value="<?= htmlspecialchars($old['national_id_number'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title">Khmer Name</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="family_name_kh">Family Name (Khmer) <span class="required">*</span></label>
                <input type="text" id="family_name_kh" name="family_name_kh" maxlength="100" required
                       value="<?= htmlspecialchars($old['family_name_kh'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="given_name_kh">Given Name (Khmer) <span class="required">*</span></label>
                <input type="text" id="given_name_kh" name="given_name_kh" maxlength="100" required
                       value="<?= htmlspecialchars($old['given_name_kh'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title">Latin Name</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="family_name_latin">Family Name (Latin) <span class="required">*</span></label>
                <input type="text" id="family_name_latin" name="family_name_latin" maxlength="100" required
                       value="<?= htmlspecialchars($old['family_name_latin'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="given_name_latin">Given Name (Latin) <span class="required">*</span></label>
                <input type="text" id="given_name_latin" name="given_name_latin" maxlength="100" required
                       value="<?= htmlspecialchars($old['given_name_latin'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title">Personal Details</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="gender">Gender <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">— Select —</option>
                    <?php foreach ($genderOptions as $g): ?>
                        <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" required
                       max="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($old['date_of_birth'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" maxlength="100"
                       value="<?= htmlspecialchars($old['nationality'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" maxlength="30"
                       value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title">Employment</div>
        <div class="form-grid">
            <div class="form-group">
                <label for="department">Department <span class="required">*</span></label>
                <input type="text" id="department" name="department" maxlength="100" required
                       value="<?= htmlspecialchars($old['department'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="position">Position <span class="required">*</span></label>
                <input type="text" id="position" name="position" maxlength="100" required
                       value="<?= htmlspecialchars($old['position'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section-title">Profile Photo</div>
        <div class="form-group">
            <?php
            $currentPhotoSrc = null;
            if (!empty($employee['photo_path'])) {
                $pNorm = str_replace('\\', '/', ltrim($employee['photo_path'], './'));
                if (strpos($pNorm, '..') === false && strpos($pNorm, 'uploads/photos/') === 0 && file_exists($employee['photo_path'])) {
                    $currentPhotoSrc = $employee['photo_path'];
                }
            }
            ?>
            <?php if ($currentPhotoSrc !== null): ?>
                <p style="margin-bottom:8px;">
                    <img src="<?= htmlspecialchars($currentPhotoSrc, ENT_QUOTES, 'UTF-8') ?>"
                         alt="Current Photo" class="emp-photo-large">
                </p>
                <p style="font-size:0.85rem;color:#666;">Upload a new photo to replace the current one.</p>
            <?php endif; ?>
            <label for="photo">Photo (JPG, PNG, GIF, WEBP — max 2 MB)</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
    </div>

    <!-- TAB: Address -->
    <div class="tab-content" id="tab-address">
        <div class="form-section-title">Birthplace</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Village</label>
                <input type="text" name="bp_village" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_village'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Commune / Sangkat</label>
                <input type="text" name="bp_commune" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_commune'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>District / Khan</label>
                <input type="text" name="bp_district" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_district'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Province / Capital</label>
                <select name="bp_province">
                    <option value="">— Select —</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p ?>" <?= $old['bp_province'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section-title">Permanent Address <span class="required">*</span></div>
        <div class="form-grid">
            <div class="form-group">
                <label>House Number</label>
                <input type="text" name="pa_house" maxlength="20"
                       value="<?= htmlspecialchars($old['pa_house'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Street Number</label>
                <input type="text" name="pa_street" maxlength="20"
                       value="<?= htmlspecialchars($old['pa_street'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Village</label>
                <input type="text" name="pa_village" maxlength="100"
                       value="<?= htmlspecialchars($old['pa_village'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Commune / Sangkat</label>
                <input type="text" name="pa_commune" maxlength="100"
                       value="<?= htmlspecialchars($old['pa_commune'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>District / Khan <span class="required">*</span></label>
                <input type="text" name="pa_district" maxlength="100" required
                       value="<?= htmlspecialchars($old['pa_district'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Province / Capital <span class="required">*</span></label>
                <select name="pa_province" required>
                    <option value="">— Select —</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p ?>" <?= $old['pa_province'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- TAB: ID Documents -->
    <div class="tab-content" id="tab-ids">
        <p class="tab-note">Update identification document details. Leave blank if not applicable.</p>
        <?php
        $docTypes = [
            ['label' => 'Government Officer ID Card',   'key' => 'doc_officer'],
            ['label' => 'Khmer National Identity Card', 'key' => 'doc_national'],
            ['label' => 'Civil Servant Identity Card',  'key' => 'doc_civil'],
        ];
        foreach ($docTypes as $dt): ?>
        <div class="form-section-title"><?= htmlspecialchars($dt['label'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label>Document Number</label>
                <input type="text" name="<?= $dt['key'] ?>_number" maxlength="100"
                       value="<?= htmlspecialchars($old[$dt['key'].'_number'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Issue Date</label>
                <input type="date" name="<?= $dt['key'] ?>_issue_date"
                       value="<?= htmlspecialchars($old[$dt['key'].'_issue_date'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="<?= $dt['key'] ?>_expiry_date"
                       value="<?= htmlspecialchars($old[$dt['key'].'_expiry_date'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label>Issuing Authority</label>
                <input type="text" name="<?= $dt['key'] ?>_authority" maxlength="200"
                       value="<?= htmlspecialchars($old[$dt['key'].'_authority'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Update Employee</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
