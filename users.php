<?php
require_once 'includes/auth.php';
require_auth();

// Security: only Administrators may create, edit, or deactivate user accounts.
$_actor = current_user();
if ($_actor['role'] !== 'Administrator') {
    http_response_code(403);
    die('Access denied. Administrator role required.');
}

require_once 'db.php';

$pageTitle   = 'User Management';
$pageEyebrow = 'Portal / Administration';

$formErrors   = [];
$formSuccess  = '';
$allowedRoles = ['Administrator', 'HR Officer', 'Manager', 'Read-only'];

// ── POST handler ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $formErrors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // ── Toggle status ──────────────────────────────────────────────────
        if ($action === 'toggle_status') {
            $toggleId = (int) ($_POST['user_id'] ?? 0);
            if ($toggleId > 0) {
                if ($toggleId === (int) $_actor['id']) {
                    $formErrors[] = 'You cannot change your own account status.';
                } else {
                    $ts = $pdo->prepare(
                        'UPDATE users SET status = IF(status = ?, ?, ?) WHERE id = ?'
                    );
                    $ts->execute(['Active', 'Inactive', 'Active', $toggleId]);
                    $formSuccess = 'User status updated.';
                }
            }

        // ── Update user ────────────────────────────────────────────────────
        } elseif ($action === 'update') {
            $editId       = (int) ($_POST['user_id'] ?? 0);
            $editUsername = trim($_POST['username'] ?? '');
            $editEmail    = trim($_POST['email'] ?? '');
            $editFullName = trim($_POST['full_name'] ?? '');
            $editRole     = $_POST['role'] ?? '';
            $editPassword = $_POST['password'] ?? '';

            if ($editUsername === '') {
                $formErrors[] = 'Username is required.';
            } elseif (!preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $editUsername)) {
                $formErrors[] = 'Username may only contain letters, numbers, underscores, and hyphens (max 100 characters).';
            }
            if ($editEmail === '' || !filter_var($editEmail, FILTER_VALIDATE_EMAIL)) {
                $formErrors[] = 'A valid email address is required.';
            }
            if ($editFullName === '' || strlen($editFullName) > 200) {
                $formErrors[] = 'Full name is required (max 200 characters).';
            }
            if (!in_array($editRole, $allowedRoles, true)) {
                $formErrors[] = 'Invalid role selected.';
            }
            if ($editId === (int) $_actor['id'] && $editRole !== 'Administrator') {
                $formErrors[] = 'You cannot remove the Administrator role from your own account.';
            }
            if ($editPassword !== '' && strlen($editPassword) < 8) {
                $formErrors[] = 'New password must be at least 8 characters.';
            }

            if (empty($formErrors) && $editId > 0) {
                // Unique checks excluding self
                $dupU = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
                $dupU->execute([$editUsername, $editId]);
                if ($dupU->fetch()) {
                    $formErrors[] = 'That username is already taken.';
                }
                $dupE = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
                $dupE->execute([$editEmail, $editId]);
                if ($dupE->fetch()) {
                    $formErrors[] = 'That email address is already in use.';
                }
            }

            if (empty($formErrors) && $editId > 0) {
                if ($editPassword !== '') {
                    $hash = password_hash($editPassword, PASSWORD_BCRYPT);
                    $upd  = $pdo->prepare(
                        'UPDATE users SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?'
                    );
                    $upd->execute([$editUsername, $editEmail, $editFullName, $editRole, $hash, $editId]);
                } else {
                    $upd = $pdo->prepare(
                        'UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?'
                    );
                    $upd->execute([$editUsername, $editEmail, $editFullName, $editRole, $editId]);
                }
                $formSuccess = 'User updated successfully.';
            }

        // ── Create user ────────────────────────────────────────────────────
        } elseif ($action === 'create') {
            $newUsername = trim($_POST['username'] ?? '');
            $newEmail    = trim($_POST['email'] ?? '');
            $newFullName = trim($_POST['full_name'] ?? '');
            $newRole     = $_POST['role'] ?? '';
            $newPassword = $_POST['password'] ?? '';

            if ($newUsername === '') {
                $formErrors[] = 'Username is required.';
            } elseif (!preg_match('/^[a-zA-Z0-9_\-]{1,100}$/', $newUsername)) {
                $formErrors[] = 'Username may only contain letters, numbers, underscores, and hyphens (max 100 characters).';
            }
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $formErrors[] = 'A valid email address is required.';
            }
            if ($newFullName === '' || strlen($newFullName) > 200) {
                $formErrors[] = 'Full name is required (max 200 characters).';
            }
            if (!in_array($newRole, $allowedRoles, true)) {
                $formErrors[] = 'Invalid role selected.';
            }
            if (strlen($newPassword) < 8) {
                $formErrors[] = 'Password must be at least 8 characters.';
            }

            if (empty($formErrors)) {
                $dupU = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $dupU->execute([$newUsername]);
                if ($dupU->fetch()) {
                    $formErrors[] = 'That username is already taken.';
                }
                $dupE = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $dupE->execute([$newEmail]);
                if ($dupE->fetch()) {
                    $formErrors[] = 'That email address is already in use.';
                }
            }

            if (empty($formErrors)) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $ins  = $pdo->prepare(
                    'INSERT INTO users (username, email, password, full_name, role, status)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$newUsername, $newEmail, $hash, $newFullName, $newRole, 'Active']);
                $formSuccess = 'User created successfully.';
            }
        }
    }
}

// ── Load users from DB (with optional backend search / role filter) ──────────
$searchTerm  = trim($_GET['search'] ?? '');
$filterRole  = $_GET['role'] ?? '';
$allowedRolesFilter = ['', 'Administrator', 'HR Officer', 'Manager', 'Read-only'];
if (!in_array($filterRole, $allowedRolesFilter, true)) { $filterRole = ''; }

$sql    = 'SELECT id, username, email, full_name AS name, role, status, last_login, created_at FROM users WHERE 1=1';
$params = [];
if ($searchTerm !== '') {
    $sql     .= ' AND (full_name LIKE ? OR username LIKE ? OR email LIKE ?)';
    $like     = '%' . $searchTerm . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filterRole !== '') {
    $sql     .= ' AND role = ?';
    $params[] = $filterRole;
}
$sql   .= ' ORDER BY id DESC';
$stmt   = $pdo->prepare($sql);
$stmt->execute($params);
$users  = $stmt->fetchAll();

// ── Edit mode: load target user for the edit form ────────────────────────────
$editTarget  = null;
$editIdParam = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editIdParam > 0) {
    $es = $pdo->prepare('SELECT id, username, email, full_name, role FROM users WHERE id = ? LIMIT 1');
    $es->execute([$editIdParam]);
    $editTarget = $es->fetch() ?: null;
}

require_once 'includes/header.php';
?>

<?php if ($formErrors): ?>
<div class="alert alert-error">
    <ul><?php foreach ($formErrors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
<?php if ($formSuccess): ?>
<div class="alert alert-success"><?= h($formSuccess) ?></div>
<?php endif; ?>

<!-- Search / filter bar (GET form for backend search) -->
<form method="get" action="users.php">
<section class="filter-bar">
    <input class="form-control" type="search" name="search" value="<?= h($searchTerm) ?>" placeholder="Search users" aria-label="Search users">
    <select class="form-control" name="role" aria-label="Filter role" onchange="this.form.submit()">
        <option value="">All roles</option>
        <?php foreach (['Administrator', 'HR Officer', 'Manager', 'Read-only'] as $r): ?>
        <option value="<?= h($r) ?>"<?= $filterRole === $r ? ' selected' : '' ?>><?= h($r) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit"><span class="material-symbols-outlined">search</span>Search</button>
    <a href="users.php?add=1" class="btn btn-primary"><span class="material-symbols-outlined">person_add</span>Add User</a>
</section>
</form>

<!-- Add user form (shown when ?add=1) -->
<?php if (isset($_GET['add'])): ?>
<section class="panel" style="margin-bottom:24px;">
    <div class="panel-title"><h2>Add New User</h2></div>
    <form method="post" action="users.php" style="padding:16px;">
        <input type="hidden" name="csrf_token" value="<?= h(ensure_csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="form-group"><label>Username</label><input class="form-control" type="text" name="username" required maxlength="100" pattern="[a-zA-Z0-9_\-]+"></div>
            <div class="form-group"><label>Full Name</label><input class="form-control" type="text" name="full_name" required maxlength="200"></div>
            <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required maxlength="150"></div>
            <div class="form-group"><label>Role</label>
                <select class="form-control" name="role" required>
                    <?php foreach ($allowedRoles as $r): ?><option value="<?= h($r) ?>"><?= h($r) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="btn btn-primary" type="submit">Create User</button>
            <a href="users.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<!-- Edit user form (shown when ?edit=ID) -->
<?php if ($editTarget): ?>
<section class="panel" style="margin-bottom:24px;">
    <div class="panel-title"><h2>Edit User: <?= h($editTarget['full_name']) ?></h2></div>
    <form method="post" action="users.php" style="padding:16px;">
        <input type="hidden" name="csrf_token" value="<?= h(ensure_csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="user_id" value="<?= (int) $editTarget['id'] ?>">
        <div class="form-grid">
            <div class="form-group"><label>Username</label><input class="form-control" type="text" name="username" required maxlength="100" value="<?= h($editTarget['username']) ?>"></div>
            <div class="form-group"><label>Full Name</label><input class="form-control" type="text" name="full_name" required maxlength="200" value="<?= h($editTarget['full_name']) ?>"></div>
            <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required maxlength="150" value="<?= h($editTarget['email']) ?>"></div>
            <div class="form-group"><label>Role</label>
                <select class="form-control" name="role" required>
                    <?php foreach ($allowedRoles as $r): ?>
                    <option value="<?= h($r) ?>"<?= $editTarget['role'] === $r ? ' selected' : '' ?>><?= h($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>New Password <small>(leave blank to keep current)</small></label><input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password"></div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px;">
            <button class="btn btn-primary" type="submit">Save Changes</button>
            <a href="users.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><strong><?= h($user['name']) ?></strong></td>
                <td><?= h($user['email']) ?></td>
                <td><?= h($user['role']) ?></td>
                <td><span class="<?= h(status_badge_class($user['status'])) ?>"><?= h($user['status']) ?></span></td>
                <td class="td-actions">
                    <a href="users.php?edit=<?= (int) $user['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <form method="post" action="users.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= h(ensure_csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                        <button class="btn btn-sm btn-secondary" type="submit"><?= $user['status'] === 'Active' ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mobile-cards">
    <?php foreach ($users as $user): ?>
    <article class="mobile-card">
        <span class="avatar-sm"><?= h(initials($user['name'])) ?></span>
        <div>
            <strong><?= h($user['name']) ?></strong>
            <p class="text-muted"><?= h($user['role']) ?> / <?= h($user['email']) ?></p>
            <span class="<?= h(status_badge_class($user['status'])) ?>"><?= h($user['status']) ?></span>
        </div>
        <a href="users.php?edit=<?= (int) $user['id'] ?>" class="btn btn-sm btn-outline" style="margin-top:8px;">Edit</a>
    </article>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
