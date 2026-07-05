<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Admin') ?> — Shuvo SMM Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<?= $extraHead ?? '' ?>
</head>
<body>
<div id="page-loader">
  <div class="loader-logo"><i class="fas fa-shield-halved"></i></div>
  <div class="loader-bar"><div class="loader-bar-fill"></div></div>
</div>
<div class="aurora-bg"></div>

<!-- Admin Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon" style="background:linear-gradient(135deg,#FF4D9D,#7C5CFF);">
      <i class="fas fa-shield-halved"></i>
    </div>
    <div class="logo-text">SMM Admin</div>
  </div>

  <nav class="sidebar-nav">
    <a href="/admin" class="nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin' ? 'active' : '') ?>" data-tooltip="Dashboard">
      <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
      <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Management</div>

    <a href="/admin/users" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/users') ? 'active' : '') ?>" data-tooltip="Users">
      <span class="nav-icon"><i class="fas fa-users"></i></span>
      <span class="nav-label">Users</span>
    </a>

    <a href="/admin/orders" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/orders') ? 'active' : '') ?>" data-tooltip="Orders">
      <span class="nav-icon"><i class="fas fa-box"></i></span>
      <span class="nav-label">Orders</span>
    </a>

    <a href="/admin/deposits" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/deposits') ? 'active' : '') ?>" data-tooltip="Deposits">
      <span class="nav-icon"><i class="fas fa-money-bill-wave"></i></span>
      <span class="nav-label">Deposits</span>
      <?php
      $pendingDeposits = \SMMPanel\Core\Database::getInstance()->fetchColumn(
          'SELECT COUNT(*) FROM smmPanel_deposits WHERE status="pending"'
      );
      if ($pendingDeposits > 0): ?>
        <span class="nav-badge"><?= $pendingDeposits ?></span>
      <?php endif; ?>
    </a>

    <a href="/admin/services" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/services') ? 'active' : '') ?>" data-tooltip="Services">
      <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
      <span class="nav-label">Services</span>
    </a>

    <div class="nav-section-label">Tools</div>

    <a href="/admin/coupons" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/coupons') ? 'active' : '') ?>" data-tooltip="Coupons">
      <span class="nav-icon"><i class="fas fa-tag"></i></span>
      <span class="nav-label">Coupons</span>
    </a>

    <a href="/admin/broadcast" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/broadcast') ? 'active' : '') ?>" data-tooltip="Broadcast">
      <span class="nav-icon"><i class="fas fa-bullhorn"></i></span>
      <span class="nav-label">Broadcast</span>
    </a>

    <a href="/admin/support" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/support') ? 'active' : '') ?>" data-tooltip="Support">
      <span class="nav-icon"><i class="fas fa-headset"></i></span>
      <span class="nav-label">Support</span>
      <?php
      $openTickets = \SMMPanel\Core\Database::getInstance()->fetchColumn(
          'SELECT COUNT(*) FROM smmPanel_support_tickets WHERE status IN ("open","in_progress")'
      );
      if ($openTickets > 0): ?>
        <span class="nav-badge"><?= $openTickets ?></span>
      <?php endif; ?>
    </a>

    <a href="/admin/blog" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/blog') ? 'active' : '') ?>" data-tooltip="Blog">
      <span class="nav-icon"><i class="fas fa-newspaper"></i></span>
      <span class="nav-label">Blog</span>
    </a>

    <div class="nav-section-label">System</div>

    <a href="/admin/settings" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/settings') ? 'active' : '') ?>" data-tooltip="Settings">
      <span class="nav-icon"><i class="fas fa-sliders"></i></span>
      <span class="nav-label">Settings</span>
    </a>

    <a href="/admin/logs" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'],'/admin/logs') ? 'active' : '') ?>" data-tooltip="Logs">
      <span class="nav-icon"><i class="fas fa-scroll"></i></span>
      <span class="nav-label">System Logs</span>
    </a>

    <a href="/dashboard" class="nav-item" data-tooltip="User View" target="_blank">
      <span class="nav-icon"><i class="fas fa-arrow-up-right-from-square"></i></span>
      <span class="nav-label">User View</span>
    </a>

    <a href="/admin/logout" class="nav-item" data-tooltip="Logout" onclick="return confirm('Sign out of admin?')">
      <span class="nav-icon"><i class="fas fa-right-from-bracket"></i></span>
      <span class="nav-label">Logout</span>
    </a>
  </nav>
</aside>

<!-- Main Layout -->
<div class="main-layout">
  <header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <div class="d-none d-md-flex align-items-center gap-2" style="font-size:0.88rem;color:var(--text-muted);">
      <span style="background:rgba(255,77,157,0.15);color:#FF4D9D;padding:3px 10px;border-radius:99px;font-size:0.75rem;font-weight:700;">
        <i class="fas fa-shield-halved"></i> ADMIN
      </span>
      <span><?= htmlspecialchars($title ?? '') ?></span>
    </div>

    <div class="topbar-right">
      <button class="topbar-btn" id="themeToggle" title="Toggle theme">
        <i class="fas fa-moon" id="themeIcon"></i>
      </button>

      <a href="/dashboard/add-funds" class="topbar-balance" style="font-size:0.82rem;">
        <i class="fas fa-server"></i> Panel Online
      </a>

      <div class="dropdown">
        <img src="/assets/img/default-avatar.svg" class="user-avatar" id="userMenuBtn" alt="Admin">
        <div class="dropdown-menu" id="userMenu">
          <div style="padding:14px 16px;border-bottom:1px solid var(--border);">
            <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($this->currentUser()['full_name'] ?? 'Admin') ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);">Super Administrator</div>
          </div>
          <a href="/admin/settings" class="dropdown-item"><i class="fas fa-sliders" style="width:16px;"></i> Settings</a>
          <div class="dropdown-divider"></div>
          <a href="/admin/logout" class="dropdown-item danger" onclick="return confirm('Sign out?')">
            <i class="fas fa-right-from-bracket" style="width:16px;"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="page-content">
    <?= $content ?>
  </main>
</div>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99;" onclick="closeSidebar()"></div>
<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/admin.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
