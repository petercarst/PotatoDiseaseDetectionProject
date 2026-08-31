<?php
require_once __DIR__ . '/functions.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title><?= e(APP_NAME) ?> &mdash; <?= e(APP_TAGLINE) ?></title>
<link rel="icon" href="assets/img/favicon.png">

<!-- Fonts (matches the Uhai Intelligence brand system) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- Tailwind (utility layer only — Bootstrap owns component defaults).
     Prefixed "tw-" so its generated utilities (e.g. a bare `.collapse`,
     which Tailwind maps to `visibility:collapse`) can never collide with
     Bootstrap's own class names like `.collapse` on the navbar. -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    prefix: 'tw-',
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          life: { DEFAULT: '#1a9650', dark: '#158249', light: '#3ecb7c' },
          intel: { DEFAULT: '#1976d2', dark: '#103068', light: '#4fa8ec' },
        },
        fontFamily: {
          display: ['"Plus Jakarta Sans"', 'sans-serif'],
          sans: ['Inter', 'sans-serif'],
        },
      },
    },
  };
</script>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg ui-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <span class="ui-logo-mark">
        <img src="assets/img/uhai-logo-mark.png" alt="Uhai Intelligence">
      </span>
      <span class="d-flex flex-column lh-1">
        <span class="fw-bold font-display text-gradient-uhai fs-5">Uhai Intelligence</span>
        <span class="ui-brand-sub">Potato Leaf Diagnostics</span>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-camera-fill me-1"></i>Scan
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'history.php' ? 'active' : '' ?>" href="history.php">
            <i class="bi bi-clock-history me-1"></i>History
          </a>
        </li>
        <li class="nav-item ms-lg-2">
          <span class="ui-api-badge" id="apiStatusBadge" title="Prediction API connectivity">
            <span class="ui-dot"></span> Checking AI engine&hellip;
          </span>
        </li>
      </ul>
    </div>
  </div>
</nav>
