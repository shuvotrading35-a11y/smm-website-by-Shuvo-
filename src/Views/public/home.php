<?php /* Home Page (public/index) */ ?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container text-center">
    <div class="hero-badge float" data-aos="fade-down">
      <span style="background:var(--grad-aurora);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">✦</span>
      Trusted by 100,000+ marketers worldwide
    </div>

    <h1 class="hero-title gradient-text" data-aos="fade-up" data-aos-delay="100">
      The #1 Trusted<br>SMM Panel
    </h1>

    <p style="font-size:clamp(1rem,2.5vw,1.2rem);color:var(--text-secondary);max-width:600px;margin:20px auto 0;"
       data-aos="fade-up" data-aos-delay="200">
      <span id="typedText"></span>
    </p>

    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:36px;"
         data-aos="fade-up" data-aos-delay="300">
      <a href="/register" class="btn btn-aurora btn-lg">
        <i class="fas fa-rocket"></i> Get Started Free
      </a>
      <a href="/services" class="btn btn-outline btn-lg">
        <i class="fas fa-layer-group"></i> View Services
      </a>
    </div>

    <!-- Live Stats Bar -->
    <div class="glass" style="display:inline-flex;flex-wrap:wrap;border-radius:var(--radius-lg);margin-top:60px;padding:0;overflow:hidden;border-radius:20px;"
         data-aos="fade-up" data-aos-delay="400">
      <?php
      $stats = [
        ['icon'=>'fas fa-shopping-cart', 'num'=>'50K+',  'desc'=>'Services'],
        ['icon'=>'fas fa-box',           'num'=>'2M+',   'desc'=>'Orders Completed'],
        ['icon'=>'fas fa-users',         'num'=>'100K+', 'desc'=>'Happy Users'],
        ['icon'=>'fas fa-shield-check',  'num'=>'99.9%', 'desc'=>'Uptime'],
      ];
      foreach ($stats as $i => $s): ?>
        <div class="stats-bar-item" style="padding:20px 32px;<?= $i < count($stats)-1 ? 'border-right:1px solid var(--border);' : '' ?>;text-align:center;">
          <div style="font-size:1.6rem;font-weight:800;font-family:var(--font-heading);background:var(--grad-aurora);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
            <?= $s['num'] ?>
          </div>
          <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">
            <i class="<?= $s['icon'] ?>" style="margin-right:5px;"></i><?= $s['desc'] ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Platforms -->
<section style="padding:80px 0;" class="container-xl">
  <div class="text-center" data-aos="fade-up">
    <h2 class="section-title">All Major Platforms</h2>
    <p class="section-sub">Grow your presence across every social media platform</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:16px;max-width:900px;margin:0 auto;"
       data-aos="fade-up" data-aos-delay="100">
    <?php
    $platforms = [
      ['fab fa-instagram', 'Instagram',  '#E1306C'],
      ['fab fa-facebook',  'Facebook',   '#1877F2'],
      ['fab fa-tiktok',    'TikTok',     '#010101'],
      ['fab fa-youtube',   'YouTube',    '#FF0000'],
      ['fab fa-twitter',   'Twitter/X',  '#1DA1F2'],
      ['fab fa-telegram',  'Telegram',   '#0088CC'],
      ['fab fa-spotify',   'Spotify',    '#1DB954'],
      ['fab fa-linkedin',  'LinkedIn',   '#0A66C2'],
      ['fab fa-discord',   'Discord',    '#5865F2'],
      ['fab fa-snapchat',  'Snapchat',   '#FFFC00'],
    ];
    foreach ($platforms as $p): ?>
    <div class="platform-icon" style="--platform-color:<?= $p[2] ?>;"
         onclick="window.location='/services?platform=<?= urlencode(strtolower($p[1])) ?>'"
         data-aos="zoom-in">
      <i class="<?= $p[0] ?>" style="color:<?= $p[2] ?>;font-size:2.2rem;"></i>
      <span><?= $p[1] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Features -->
<section style="padding:80px 0;background:linear-gradient(180deg,transparent,rgba(124,92,255,0.03),transparent);">
  <div class="container-xl">
    <div class="text-center" data-aos="fade-up">
      <h2 class="section-title">Why Choose Us</h2>
      <p class="section-sub">Everything you need to dominate social media growth</p>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['fas fa-bolt',           '#7C5CFF', 'rgba(124,92,255,0.15)', 'Instant Delivery',    'Orders start within seconds of payment. No waiting, no delays.'],
        ['fas fa-shield-halved',  '#00D4FF', 'rgba(0,212,255,0.15)',  'Secure Payments',     'SSL encrypted checkout. Your financial data is always protected.'],
        ['fas fa-code',           '#FF4D9D', 'rgba(255,77,157,0.15)', 'Full API Access',     'Automate orders with our REST API. PHP, Python, Node.js examples included.'],
        ['fas fa-tags',           '#00E676', 'rgba(0,230,118,0.15)',  'Lowest Prices',       'Wholesale pricing with no hidden fees. Best rates in the industry.'],
        ['fas fa-rotate',         '#FFD600', 'rgba(255,214,0,0.15)',  'Auto Refill',         'Orders that drop get automatically refilled for qualifying services.'],
        ['fas fa-headset',        '#9C27B0', 'rgba(156,39,176,0.15)', '24/7 Support',        'Our support team responds within minutes via tickets or Telegram.'],
      ];
      foreach ($features as $i => $f): ?>
      <div class="col-6 col-md-4" data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
        <div class="feature-card h-100">
          <div class="feature-icon" style="background:<?= $f[2] ?>;color:<?= $f[1] ?>;">
            <i class="<?= $f[0] ?>"></i>
          </div>
          <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:10px;"><?= $f[3] ?></h5>
          <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.65;"><?= $f[4] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Pricing Preview -->
<section style="padding:80px 0;" class="container-xl" data-aos="fade-up">
  <div class="text-center mb-5">
    <h2 class="section-title">Top Services</h2>
    <p class="section-sub">Explore our most popular services — no account needed</p>
  </div>

  <div class="table-wrap glass">
    <table class="table">
      <thead>
        <tr>
          <th>Platform</th>
          <th>Service</th>
          <th>Rate / 1000</th>
          <th>Min</th>
          <th>Max</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="topServicesTable">
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">Loading services...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="text-center mt-4">
    <a href="/services" class="btn btn-primary btn-lg"><i class="fas fa-layer-group"></i> View All Services</a>
  </div>
</section>

<!-- Testimonials -->
<section style="padding:80px 0;background:linear-gradient(180deg,transparent,rgba(0,212,255,0.02),transparent);">
  <div class="container-xl">
    <div class="text-center" data-aos="fade-up">
      <h2 class="section-title">What Customers Say</h2>
    </div>
    <div class="row g-4 mt-2">
      <?php
      $testimonials = [
        ['Arif H.',    '★★★★★', 'Best SMM panel I\'ve used. Prices are unbeatable and delivery is always instant.', 'Instagram Marketer'],
        ['Priya S.',   '★★★★★', 'API integration was super easy. I\'ve automated all my client orders now.', 'Agency Owner'],
        ['Mehmet K.',  '★★★★★', 'Support team is amazing. They fixed my issue within 10 minutes on Telegram.', 'Social Media Manager'],
        ['Linh T.',    '★★★★★', 'I tried 5 SMM panels. This is the only one with consistent quality and speed.', 'Content Creator'],
        ['James O.',   '★★★★★', 'The referral program is great. I earn passive income just from sharing my link.', 'Affiliate Marketer'],
        ['Sofia R.',   '★★★★★', 'bKash payment works perfectly. Perfect for Bangladesh-based users like me.', 'Local Business Owner'],
      ];
      foreach ($testimonials as $i => $t): ?>
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div class="glass-flat p-4 h-100">
          <div style="color:#FFD600;font-size:0.9rem;margin-bottom:10px;"><?= $t[1] ?></div>
          <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.7;font-style:italic;">"<?= htmlspecialchars($t[2]) ?>"</p>
          <div style="margin-top:16px;display:flex;align-items:center;gap:10px;border-top:1px solid var(--border);padding-top:14px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--grad-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
              <?= $t[0][0] ?>
            </div>
            <div>
              <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($t[0]) ?></div>
              <div style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($t[3]) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FAQ -->
<section style="padding:80px 0;" class="container-xl">
  <div class="text-center" data-aos="fade-up">
    <h2 class="section-title">Frequently Asked Questions</h2>
  </div>
  <div style="max-width:760px;margin:0 auto;" data-aos="fade-up" data-aos-delay="100">
    <?php
    $faqs = [
      ['How fast are orders delivered?',           'Most orders start within 30 seconds to a few minutes. Speed varies by service — instant delivery services are marked in the service list.'],
      ['Is it safe for my accounts?',              'We use high-quality, gradual delivery methods designed to look natural. However, no SMM panel can guarantee 100% safety — use at your own discretion.'],
      ['What payment methods do you accept?',      'We accept bKash, Nagad, Rocket, USDT TRC20, USDT ERC20, and Binance Pay. All deposits are manually verified by our team.'],
      ['What is the minimum deposit?',             'The minimum deposit is $1 equivalent. This ensures small top-ups are possible for budget-conscious users.'],
      ['Do you offer refills?',                    'Yes! Services with refill support will automatically refill if the count drops. Look for the ♻ Refill badge on services.'],
      ['Can I use your API?',                      'Yes. All accounts get access to our API. You can find your API key and documentation under Dashboard → API Access.'],
      ['What happens if my order doesn\'t deliver?', 'Open a support ticket with your Order ID. We\'ll investigate within 24 hours and issue a refill or refund if applicable.'],
      ['Do you have a referral program?',          'Yes! You earn a commission on every order placed by users you refer. Find your referral link under Dashboard → Referrals.'],
      ['How do I contact support?',               'Open a ticket at Dashboard → Support, or message us directly on Telegram at @shuvo_9882.'],
      ['Is my account information secure?',        'Absolutely. We use bcrypt for passwords, CSRF protection, and SSL encryption. Your data is never shared with third parties.'],
    ];
    foreach ($faqs as $i => $faq): ?>
    <div style="border-bottom:1px solid var(--border);">
      <button
        onclick="this.closest('div').classList.toggle('open');this.nextElementSibling.style.maxHeight=this.closest('div').classList.contains('open')?'200px':'0';"
        style="width:100%;display:flex;justify-content:space-between;align-items:center;padding:20px 4px;background:none;border:none;color:var(--text);font-family:var(--font-body);font-size:0.95rem;font-weight:600;cursor:pointer;text-align:left;gap:12px;">
        <?= htmlspecialchars($faq[0]) ?>
        <i class="fas fa-chevron-down" style="flex-shrink:0;transition:transform 0.3s;font-size:0.85rem;color:var(--text-muted);"></i>
      </button>
      <div style="max-height:0;overflow:hidden;transition:max-height 0.35s ease;">
        <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.7;padding:0 4px 20px;"><?= htmlspecialchars($faq[1]) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA Banner -->
<section style="padding:80px 40px;text-align:center;" data-aos="fade-up">
  <div class="glass" style="max-width:700px;margin:0 auto;padding:60px 40px;">
    <h2 style="font-size:2.5rem;font-family:var(--font-heading);margin-bottom:16px;" class="gradient-text">Ready to Grow?</h2>
    <p style="color:var(--text-secondary);font-size:1.05rem;margin-bottom:32px;">Join 100,000+ marketers already using Shuvo SMM Panel</p>
    <a href="/register" class="btn btn-aurora btn-lg"><i class="fas fa-rocket"></i> Create Free Account</a>
  </div>
</section>

<script>
// Typed.js
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Typed !== 'undefined') {
    new Typed('#typedText', {
      strings: [
        'Buy Instagram followers instantly.',
        'Grow YouTube views & subscribers.',
        'Boost TikTok engagement today.',
        'Increase Facebook page likes.',
        'Build Telegram channel members.',
      ],
      typeSpeed: 45,
      backSpeed: 25,
      loop: true,
      backDelay: 2000,
    });
  }

  // Load top services
  fetch('/api/services?limit=10')
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.data) return;
      const tbody = document.getElementById('topServicesTable');
      tbody.innerHTML = data.data.slice(0, 10).map(s => `
        <tr>
          <td><span style="font-size:0.85rem;color:var(--text-secondary);">${escHtml(s.category || '—')}</span></td>
          <td style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.name)}</td>
          <td style="color:var(--primary);font-weight:700;">$${parseFloat(s.user_rate||s.rate).toFixed(4)}</td>
          <td>${parseInt(s.min_quantity).toLocaleString()}</td>
          <td>${parseInt(s.max_quantity).toLocaleString()}</td>
          <td><a href="/dashboard/new-order?service=${s.id}" class="btn btn-primary btn-sm">Order</a></td>
        </tr>
      `).join('');
    });

  // FAQ chevron rotation
  document.querySelectorAll('[onclick*="classList.toggle"]').forEach(btn => {
    btn.addEventListener('click', function() {
      const icon = this.querySelector('.fa-chevron-down');
      if (icon) icon.style.transform = this.closest('div').classList.contains('open') ? 'rotate(180deg)' : '';
    });
  });
});
</script>
