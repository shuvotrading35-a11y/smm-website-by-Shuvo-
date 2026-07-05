<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Dashboard') ?> — Shuvo SMM Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<?= $extraHead ?? '' ?>
</head>
<body>

<!-- Page Loader -->
<div id="page-loader">
  <div class="loader-logo"><i class="fas fa-bolt"></i></div>
  <div class="loader-bar"><div class="loader-bar-fill"></div></div>
</div>

<!-- Aurora Background -->
<div class="aurora-bg"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-bolt"></i></div>
    <div class="logo-text">Shuvo SMM</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="/dashboard" class="nav-item <?= ($_SERVER['REQUEST_URI'] === '/dashboard' ? 'active' : '') ?>" data-tooltip="Dashboard">
      <span class="nav-icon"><i class="fas fa-house"></i></span>
      <span class="nav-label">Dashboard</span>
    </a>

    <a href="/dashboard/new-order" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'new-order') ? 'active' : '') ?>" data-tooltip="New Order">
      <span class="nav-icon"><i class="fas fa-cart-plus"></i></span>
      <span class="nav-label">New Order</span>
    </a>

    <a href="/dashboard/orders" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], '/orders') ? 'active' : '') ?>" data-tooltip="My Orders">
      <span class="nav-icon"><i class="fas fa-box"></i></span>
      <span class="nav-label">My Orders</span>
    </a>

    <a href="/dashboard/order-status" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'order-status') ? 'active' : '') ?>" data-tooltip="Order Status">
      <span class="nav-icon"><i class="fas fa-magnifying-glass"></i></span>
      <span class="nav-label">Order Status</span>
    </a>

    <div class="nav-section-label">Finance</div>

    <a href="/dashboard/add-funds" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'add-funds') ? 'active' : '') ?>" data-tooltip="Add Funds">
      <span class="nav-icon"><i class="fas fa-wallet"></i></span>
      <span class="nav-label">Add Funds</span>
    </a>

    <a href="/dashboard/transactions" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'transactions') ? 'active' : '') ?>" data-tooltip="Transactions">
      <span class="nav-icon"><i class="fas fa-receipt"></i></span>
      <span class="nav-label">Transactions</span>
    </a>

    <div class="nav-section-label">Tools</div>

    <a href="/dashboard/favorites" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'favorites') ? 'active' : '') ?>" data-tooltip="Favorites">
      <span class="nav-icon"><i class="fas fa-heart"></i></span>
      <span class="nav-label">Favorites</span>
    </a>

    <a href="/dashboard/api" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], '/api') ? 'active' : '') ?>" data-tooltip="API Access">
      <span class="nav-icon"><i class="fas fa-code"></i></span>
      <span class="nav-label">API Access</span>
    </a>

    <a href="/dashboard/coupons" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'coupons') ? 'active' : '') ?>" data-tooltip="Coupons">
      <span class="nav-icon"><i class="fas fa-tag"></i></span>
      <span class="nav-label">Coupons</span>
    </a>

    <a href="/dashboard/referrals" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'referrals') ? 'active' : '') ?>" data-tooltip="Referrals">
      <span class="nav-icon"><i class="fas fa-people-group"></i></span>
      <span class="nav-label">Referrals</span>
    </a>

    <div class="nav-section-label">Help</div>

    <a href="/dashboard/support" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'support') ? 'active' : '') ?>" data-tooltip="Support">
      <span class="nav-icon"><i class="fas fa-headset"></i></span>
      <span class="nav-label">Support</span>
      <?php if (($openTickets ?? 0) > 0): ?>
        <span class="nav-badge"><?= $openTickets ?></span>
      <?php endif; ?>
    </a>

    <a href="/dashboard/settings" class="nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'settings') ? 'active' : '') ?>" data-tooltip="Settings">
      <span class="nav-icon"><i class="fas fa-gear"></i></span>
      <span class="nav-label">Settings</span>
    </a>

    <a href="/logout" class="nav-item" data-tooltip="Logout" onclick="return confirm('Sign out?')">
      <span class="nav-icon"><i class="fas fa-right-from-bracket"></i></span>
      <span class="nav-label">Logout</span>
    </a>
  </nav>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer" style="padding:16px;border-top:1px solid var(--border);">
    <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
      <img src="<?= htmlspecialchars($user['avatar'] ?? '/assets/img/default-avatar.svg') ?>"
           style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border-active);"
           alt="Avatar">
      <div class="nav-label" style="overflow:hidden;">
        <div style="font-size:0.88rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= htmlspecialchars($user['full_name'] ?? '') ?>
        </div>
        <div style="font-size:0.75rem;color:var(--text-muted);">
          $<?= number_format((float)($user['balance'] ?? 0), 2) ?>
        </div>
      </div>
    </div>
  </div>
</aside>

<!-- Main Layout -->
<div class="main-layout">

  <!-- Topbar -->
  <header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle" title="Toggle sidebar">
      <i class="fas fa-bars"></i>
    </button>

    <!-- Page breadcrumb -->
    <div class="d-none d-md-block" style="font-size:0.88rem;color:var(--text-muted);">
      <a href="/dashboard" style="color:var(--text-muted);">Dashboard</a>
      <?php if (($title ?? '') !== 'Dashboard'): ?>
        <span style="margin:0 6px;">›</span>
        <span style="color:var(--text);"><?= htmlspecialchars($title ?? '') ?></span>
      <?php endif; ?>
    </div>

    <div class="topbar-right">
      <!-- Balance -->
      <a href="/dashboard/add-funds" class="topbar-balance d-none d-sm-flex">
        <i class="fas fa-wallet"></i>
        $<?= number_format((float)($user['balance'] ?? 0), 2) ?>
      </a>

      <!-- Theme toggle -->
      <button class="topbar-btn" id="themeToggle" title="Toggle theme">
        <i class="fas fa-moon" id="themeIcon"></i>
      </button>

      <!-- Notifications -->
      <div class="dropdown">
        <button class="topbar-btn" id="notifBtn" title="Notifications">
          <i class="fas fa-bell"></i>
          <?php if (($unreadCount ?? 0) > 0): ?>
            <span class="notif-dot"></span>
          <?php endif; ?>
        </button>

        <div class="dropdown-menu" id="notifDropdown" style="width:340px;right:0;">
          <div style="padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <span style="font-weight:700;font-size:0.95rem;">Notifications</span>
            <button onclick="markAllRead()" style="font-size:0.78rem;color:var(--primary);background:none;border:none;cursor:pointer;">Mark all read</button>
          </div>
          <div id="notifList" style="max-height:320px;overflow-y:auto;"></div>
          <div style="padding:12px;text-align:center;border-top:1px solid var(--border);">
            <a href="/dashboard/settings#notifications" style="font-size:0.85rem;color:var(--primary);">View all</a>
          </div>
        </div>
      </div>

      <!-- User Avatar -->
      <div class="dropdown">
        <img src="<?= htmlspecialchars($user['avatar'] ?? '/assets/img/default-avatar.svg') ?>"
             class="user-avatar"
             id="userMenuBtn"
             alt="Avatar">

        <div class="dropdown-menu" id="userMenu">
          <div style="padding:14px 16px;border-bottom:1px solid var(--border);">
            <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
          <a href="/dashboard/settings" class="dropdown-item"><i class="fas fa-gear" style="width:16px;"></i> Settings</a>
          <a href="/dashboard/api" class="dropdown-item"><i class="fas fa-code" style="width:16px;"></i> API Access</a>
          <a href="/services" class="dropdown-item"><i class="fas fa-layer-group" style="width:16px;"></i> Services</a>
          <div class="dropdown-divider"></div>
          <a href="/logout" class="dropdown-item danger" onclick="return confirm('Sign out?')">
            <i class="fas fa-right-from-bracket" style="width:16px;"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Page Content -->
  <main class="page-content">
    <?= $content ?>
  </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-nav">
  <a href="/dashboard" class="mobile-nav-item <?= ($_SERVER['REQUEST_URI'] === '/dashboard' ? 'active' : '') ?>">
    <i class="fas fa-house"></i><span>Home</span>
  </a>
  <a href="/dashboard/new-order" class="mobile-nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'new-order') ? 'active' : '') ?>">
    <i class="fas fa-cart-plus"></i><span>Order</span>
  </a>
  <a href="/dashboard/orders" class="mobile-nav-item <?= (str_contains($_SERVER['REQUEST_URI'], '/orders') ? 'active' : '') ?>">
    <i class="fas fa-box"></i><span>Orders</span>
  </a>
  <a href="/dashboard/add-funds" class="mobile-nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'add-funds') ? 'active' : '') ?>">
    <i class="fas fa-wallet"></i><span>Funds</span>
  </a>
  <a href="/dashboard/settings" class="mobile-nav-item <?= (str_contains($_SERVER['REQUEST_URI'], 'settings') ? 'active' : '') ?>">
    <i class="fas fa-gear"></i><span>Settings</span>
  </a>
</nav>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99;backdrop-filter:blur(4px);" onclick="closeSidebar()"></div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/dashboard.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
