<?php /* Reset Password View */ ?>

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
      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:6px;">Set New Password</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:24px;">Choose a strong password for your account.</p>

      <div id="errMsg" style="display:none;padding:12px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.88rem;margin-bottom:20px;"></div>

      <form id="resetForm" onsubmit="submitReset(event)">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

        <div class="form-group">
          <label class="form-label">New Password</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" name="password" id="newPw" class="form-control"
                   placeholder="Min 8 chars, 1 uppercase, 1 number"
                   required minlength="8"
                   oninput="checkPwStrength(this.value)">
          </div>
          <div style="margin-top:8px;">
            <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
              <div id="pwBar" style="height:100%;width:0;border-radius:2px;transition:all 0.3s;background:var(--danger);"></div>
            </div>
            <div id="pwLabel" style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;"></div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-lock"></i>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-aurora w-100 btn-lg" id="resetBtn">
          <i class="fas fa-shield-check"></i> Update Password
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function checkPwStrength(pw) {
  let s = 0;
  if (pw.length >= 8)  s++;
  if (pw.length >= 12) s++;
  if (/[A-Z]/.test(pw)) s++;
  if (/[0-9]/.test(pw)) s++;
  if (/[^A-Za-z0-9]/.test(pw)) s++;
  const levels = [
    {pct:'20%',color:'var(--danger)',text:'Very weak'},
    {pct:'40%',color:'var(--danger)',text:'Weak'},
    {pct:'60%',color:'var(--warning)',text:'Fair'},
    {pct:'80%',color:'var(--primary)',text:'Good'},
    {pct:'100%',color:'var(--success)',text:'Strong 💪'},
  ];
  const l = levels[Math.min(s,4)];
  const bar = document.getElementById('pwBar');
  bar.style.width = l.pct; bar.style.background = l.color;
  const lbl = document.getElementById('pwLabel');
  lbl.textContent = pw.length ? l.text : '';
  lbl.style.color = l.color;
}

async function submitReset(e) {
  e.preventDefault();
  const btn   = document.getElementById('resetBtn');
  const errEl = document.getElementById('errMsg');
  const form  = e.target;
  const pw    = form.querySelector('[name=password]').value;
  const cpw   = form.querySelector('[name=confirm_password]').value;

  if (pw !== cpw) {
    errEl.style.display = 'block';
    errEl.textContent = 'Passwords do not match.';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Updating...';
  errEl.style.display = 'none';

  const token = form.querySelector('[name=token]').value;
  const data  = await fetch('/reset-password/' + token, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(form),
  }).then(r => r.json());

  if (data.success) {
    btn.innerHTML = '<i class="fas fa-check"></i> Password Updated!';
    showToast('Password changed successfully. Redirecting…', 'success');
    setTimeout(() => window.location.href = data.redirect || '/login', 1500);
  } else {
    errEl.style.display = 'block';
    errEl.textContent   = data.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-shield-check"></i> Update Password';
  }
}
</script>
