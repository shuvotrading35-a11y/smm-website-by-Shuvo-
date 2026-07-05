<?php /* Login View */ ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:440px;">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:36px;" data-aos="fade-down">
      <a href="/" style="display:inline-flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;box-shadow:0 0 40px rgba(124,92,255,0.5);">
          <i class="fas fa-bolt"></i>
        </div>
        <span style="font-family:var(--font-heading);font-weight:700;font-size:1.3rem;color:var(--text);">Shuvo SMM Panel</span>
      </a>
    </div>

    <div class="glass" style="padding:36px;" data-aos="fade-up">
      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.6rem;margin-bottom:6px;">Welcome back</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:28px;">Sign in to your account</p>

      <div id="loginError" style="display:none;padding:12px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.88rem;margin-bottom:20px;"></div>

      <form id="loginForm" onsubmit="submitLogin(event)">
        <div class="form-group">
          <label class="form-label">Email or Username</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-user"></i>
            <input type="text" name="identifier" class="form-control" placeholder="you@email.com" required autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;">
            Password
            <a href="/forgot-password" style="font-weight:400;text-transform:none;letter-spacing:0;font-size:0.82rem;color:var(--primary);">Forgot password?</a>
          </label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" onclick="togglePw()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
              <i class="fas fa-eye" id="pwEyeIcon"></i>
            </button>
          </div>
        </div>

        <!-- hCaptcha (shown after 5 failed attempts) -->
        <div id="captchaWrapper" style="display:none;margin-bottom:16px;">
          <div class="h-captcha" data-sitekey="<?= htmlspecialchars($hcaptchaKey ?? '') ?>"></div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.88rem;color:var(--text-secondary);">
            <input type="checkbox" name="remember" style="accent-color:var(--primary);"> Remember me
          </label>
        </div>

        <button type="submit" class="btn btn-aurora w-100 btn-lg" id="loginBtn">
          <i class="fas fa-arrow-right-to-bracket"></i> Sign In
        </button>
      </form>

      <div class="divider"></div>

      <p style="text-align:center;color:var(--text-secondary);font-size:0.9rem;">
        Don't have an account?
        <a href="/register" style="color:var(--primary);font-weight:600;">Create one free</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const input = document.getElementById('passwordInput');
  const icon  = document.getElementById('pwEyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fas fa-eye';
  }
}

async function submitLogin(e) {
  e.preventDefault();
  const btn   = document.getElementById('loginBtn');
  const errEl = document.getElementById('loginError');
  const form  = e.target;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Signing in...';
  errEl.style.display = 'none';

  const data = await fetch('/login', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(form),
  }).then(r => r.json());

  if (data.success) {
    btn.innerHTML = '<i class="fas fa-check"></i> Success!';
    window.location.href = data.redirect || '/dashboard';
  } else {
    errEl.style.display = 'block';
    errEl.textContent   = data.message;

    if (data.captcha) {
      document.getElementById('captchaWrapper').style.display = 'block';
      if (typeof hcaptcha !== 'undefined') hcaptcha.render();
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> Sign In';
  }
}
</script>
