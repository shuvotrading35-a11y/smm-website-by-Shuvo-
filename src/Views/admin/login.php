<?php /* Admin Login View */ ?>

<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:420px;">

    <div style="text-align:center;margin-bottom:36px;">
      <a href="/" style="display:inline-flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;box-shadow:0 0 40px rgba(124,92,255,0.5);">
          <i class="fas fa-shield-halved"></i>
        </div>
        <span style="font-family:var(--font-heading);font-weight:700;font-size:1.3rem;color:var(--text);">Shuvo SMM Panel</span>
      </a>
    </div>

    <div class="glass" style="padding:36px;">
      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:6px;">Admin Sign In</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:28px;">Restricted area — authorized staff only</p>

      <div id="loginError" style="display:none;padding:12px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.88rem;margin-bottom:20px;"></div>

      <form id="adminLoginForm" onsubmit="submitAdminLogin(event)">
        <div class="form-group">
          <label class="form-label">Email or Username</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-user-shield"></i>
            <input type="text" name="identifier" class="form-control" placeholder="admin@email.com" required autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" onclick="togglePw()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
              <i class="fas fa-eye" id="pwEyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-aurora w-100 btn-lg" id="loginBtn" style="margin-top:8px;">
          <i class="fas fa-arrow-right-to-bracket"></i> Sign In
        </button>
      </form>
    </div>

    <p style="text-align:center;color:var(--text-secondary);font-size:0.82rem;margin-top:20px;">
      <a href="/" style="color:var(--text-secondary);"><i class="fas fa-arrow-left"></i> Back to site</a>
    </p>
  </div>
</div>

<script>
function togglePw() {
  const input = document.getElementById('passwordInput');
  const icon  = document.getElementById('pwEyeIcon');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fas fa-eye'; }
}

async function submitAdminLogin(e) {
  e.preventDefault();
  const btn   = document.getElementById('loginBtn');
  const errEl = document.getElementById('loginError');
  const form  = e.target;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Signing in...';
  errEl.style.display = 'none';

  try {
    const res = await fetch('/admin/login', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(form)
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect || '/admin';
    } else {
      errEl.textContent = data.message || 'Invalid credentials.';
      errEl.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> Sign In';
    }
  } catch (err) {
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> Sign In';
  }
}
</script>
