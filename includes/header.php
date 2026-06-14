<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <header class="site-header">
        <div class="site-title">
            <a href="index.php">Employee Management System</a>
        </div>
        <nav class="site-nav">
            <a href="index.php">Employee List</a>
            <a href="create.php" class="btn btn-primary btn-sm">+ Add Employee</a>
        </nav>
    </header>
    <main class="main-content">
