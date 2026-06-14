<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'db.php';

$errors = [];
$values = [
    'employee_code' => '',
    'first_name'    => '',
    'last_name'     => '',
    'email'         => '',
    'phone'         => '',
    'department'    => '',
    'position'      => '',
    'salary'        => '',
    'hire_date'     => '',
    'status'        => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token. Please try again.';
    } else {
        $values['employee_code'] = trim($_POST['employee_code'] ?? '');
        $values['first_name']    = trim($_POST['first_name']    ?? '');
        $values['last_name']     = trim($_POST['last_name']     ?? '');
        $values['email']         = trim($_POST['email']         ?? '');
        $values['phone']         = trim($_POST['phone']         ?? '');
        $values['department']    = trim($_POST['department']    ?? '');
        $values['position']      = trim($_POST['position']      ?? '');
        $values['salary']        = trim($_POST['salary']        ?? '');
        $values['hire_date']     = trim($_POST['hire_date']     ?? '');
        $values['status']        = trim($_POST['status']        ?? '');

        if ($values['employee_code'] === '') {
            $errors['employee_code'] = 'Employee code is required.';
        } elseif (strlen($values['employee_code']) > 30) {
            $errors['employee_code'] = 'Employee code must not exceed 30 characters.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $values['employee_code'])) {
            $errors['employee_code'] = 'Employee code may only contain letters, numbers, hyphens, and underscores.';
        }

        if ($values['first_name'] === '') {
            $errors['first_name'] = 'First name is required.';
        } elseif (strlen($values['first_name']) > 100) {
            $errors['first_name'] = 'First name must not exceed 100 characters.';
        }

        if ($values['last_name'] === '') {
            $errors['last_name'] = 'Last name is required.';
        } elseif (strlen($values['last_name']) > 100) {
            $errors['last_name'] = 'Last name must not exceed 100 characters.';
        }

        if ($values['email'] === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email format is invalid.';
        } elseif (strlen($values['email']) > 150) {
            $errors['email'] = 'Email must not exceed 150 characters.';
        }

        if ($values['phone'] !== '' && !preg_match('/^[0-9\s\+\-\(\)]+$/', $values['phone'])) {
            $errors['phone'] = 'Phone number contains invalid characters.';
        }

        if ($values['department'] === '') {
            $errors['department'] = 'Department is required.';
        } elseif (strlen($values['department']) > 100) {
            $errors['department'] = 'Department must not exceed 100 characters.';
        }

        if ($values['position'] === '') {
            $errors['position'] = 'Position is required.';
        } elseif (strlen($values['position']) > 100) {
            $errors['position'] = 'Position must not exceed 100 characters.';
        }

        $salary = 0.00;
        if ($values['salary'] !== '') {
            if (!is_numeric($values['salary']) || (float)$values['salary'] < 0) {
                $errors['salary'] = 'Salary must be a valid non-negative number.';
            } else {
                $salary = (float)$values['salary'];
            }
        }

        if ($values['hire_date'] === '') {
            $errors['hire_date'] = 'Hire date is required.';
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $values['hire_date']);
            if (!$d || $d->format('Y-m-d') !== $values['hire_date']) {
                $errors['hire_date'] = 'Hire date must be a valid date (YYYY-MM-DD).';
            }
        }

        if (!in_array($values['status'], ['Active', 'Inactive'], true)) {
            $errors['status'] = 'Status must be Active or Inactive.';
        }

        if (!isset($errors['employee_code']) && $values['employee_code'] !== '') {
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_code = ? LIMIT 1");
            $stmt->execute([$values['employee_code']]);
            if ($stmt->fetch()) {
                $errors['employee_code'] = 'This employee code already exists.';
            }
        }

        if (!isset($errors['email']) && $values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? LIMIT 1");
            $stmt->execute([$values['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already in use.';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO employees
                        (employee_code, first_name, last_name, email, phone, department, position, salary, hire_date, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $values['employee_code'],
                    $values['first_name'],
                    $values['last_name'],
                    $values['email'],
                    $values['phone'] !== '' ? $values['phone'] : null,
                    $values['department'],
                    $values['position'],
                    $salary,
                    $values['hire_date'],
                    $values['status'],
                ]);
                header('Location: index.php?success=created');
                exit;
            } catch (PDOException $e) {
                error_log('Failed to insert employee: ' . $e->getMessage());
                $errors[] = 'Unable to save employee. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Add Employee</h2>
    <a href="index.php" class="btn btn-outline">&larr; Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="create.php" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-row">
        <div class="form-group">
            <label for="employee_code">Employee Code <span class="required">*</span></label>
            <input type="text" id="employee_code" name="employee_code" maxlength="30" required
                   placeholder="e.g. EMP-001"
                   class="<?= isset($errors['employee_code']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['employee_code'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['employee_code'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['employee_code'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="status">Status <span class="required">*</span></label>
            <select id="status" name="status" required
                    class="<?= isset($errors['status']) ? 'input-error' : '' ?>">
                <option value="Active"   <?= $values['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $values['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <?php if (isset($errors['status'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="first_name">First Name <span class="required">*</span></label>
            <input type="text" id="first_name" name="first_name" maxlength="100" required
                   class="<?= isset($errors['first_name']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['first_name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['first_name'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name <span class="required">*</span></label>
            <input type="text" id="last_name" name="last_name" maxlength="100" required
                   class="<?= isset($errors['last_name']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['last_name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['last_name'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" maxlength="150" required
                   class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['email'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" maxlength="30"
                   placeholder="e.g. +855-12-345-678"
                   class="<?= isset($errors['phone']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['phone'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['phone'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="department">Department <span class="required">*</span></label>
            <input type="text" id="department" name="department" maxlength="100" required
                   class="<?= isset($errors['department']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['department'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['department'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['department'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="position">Position <span class="required">*</span></label>
            <input type="text" id="position" name="position" maxlength="100" required
                   class="<?= isset($errors['position']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['position'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['position'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['position'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="salary">Salary</label>
            <input type="number" id="salary" name="salary" min="0" step="0.01"
                   placeholder="0.00"
                   class="<?= isset($errors['salary']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['salary'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['salary'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['salary'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="hire_date">Hire Date <span class="required">*</span></label>
            <input type="date" id="hire_date" name="hire_date" required
                   class="<?= isset($errors['hire_date']) ? 'input-error' : '' ?>"
                   value="<?= htmlspecialchars($values['hire_date'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['hire_date'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['hire_date'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Employee</button>
        <a href="index.php" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
