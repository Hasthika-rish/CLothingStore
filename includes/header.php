<?php
/**
 * Global Header Template
 * Anjiana Clothing Store
 */
require_once __DIR__ . '/functions.php';

$site_name = getSetting('site_name', SITE_NAME);
$page_title = isset($page_title) ? $page_title . ' | ' . $site_name : $site_name . ' | Modern Clothing Store';
$page_desc = isset($page_desc) ? $page_desc : 'Discover modern, premium clothing and stylish fashion at ' . $site_name . '. Shop our latest collection today.';
$og_image = isset($og_image) ? $og_image : 'images/hero_banner.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="icon" type="image/png" href="images/logo.png">
  <meta name="description" content="<?= e($page_desc) ?>">
  <meta name="keywords" content="clothing, fashion, modern apparel, premium fabrics, Anjiana Store, shop clothes online">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_desc) ?>">
  <meta property="og:image" content="<?= e($og_image) ?>">

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:title" content="<?= e($page_title) ?>">
  <meta property="twitter:description" content="<?= e($page_desc) ?>">
  <meta property="twitter:image" content="<?= e($og_image) ?>">

  <!-- Google Fonts & Stylesheet -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

  <!-- Toast Notification Container -->
  <div id="toastNotification" class="toast-notification" role="alert" aria-live="polite">
    <div class="toast-icon" id="toastIcon">✓</div>
    <div class="toast-content">
      <div class="toast-title" id="toastTitle">Notification</div>
      <div class="toast-message" id="toastMessage"></div>
    </div>
  </div>

  <?php if (hasFlash('success')): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (window.showToast) window.showToast(<?= json_encode(getFlash('success')) ?>, false);
      });
    </script>
  <?php endif; ?>

  <?php if (hasFlash('error')): ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (window.showToast) window.showToast(<?= json_encode(getFlash('error')) ?>, true);
      });
    </script>
  <?php endif; ?>
