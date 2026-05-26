<?php
/**
 * Layout partial — HTML <head> + sidebar + topbar
 * Included at the top of every authenticated page.
 *
 * Expected variables from the calling script:
 * @var string $pageTitle   Short title shown in <title> and topbar
 * @var string $activePage  Sidebar nav key, e.g. 'dashboard' | 'products'
 */

$lowStockCount = getLowStockCount(isAdmin() ? null : ($_SESSION['user_id'] ?? null));
$userInitials  = strtoupper(substr($_SESSION['user_full_name'] ?? 'U', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle) ?> — InvenTrack</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <!-- Font Awesome for icons -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plh7eecXumXlmBTGOHCGwI5KWcK8g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<!-- Mobile sidebar overlay -->
<div id="sidebar-overlay" style="
  display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
  z-index:99; transition:.2s;
" onclick="this.classList.remove('active'); document.getElementById('sidebar').classList.remove('open');"></div>

<div class="app-layout">

  <!-- ── Sidebar ──────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
      <div class="sidebar-logo-icon">📦</div>
      <span class="sidebar-logo-text">InvenTrack</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>

      <a href="<?= BASE_URL ?>/index.php?page=dashboard"
         class="nav-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
        Dashboard
      </a>

      <a href="<?= BASE_URL ?>/index.php?page=products"
         class="nav-item <?= ($activePage === 'products') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-box"></i></span>
        Products
        <?php if ($lowStockCount > 0): ?>
          <span class="nav-badge"><?= $lowStockCount ?></span>
        <?php endif; ?>
      </a>

      <?php if (isAdmin()): ?>
      <div class="nav-section-label">Admin</div>
      <a href="<?= BASE_URL ?>/index.php?page=users"
         class="nav-item <?= ($activePage === 'users') ? 'active' : '' ?>">
        <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
        Users
      </a>
      <?php endif; ?>

    </nav><!-- /sidebar-nav -->

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= $userInitials ?></div>
        <div class="user-info">
          <div class="user-name"><?= sanitize($_SESSION['user_full_name'] ?? '') ?></div>
          <div class="user-role"><?= sanitize($_SESSION['user_role'] ?? '') ?></div>
        </div>
      </div>
    </div>

  </aside><!-- /sidebar -->

  <!-- ── Main content area ────────────────────────────────── -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button id="menu-toggle" style="
          display:none; background:none; border:none; color:var(--text-primary);
          font-size:1.25rem; cursor:pointer; margin-right:.5rem;
        " aria-label="Toggle menu">☰</button>
        <h1><?= sanitize($pageTitle) ?></h1>
      </div>
      <div class="topbar-right">
        <span class="topbar-time" id="live-clock"></span>
        <a href="<?= BASE_URL ?>/index.php?page=logout" class="logout-btn">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>
    </header>

    <!-- Flash messages -->
    <div style="padding:0 1.75rem; margin-top:1.25rem;">
    <?php
    $flash = getFlash();
    if ($flash):
        $icons = [
            'success' => '✅',
            'error'   => '❌',
            'warning' => '⚠️',
            'info'    => 'ℹ️',
        ];
        $icon = $icons[$flash['type']] ?? 'ℹ️';
    ?>
      <div class="alert alert-<?= sanitize($flash['type']) ?>" data-auto-dismiss>
        <span class="alert-icon"><?= $icon ?></span>
        <div class="alert-body"><?= sanitize($flash['message']) ?></div>
      </div>
    <?php endif; ?>
    </div>

    <!-- Page body injected here -->
