<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? 'Shuvo SMM Panel') ?> — Shuvo SMM Panel</title>
<meta name="description" content="<?= htmlspecialchars($seoDesc ?? 'The #1 trusted SMM panel. Buy followers, likes, views and more at the best prices.') ?>">

<!-- Preconnect -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<!-- AOS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

<!-- App CSS -->
<link rel="stylesheet" href="/assets/css/app.css">

<!-- Favicon -->
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
<canvas id="particles-canvas"></canvas>

<!-- Navigation -->
<nav class="public-nav">
  <a href="/" class="nav-brand">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 0 20px rgba(124,92,255,0.4);">
      <i class="fas fa-bolt"></i>
    </div>
    <span>Shuvo SMM</span>
  </a>

  <div class="nav-links d-none d-lg-flex">
    <a href="/" class="nav-link <?= ($_SERVER['REQUEST_URI'] === '/' ? 'active' : '') ?>">Home</a>
    <a href="/services" class="nav-link <?= (str_starts_with($_SERVER['REQUEST_URI'], '/services') ? 'active' : '') ?>">Services</a>
    <a href="/api-docs" class="nav-link <?= (str_starts_with($_SERVER['REQUEST_URI'], '/api-docs') ? 'active' : '') ?>">API</a>
    <a href="/blog" class="nav-link <?= (str_starts_with($_SERVER['REQUEST_URI'], '/blog') ? 'active' : '') ?>">Blog</a>
  </div>

  <div class="nav-ctas">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="/dashboard" class="btn btn-primary btn-sm"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <?php else: ?>
      <a href="/login" class="btn btn-ghost btn-sm">Sign In</a>
      <a href="/register" class="btn btn-primary btn-sm"><i class="fas fa-rocket"></i> Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Main Content -->
<main>
  <?= $content ?>
</main>

<!-- Footer -->
<footer class="public-footer">
  <div class="container-xl">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="footer-brand">
          <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;box-shadow:0 0 30px rgba(124,92,255,0.4);">
            <i class="fas fa-bolt"></i>
          </div>
          <div class="logo-text">Shuvo SMM Panel</div>
          <p style="color:var(--text-muted);font-size:0.88rem;margin-top:12px;line-height:1.7;">
            The most trusted SMM panel for social media growth. Boost your online presence with real, high-quality engagement.
          </p>
          <div style="display:flex;gap:10px;margin-top:20px;">
            <a href="#" style="width:36px;height:36px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-secondary);transition:all 0.25s;" onmouseover="this.style.borderColor='#1877F2';this.style.color='#1877F2'" onmouseout="this.style.borderColor='';this.style.color=''">
              <i class="fab fa-facebook-f" style="font-size:0.8rem;"></i>
            </a>
            <a href="https://t.me/shuvo_9882" style="width:36px;height:36px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-secondary);transition:all 0.25s;">
              <i class="fab fa-telegram" style="font-size:0.8rem;"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div class="footer-links">
          <h6>Platform</h6>
          <a href="/services">Services</a>
          <a href="/api-docs">API Docs</a>
          <a href="/blog">Blog</a>
          <a href="/register">Sign Up</a>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div class="footer-links">
          <h6>Support</h6>
          <a href="/dashboard/support">Tickets</a>
          <a href="https://t.me/shuvo_9882">Telegram</a>
          <a href="/faq">FAQ</a>
          <a href="/dashboard/add-funds">Add Funds</a>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div class="footer-links">
          <h6>Legal</h6>
          <a href="/terms">Terms of Service</a>
          <a href="/privacy">Privacy Policy</a>
          <a href="/refund">Refund Policy</a>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div class="footer-links">
          <h6>Payments</h6>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
            <span style="padding:4px 10px;background:rgba(226,19,110,0.1);border:1px solid rgba(226,19,110,0.2);border-radius:8px;font-size:0.78rem;color:#E2136E;font-weight:600;">bKash</span>
            <span style="padding:4px 10px;background:rgba(246,166,35,0.1);border:1px solid rgba(246,166,35,0.2);border-radius:8px;font-size:0.78rem;color:#F6A623;font-weight:600;">Nagad</span>
            <span style="padding:4px 10px;background:rgba(38,161,123,0.1);border:1px solid rgba(38,161,123,0.2);border-radius:8px;font-size:0.78rem;color:#26A17B;font-weight:600;">USDT</span>
            <span style="padding:4px 10px;background:rgba(240,185,11,0.1);border:1px solid rgba(240,185,11,0.2);border-radius:8px;font-size:0.78rem;color:#F0B90B;font-weight:600;">Binance</span>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Shuvo SMM Panel. All rights reserved.</span>
      <span>Made with <i class="fas fa-heart" style="color:#FF4D9D;"></i> by <a href="https://t.me/shuvo_9882" style="color:var(--primary);">@shuvo_9882</a></span>
    </div>
  </div>
</footer>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<!-- Typed.js -->
<script src="https://cdn.jsdelivr.net/npm/typed.js@2.1.0/dist/typed.umd.js"></script>
<!-- App JS -->
<script src="/assets/js/app.js"></script>

<?= $extraScripts ?? '' ?>
</body>
</html>
