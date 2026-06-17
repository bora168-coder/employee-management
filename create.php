<?php
require_once 'includes/auth.php';
require_auth();
require_once 'db.php';
require_once 'includes/helpers.php';

$pageTitle = ui_text('add_employee');
$pageEyebrow = ui_text('portal') . ' / ' . ui_text('employees') . ' / ' . ui_text('add_employee');
$pageActionHtml = '<a href="' . h(lang_url('index.php')) . '" class="btn btn-outline">' . h(ui_text('back_to_list')) . '</a>';

$errors = [];
$old    = [];

$genderOptions = ['Male', 'Female', 'Other'];
$statusOptions = ['Active','Inactive','Retired','Suspended','Transferred','Deceased'];
$genderLabels = [
    'Male' => ui_text('gender_male'),
    'Female' => ui_text('gender_female'),
    'Other' => ui_text('gender_other'),
];
$statusLabels = [
    'Active' => ui_text('status_active'),
    'Inactive' => ui_text('status_inactive'),
    'Retired' => ui_text('status_retired'),
    'Suspended' => ui_text('status_suspended'),
    'Transferred' => ui_text('status_transferred'),
    'Deceased' => ui_text('status_deceased'),
];
$provinces     = [
    'Phnom Penh','Siem Reap','Battambang','Kampong Cham','Kampong Chhnang',
    'Kampong Speu','Kampong Thom','Kampot','Kandal','Kep','Koh Kong',
    'Kratié','Mondulkiri','Oddar Meanchey','Pailin','Preah Sihanouk',
    'Preah Vihear','Prey Veng','Pursat','Ratanakiri','Stung Treng',
    'Svay Rieng','Takéo','Tboung Khmum'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Collect and sanitize input
        $old = [
            'employee_code'        => trim($_POST['employee_code']        ?? ''),
            'officer_number'       => trim($_POST['officer_number']        ?? '') ?: null,
            'civil_servant_number' => trim($_POST['civil_servant_number']  ?? '') ?: null,
            'national_id_number'   => trim($_POST['national_id_number']    ?? '') ?: null,
            'family_name_kh'       => trim($_POST['family_name_kh']        ?? ''),
            'given_name_kh'        => trim($_POST['given_name_kh']         ?? ''),
            'family_name_latin'    => trim($_POST['family_name_latin']     ?? ''),
            'given_name_latin'     => trim($_POST['given_name_latin']      ?? ''),
            'gender'               => trim($_POST['gender']                ?? ''),
            'date_of_birth'        => trim($_POST['date_of_birth']         ?? ''),
            'nationality'          => trim($_POST['nationality']           ?? 'Cambodian'),
            'phone'                => trim($_POST['phone']                 ?? '') ?: null,
            'department'           => trim($_POST['department']            ?? ''),
            'position'             => trim($_POST['position']              ?? ''),
            'status'               => trim($_POST['status']                ?? 'Active'),
            // birthplace address
            'bp_village'    => trim($_POST['bp_village']   ?? ''),
            'bp_commune'    => trim($_POST['bp_commune']   ?? ''),
            'bp_district'   => trim($_POST['bp_district']  ?? ''),
            'bp_province'   => trim($_POST['bp_province']  ?? ''),
            // permanent address
            'pa_house'      => trim($_POST['pa_house']     ?? ''),
            'pa_street'     => trim($_POST['pa_street']    ?? ''),
            'pa_village'    => trim($_POST['pa_village']   ?? ''),
            'pa_commune'    => trim($_POST['pa_commune']   ?? ''),
            'pa_district'   => trim($_POST['pa_district']  ?? ''),
            'pa_province'   => trim($_POST['pa_province']  ?? ''),
            // document fields (for repopulating on error)
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

        // Required field validation
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

        // Date of birth validation
        if ($old['date_of_birth'] !== '') {
            $dobObj = DateTime::createFromFormat('Y-m-d', $old['date_of_birth']);
            if (!$dobObj || $dobObj->format('Y-m-d') !== $old['date_of_birth']) {
                $errors[] = 'Date of birth must be a valid date (YYYY-MM-DD).';
            } elseif ($dobObj > new DateTime()) {
                $errors[] = 'Date of birth cannot be in the future.';
            }
        }

        // Phone format
        if ($old['phone'] !== null && !preg_match('/^[0-9+\-\s()]{6,20}$/', $old['phone'])) {
            $errors[] = 'Phone number format is invalid.';
        }

        // Unique checks
        if ($old['employee_code'] !== '') {
            $s = $pdo->prepare('SELECT id FROM employees WHERE employee_code = ?');
            $s->execute([$old['employee_code']]);
            if ($s->fetch()) { $errors[] = 'Employee code already exists.'; }
        }
        if ($old['officer_number'] !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE officer_number = ?');
            $s->execute([$old['officer_number']]);
            if ($s->fetch()) { $errors[] = 'Officer number already exists.'; }
        }
        if ($old['civil_servant_number'] !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE civil_servant_number = ?');
            $s->execute([$old['civil_servant_number']]);
            if ($s->fetch()) { $errors[] = 'Civil servant number already exists.'; }
        }
        if ($old['national_id_number'] !== null) {
            $s = $pdo->prepare('SELECT id FROM employees WHERE national_id_number = ?');
            $s->execute([$old['national_id_number']]);
            if ($s->fetch()) { $errors[] = 'National ID number already exists.'; }
        }

        // Validate document dates: issue must be before expiry
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

        // Photo upload
        $photoPath = null;
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
                    $photoPath = $destPath;
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO employees
                     (employee_code, officer_number, civil_servant_number, national_id_number,
                      family_name_kh, given_name_kh, family_name_latin, given_name_latin,
                      gender, date_of_birth, nationality, phone, department, position, photo_path, status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $old['employee_code'], $old['officer_number'], $old['civil_servant_number'],
                    $old['national_id_number'], $old['family_name_kh'], $old['given_name_kh'],
                    $old['family_name_latin'], $old['given_name_latin'], $old['gender'],
                    $old['date_of_birth'], $old['nationality'], $old['phone'],
                    $old['department'], $old['position'], $photoPath, $old['status']
                ]);
                $empId = (int)$pdo->lastInsertId();

                // Insert birthplace address if any field filled
                if ($old['bp_village'] || $old['bp_commune'] || $old['bp_district'] || $old['bp_province']) {
                    $as = $pdo->prepare(
                        'INSERT INTO employee_addresses (employee_id, address_type, village, commune, district, province)
                         VALUES (?,?,?,?,?,?)'
                    );
                    $as->execute([
                        $empId, 'birthplace',
                        $old['bp_village'] ?: null, $old['bp_commune'] ?: null,
                        $old['bp_district'] ?: null, $old['bp_province'] ?: null
                    ]);
                }

                // Insert permanent address
                $ap = $pdo->prepare(
                    'INSERT INTO employee_addresses
                     (employee_id, address_type, house_number, street_number, village, commune, district, province)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $ap->execute([
                    $empId, 'permanent',
                    $old['pa_house'] ?: null, $old['pa_street'] ?: null,
                    $old['pa_village'] ?: null, $old['pa_commune'] ?: null,
                    $old['pa_district'], $old['pa_province']
                ]);

                // Insert ID document records
                $ds = $pdo->prepare(
                    'INSERT INTO employee_documents
                     (employee_id, document_type, document_number, issue_date, expiry_date, issuing_authority)
                     VALUES (?,?,?,?,?,?)'
                );
                foreach ($docTypes2 as $dt) {
                    $docNum  = $old[$dt['key'].'_number']      ?: null;
                    $issDate = $old[$dt['key'].'_issue_date']   ?: null;
                    $expDate = $old[$dt['key'].'_expiry_date']  ?: null;
                    $auth    = $old[$dt['key'].'_authority']    ?: null;
                    if ($docNum || $issDate || $expDate || $auth) {
                        $ds->execute([$empId, $dt['label'], $docNum, $issDate, $expDate, $auth]);
                    }
                }

                $pdo->commit();
                header('Location: ' . lang_url('index.php?success=' . urlencode('Employee created successfully.')));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                if ($photoPath && file_exists($photoPath)) { unlink($photoPath); }
                error_log('Employee create failed: ' . $e->getMessage());
                $errors[] = 'Failed to save employee. Please try again.';
            }
        }
    }
}

?>

<?php require_once 'includes/header.php'; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-card" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <div class="tabs">
        <button type="button" class="tab-btn active" data-tab="personal"><?= h(ui_text('personal')) ?></button>
        <button type="button" class="tab-btn" data-tab="address"><?= h(ui_text('address')) ?></button>
        <button type="button" class="tab-btn" data-tab="ids"><?= h(ui_text('id_documents')) ?></button>
    </div>

    <!-- TAB: Personal -->
    <div class="tab-content active" id="tab-personal">
        <div class="form-section-title"><?= h(ui_text('identity')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="employee_code"><?= h(ui_text('employee_code')) ?> <span class="required">*</span></label>
                <input type="text" id="employee_code" name="employee_code" maxlength="30" required
                       value="<?= htmlspecialchars($old['employee_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="officer_number"><?= h(ui_text('officer_number')) ?></label>
                <input type="text" id="officer_number" name="officer_number" maxlength="50"
                       value="<?= htmlspecialchars($old['officer_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="civil_servant_number"><?= h(ui_text('civil_servant_number')) ?></label>
                <input type="text" id="civil_servant_number" name="civil_servant_number" maxlength="50"
                       value="<?= htmlspecialchars($old['civil_servant_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="national_id_number"><?= h(ui_text('national_id_number')) ?></label>
                <input type="text" id="national_id_number" name="national_id_number" maxlength="50"
                       value="<?= htmlspecialchars($old['national_id_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('khmer_name')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="family_name_kh"><?= h(ui_text('family_name_khmer')) ?> <span class="required">*</span></label>
                <input type="text" id="family_name_kh" name="family_name_kh" maxlength="100" required
                       value="<?= htmlspecialchars($old['family_name_kh'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="given_name_kh"><?= h(ui_text('given_name_khmer')) ?> <span class="required">*</span></label>
                <input type="text" id="given_name_kh" name="given_name_kh" maxlength="100" required
                       value="<?= htmlspecialchars($old['given_name_kh'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('latin_name')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="family_name_latin"><?= h(ui_text('family_name_latin')) ?> <span class="required">*</span></label>
                <input type="text" id="family_name_latin" name="family_name_latin" maxlength="100" required
                       value="<?= htmlspecialchars($old['family_name_latin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="given_name_latin"><?= h(ui_text('given_name_latin')) ?> <span class="required">*</span></label>
                <input type="text" id="given_name_latin" name="given_name_latin" maxlength="100" required
                       value="<?= htmlspecialchars($old['given_name_latin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('personal_details')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="gender"><?= h(ui_text('gender')) ?> <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">- <?= h(ui_text('select_option')) ?> -</option>
                    <?php foreach ($genderOptions as $g): ?>
                        <option value="<?= h($g) ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= h($genderLabels[$g] ?? $g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_of_birth"><?= h(ui_text('date_of_birth')) ?> <span class="required">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" required
                       max="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($old['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="nationality"><?= h(ui_text('nationality')) ?></label>
                <input type="text" id="nationality" name="nationality" maxlength="100"
                       value="<?= htmlspecialchars($old['nationality'] ?? 'Cambodian', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="phone"><?= h(ui_text('phone')) ?></label>
                <input type="text" id="phone" name="phone" maxlength="30"
                       value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('employment')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="department"><?= h(ui_text('department')) ?> <span class="required">*</span></label>
                <input type="text" id="department" name="department" maxlength="100" required
                       value="<?= htmlspecialchars($old['department'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="position"><?= h(ui_text('position')) ?> <span class="required">*</span></label>
                <input type="text" id="position" name="position" maxlength="100" required
                       value="<?= htmlspecialchars($old['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="status"><?= h(ui_text('status')) ?></label>
                <select id="status" name="status">
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= h($s) ?>" <?= ($old['status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= h($statusLabels[$s] ?? $s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('profile_photo')) ?></div>
        <div class="form-group">
            <label for="photo"><?= h(ui_text('photo_requirements')) ?></label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
    </div>

    <!-- TAB: Address -->
    <div class="tab-content" id="tab-address">
        <div class="form-section-title"><?= h(ui_text('birthplace')) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label><?= h(ui_text('village')) ?></label>
                <input type="text" name="bp_village" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_village'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('commune_sangkat')) ?></label>
                <input type="text" name="bp_commune" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_commune'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('district_khan')) ?></label>
                <input type="text" name="bp_district" maxlength="100"
                       value="<?= htmlspecialchars($old['bp_district'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('province_capital')) ?></label>
                <select name="bp_province">
                    <option value="">- <?= h(ui_text('select_option')) ?> -</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p ?>" <?= ($old['bp_province'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section-title"><?= h(ui_text('permanent_address')) ?> <span class="required">*</span></div>
        <div class="form-grid">
            <div class="form-group">
                <label><?= h(ui_text('house_number')) ?></label>
                <input type="text" name="pa_house" maxlength="20"
                       value="<?= htmlspecialchars($old['pa_house'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('street_number')) ?></label>
                <input type="text" name="pa_street" maxlength="20"
                       value="<?= htmlspecialchars($old['pa_street'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('village')) ?></label>
                <input type="text" name="pa_village" maxlength="100"
                       value="<?= htmlspecialchars($old['pa_village'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('commune_sangkat')) ?></label>
                <input type="text" name="pa_commune" maxlength="100"
                       value="<?= htmlspecialchars($old['pa_commune'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('district_khan')) ?> <span class="required">*</span></label>
                <input type="text" name="pa_district" maxlength="100" required
                       value="<?= htmlspecialchars($old['pa_district'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('province_capital')) ?> <span class="required">*</span></label>
                <select name="pa_province" required>
                    <option value="">- <?= h(ui_text('select_option')) ?> -</option>
                    <?php foreach ($provinces as $p): ?>
                        <option value="<?= $p ?>" <?= ($old['pa_province'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- TAB: ID Documents -->
    <div class="tab-content" id="tab-ids">
        <p class="tab-note"><?= h(ui_text('id_document_note')) ?></p>
        <?php
        $docTypes = [
            ['label' => ui_text('government_officer_id_card'),   'key' => 'doc_officer'],
            ['label' => ui_text('khmer_national_identity_card'), 'key' => 'doc_national'],
            ['label' => ui_text('civil_servant_identity_card'),  'key' => 'doc_civil'],
        ];
        foreach ($docTypes as $dt): ?>
        <div class="form-section-title"><?= h($dt['label']) ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label><?= h(ui_text('document_number')) ?></label>
                <input type="text" name="<?= $dt['key'] ?>_number" maxlength="100"
                       value="<?= htmlspecialchars($old[$dt['key'].'_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('issue_date')) ?></label>
                <input type="date" name="<?= $dt['key'] ?>_issue_date"
                       value="<?= htmlspecialchars($old[$dt['key'].'_issue_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('expiry_date')) ?></label>
                <input type="date" name="<?= $dt['key'] ?>_expiry_date"
                       value="<?= htmlspecialchars($old[$dt['key'].'_expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label><?= h(ui_text('issuing_authority')) ?></label>
                <input type="text" name="<?= $dt['key'] ?>_authority" maxlength="200"
                       value="<?= htmlspecialchars($old[$dt['key'].'_authority'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= h(ui_text('save_employee')) ?></button>
        <a href="<?= h(lang_url('index.php')) ?>" class="btn btn-outline"><?= h(ui_text('cancel')) ?></a>
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
