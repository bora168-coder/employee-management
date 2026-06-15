<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_auth();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - ' : '' ?>GovLink Pro EMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(isset($cssPath) ? $cssPath : '') ?>assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="gov-sidebar" aria-label="Primary navigation">
        <div class="brand-block">
            <a class="brand-title" href="dashboard.php">GovLink Pro EMS</a>
            <p>Gov Admin Portal</p>
        </div>

        <nav class="side-nav">
            <a href="dashboard.php" class="nav-item<?= active_nav('dashboard.php') ?>"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
            <a href="index.php" class="nav-item<?= active_nav('index.php') ?>"><span class="material-symbols-outlined">badge</span>Employees</a>
            <a href="create.php" class="nav-item<?= active_nav('create.php') ?>"><span class="material-symbols-outlined">person_add</span>Add Employee</a>
            <a href="verification.php" class="nav-item<?= active_nav('verification.php') ?>"><span class="material-symbols-outlined">verified_user</span>Verification</a>
            <a href="users.php" class="nav-item<?= active_nav('users.php') ?>"><span class="material-symbols-outlined">manage_accounts</span>User Management</a>
            <a href="deployment.php" class="nav-item<?= active_nav('deployment.php') ?>"><span class="material-symbols-outlined">monitor_heart</span>Deployment</a>
            <a href="logout.php" class="nav-item"><span class="material-symbols-outlined">logout</span>Logout</a>
        </nav>

        <div class="sidebar-footer">
            <div class="agency-mark"><span class="material-symbols-outlined">account_balance</span></div>
            <div>
                <strong>Admin HQ</strong>
                <span>Version 4.2.0</span>
            </div>
        </div>
    </aside>

    <div class="app-frame">
    <header class="topbar">
        <form class="global-search" action="index.php" method="GET" role="search">
            <span class="material-symbols-outlined">search</span>
            <input type="search" name="search" placeholder="Search records, documents, or reports..." aria-label="Search records">
        </form>
        <div class="topbar-actions">
            <span class="language-toggle"><span class="material-symbols-outlined">language</span> Khmer/English</span>
            <span class="notification-dot" aria-label="3 notifications"><span class="material-symbols-outlined">notifications</span></span>
            <div class="user-chip">
                <div>
                    <strong><?= h($user['name']) ?></strong>
                    <span><?= h($user['role']) ?></span>
                </div>
                <div class="avatar" aria-hidden="true"><?= h(initials($user['name'])) ?></div>
            </div>
        </div>
    </header>
    <nav class="mobile-nav" aria-label="Mobile navigation">
        <a href="dashboard.php" class="<?= active_nav('dashboard.php') ?>"><span class="material-symbols-outlined">dashboard</span><span>Home</span></a>
        <a href="index.php" class="<?= active_nav('index.php') ?>"><span class="material-symbols-outlined">badge</span><span>Employees</span></a>
        <a href="create.php" class="<?= active_nav('create.php') ?>"><span class="material-symbols-outlined">person_add</span><span>Add</span></a>
        <a href="verification.php" class="<?= active_nav('verification.php') ?>"><span class="material-symbols-outlined">verified_user</span><span>Verify</span></a>
        <a href="users.php" class="<?= active_nav('users.php') ?>"><span class="material-symbols-outlined">manage_accounts</span><span>Users</span></a>
    </nav>
    <main class="main-content">
        <?php if (!empty($pageEyebrow) || !empty($pageTitle)): ?>
        <div class="page-heading">
            <div>
                <?php if (!empty($pageEyebrow)): ?><p class="eyebrow"><?= h($pageEyebrow) ?></p><?php endif; ?>
                <?php if (!empty($pageTitle)): ?><h1><?= h($pageTitle) ?></h1><?php endif; ?>
            </div>
            <?php if (!empty($pageActionHtml)): ?><div class="page-actions"><?= $pageActionHtml ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
