<?php
/**
 * Admin Layout Header
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$admin = currentAdmin();
$site_name = getSetting('site_name', SITE_NAME);
$admin_page_title = $admin_page_title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($admin_page_title) ?> | <?= e($site_name) ?> Admin</title>
  <link rel="icon" type="image/png" href="../images/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    /* Clean admin specific enhancements */
    .admin-stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2.5rem;
    }
    .stat-card {
      background: var(--card-bg, #fff);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      position: relative;
      overflow: hidden;
    }
    .stat-card .stat-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }
    .stat-card .stat-title {
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      font-weight: 600;
    }
    .stat-card .stat-value {
      font-size: 2rem;
      font-weight: 800;
      color: var(--primary-color);
      margin-top: 0.25rem;
    }
    .admin-table-container {
      background: var(--card-bg, #fff);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      overflow-x: auto;
      box-shadow: var(--shadow-sm);
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    .admin-table th {
      background: var(--bg-alt, #f8f9fa);
      padding: 14px 18px;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      font-weight: 700;
      border-bottom: 1px solid var(--border-color);
    }
    .admin-table td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-color);
      vertical-align: middle;
      font-size: 0.92rem;
    }
    .admin-table tr:hover {
      background: var(--bg-alt, rgba(0,0,0,0.015));
    }
  </style>
</head>
<body>

  <!-- Toast Notification Container -->
  <div id="toastNotification" class="toast-notification" role="alert">
    <div class="toast-icon" id="toastIcon">✓</div>
    <div class="toast-content">
      <div class="toast-title" id="toastTitle">Notification</div>
      <div class="toast-message" id="toastMessage"></div>
    </div>
  </div>

  <div class="admin-layout">
    <?php require_once __DIR__ . '/admin-sidebar.php'; ?>

    <main class="admin-content">
      <!-- Admin Top Bar -->
      <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <h1 style="font-size: 2rem; font-weight: 800;"><?= e($admin_page_title) ?></h1>
          <p class="text-muted" style="font-size: 0.9rem; margin-top: 0.2rem;">Welcome back, <strong><?= e($admin['name'] ?? 'Admin') ?></strong></p>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center;">
          <!-- Storefront Quick Link -->
          <a href="../index.php" target="_blank" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
            <span>🌐 View Storefront</span>
          </a>

          <!-- Theme Toggle -->
          <button class="icon-btn theme-toggle" id="themeToggleBtn" aria-label="Toggle Theme" title="Toggle Theme">
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
          </button>

          <!-- Logout Button -->
          <a href="logout.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem; color: #C62828; border-color: #FFCDD2;" onclick="return confirm('Log out of administrator account?');">
            Logout
          </a>
        </div>
      </div>

      <?php if (hasFlash('success')): ?>
        <div style="background: #E8F5E9; border: 1px solid #C8E6C9; color: #2E7D32; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500;">
          ✓ <?= e(getFlash('success')) ?>
        </div>
      <?php endif; ?>

      <?php if (hasFlash('error')): ?>
        <div style="background: #FFEBEE; border: 1px solid #FFCDD2; color: #C62828; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500;">
          ✕ <?= e(getFlash('error')) ?>
        </div>
      <?php endif; ?>
