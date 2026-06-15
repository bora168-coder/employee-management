<?php
require_once 'includes/auth.php';
require_auth();
$pageTitle = 'User Management';
$pageEyebrow = 'Portal / Administration';

// TODO: Replace this isolated placeholder with a users/roles table when backend support exists.
$users = [
    ['name' => 'S. Rathana', 'email' => 'rathana@govlink.local', 'role' => 'Executive Director', 'status' => 'Active'],
    ['name' => 'HR Unit', 'email' => 'hr@govlink.local', 'role' => 'Records Manager', 'status' => 'Active'],
    ['name' => 'Audit Office', 'email' => 'audit@govlink.local', 'role' => 'Auditor', 'status' => 'Suspended'],
];

require_once 'includes/header.php';
?>

<section class="filter-bar">
    <input class="form-control" type="search" placeholder="Search users" aria-label="Search users">
    <select class="form-control" aria-label="Filter role"><option>All roles</option><option>Executive Director</option><option>Records Manager</option><option>Auditor</option></select>
    <button class="btn btn-primary" type="button"><span class="material-symbols-outlined">person_add</span>Add User</button>
</section>

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
                <td class="td-actions"><button class="btn btn-sm btn-outline" type="button">Edit</button><button class="btn btn-sm btn-secondary" type="button">Permissions</button></td>
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
    </article>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
