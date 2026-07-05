<?php /* Forgot Password View */ ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:420px;">

    <div style="text-align:center;margin-bottom:36px;" data-aos="fade-down">
      <a href="/" style="display:inline-flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;box-shadow:0 0 40px rgba(124,92,255,0.5);">
          <i class="fas fa-bolt"></i>
        </div>
      </a>
    </div>

    <div class="glass" style="padding:36px;" data-aos="fade-up">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--warning-bg);border:2px solid rgba(255,214,0,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:var(--warning);">
          <i class="fas fa-key"></i>
        </div>
        <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:8px;">Forgot Password?</h2>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Enter your email and we'll send a reset link.</p>
      </div>

      <div id="successMsg" style="display:none;padding:14px 16px;background:var(--success-bg);border:1px solid rgba(0,230,118,0.3);border-radius:var(--radius);color:var(--success);font-size:0.88rem;margin-bottom:20px;text-align:center;">
        <i class="fas fa-check-circle"></i> Reset link sent! Check your inbox.
      </div>

      <div id="errorMsg" style="display:none;padding:12px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.88rem;margin-bottom:20px;"></div>

      <form id="forgotForm" onsubmit="submitForgot(event)">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="you@email.com" required autocomplete="email">
          </div>
        </div>

        <button type="submit" class="btn btn-aurora w-100 btn-lg" id="submitBtn">
          <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
      </form>

      <div class="divider"></div>
      <p style="text-align:center;font-size:0.88rem;color:var(--text-secondary);">
        Remember your password? <a href="/login" style="color:var(--primary);font-weight:600;">Sign in</a>
      </p>
    </div>
  </div>
</div>

<script>
async function submitForgot(e) {
  e.preventDefault();
  const btn    = document.getElementById('submitBtn');
  const errEl  = document.getElementById('errorMsg');
  const succEl = document.getElementById('successMsg');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Sending...';
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  const data = await fetch('/forgot-password', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(e.target),
  }).then(r => r.json());

  if (data.success) {
    succEl.style.display = 'block';
    btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
  } else {
    errEl.style.display  = 'block';
    errEl.textContent    = data.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reset Link';
  }
}
</script>
