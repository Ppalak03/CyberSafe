<?php
// Expects (optional): $pageTitle, $layout ('public' | 'auth' | 'app'), $activeNav, $extraCss (array), $currentUser (app layout)
declare(strict_types=1);
$layout    = $layout ?? 'public';
$pageTitle = $pageTitle ?? APP_NAME;
$extraCss  = $extraCss ?? [];
$flashes   = pull_flash();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
  <link href="<?= e(url('assets/css/responsive.css')) ?>" rel="stylesheet">
<?php foreach ($extraCss as $css): ?>
  <link href="<?= e(url('assets/css/' . $css)) ?>" rel="stylesheet">
<?php endforeach; ?>
</head>
<body class="layout-<?= e($layout) ?>" data-flash="<?= e(json_encode($flashes)) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div id="toasts" class="toasts" aria-live="polite"></div>
<?php if ($layout === 'app'): ?>
<div class="app-shell">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="app-main">
    <?php include __DIR__ . '/navbar.php'; ?>
    <main id="main" class="content">
<?php else: ?>
  <?php include __DIR__ . '/public_nav.php'; ?>
  <main id="main" class="<?= $layout === 'auth' ? 'auth-main' : 'public-main' ?>">
<?php endif; ?>
