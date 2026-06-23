<?php
// ============================================================
// includes/header.php - Global Header
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$theme = $_COOKIE['theme'] ?? 'light';
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="<?= $theme ?>-mode">

<nav class="navbar">
  <div class="nav-brand">
    <i class="fa-solid fa-road"></i>
    <span><?= APP_NAME ?></span>
  </div>
  <div class="nav-links">
    <?php if ($currentUser): ?>
      <a href="<?= APP_URL ?>/modules/complaints/list.php"><i class="fa fa-list"></i> Complaints</a>
      <?php if ($currentUser['role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/modules/admin/dashboard.php"><i class="fa fa-gauge"></i> Admin</a>
        <a href="<?= APP_URL ?>/reports/reopened.php"><i class="fa fa-chart-bar"></i> Reports</a>
      <?php elseif ($currentUser['role'] === 'staff'): ?>
        <a href="<?= APP_URL ?>/modules/staff/dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
      <?php else: ?>
        <a href="<?= APP_URL ?>/modules/complaints/add.php"><i class="fa fa-plus"></i> New Complaint</a>
        <a href="<?= APP_URL ?>/modules/users/dashboard.php"><i class="fa fa-gauge"></i> My Dashboard</a>
      <?php endif; ?>
      <div class="nav-user">
        <i class="fa fa-user-circle"></i>
        <span><?= htmlspecialchars($currentUser['name']) ?></span>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout"><i class="fa fa-sign-out-alt"></i></a>
      </div>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login.php" class="btn-nav-login">Login</a>
    <?php endif; ?>
    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
      <i class="fa <?= $theme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
    </button>
  </div>
  <button class="hamburger" id="hamburger"><i class="fa fa-bars"></i></button>
</nav>
<div class="mobile-menu" id="mobileMenu">
  <?php if ($currentUser): ?>
    <a href="<?= APP_URL ?>/modules/complaints/list.php">All Complaints</a>
    <?php if ($currentUser['role'] === 'admin'): ?>
      <a href="<?= APP_URL ?>/modules/admin/dashboard.php">Admin Panel</a>
      <a href="<?= APP_URL ?>/reports/reopened.php">Reports</a>
    <?php elseif ($currentUser['role'] === 'staff'): ?>
      <a href="<?= APP_URL ?>/modules/staff/dashboard.php">My Dashboard</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/modules/complaints/add.php">File Complaint</a>
      <a href="<?= APP_URL ?>/modules/users/dashboard.php">My Dashboard</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/logout.php">Logout</a>
  <?php else: ?>
    <a href="<?= APP_URL ?>/login.php">Login</a>
    <a href="<?= APP_URL ?>/register.php">Register</a>
  <?php endif; ?>
</div>
<main class="main-content">
