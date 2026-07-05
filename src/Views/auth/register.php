<?php /* Register View */ ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:480px;">

    <div style="text-align:center;margin-bottom:36px;" data-aos="fade-down">
      <a href="/" style="display:inline-flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;box-shadow:0 0 40px rgba(124,92,255,0.5);">
          <i class="fas fa-bolt"></i>
        </div>
        <span style="font-family:var(--font-heading);font-weight:700;font-size:1.3rem;color:var(--text);">Shuvo SMM Panel</span>
      </a>
    </div>

    <div class="glass" style="padding:36px;" data-aos="fade-up">
      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.6rem;margin-bottom:6px;">Create your account</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:28px;">Free forever. No credit card required.</p>

      <div id="regError" style="display:none;padding:12px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.88rem;margin-bottom:20px;"></div>

      <form id="registerForm" onsubmit="submitRegister(event)">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-id-card"></i>
            <input type="text" name="full_name" class="form-control" placeholder="John Doe" required minlength="2">
          </div>
        </div>

        <div class="row g-3">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Username</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-at"></i>
                <input type="text" name="username" id="usernameInput" class="form-control"
                       placeholder="johndoe" required minlength="3" maxlength="20"
                       pattern="[a-zA-Z0-9_]+"
                       oninput="validateUsername(this)">
              </div>
              <div class="form-text" id="usernameHint">3–20 chars, letters/numbers/_</div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Email</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-envelope"></i>
                <input type="email" name="email" class="form-control" placeholder="you@email.com" required>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-lock"></i>
                <input type="password" name="password" id="regPassword" class="form-control"
                       placeholder="Min 8 chars" required minlength="8"
                       oninput="checkPwStrength(this.value)">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="input-icon-wrap">
                <i class="input-icon fas fa-lock"></i>
                <input type="password" name="confirm_password" class="form-control"
                       placeholder="Repeat password" required>
              </div>
            </div>
          </div>
        </div>

        <!-- Password strength bar -->
        <div style="margin-bottom:16px;margin-top:-8px;">
          <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
            <div id="pwStrengthBar" style="height:100%;width:0;border-radius:2px;transition:all 0.3s;background:var(--danger);"></div>
          </div>
          <div id="pwStrengthLabel" style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Referral Code <span style="font-weight:400;text-transform:none;font-size:0.8rem;color:var(--text-muted);">(optional)</span></label>
          <div class="input-icon-wrap">
            <i class="input-icon fas fa-gift"></i>
            <input type="text" name="referral_code" class="form-control"
                   placeholder="Enter referral code"
                   value="<?= htmlspecialchars($_GET['ref'] ?? '') ?>"
                   oninput="this.value=this.value.toUpperCase()">
          </div>
        </div>

        <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:20px;line-height:1.6;">
          By creating an account you agree to our
          <a href="/terms" style="color:var(--primary);">Terms of Service</a> and
          <a href="/privacy" style="color:var(--primary);">Privacy Policy</a>.
        </div>

        <button type="submit" class="btn btn-aurora w-100 btn-lg" id="regBtn">
          <i class="fas fa-rocket"></i> Create Account
        </button>
      </form>

      <div class="divider"></div>

      <p style="text-align:center;color:var(--text-secondary);font-size:0.9rem;">
        Already have an account?
        <a href="/login" style="color:var(--primary);font-weight:600;">Sign in</a>
      </p>
    </div>
  </div>
</div>

<script>
function validateUsername(input) {
  const val     = input.value;
  const valid   = /^[a-zA-Z0-9_]{3,20}$/.test(val);
  const hint    = document.getElementById('usernameHint');
  input.style.borderColor = val.length < 3 ? '' : (valid ? 'var(--success)' : 'var(--danger)');
  hint.style.color = valid ? 'var(--success)' : 'var(--text-muted)';
}

function checkPwStrength(pw) {
  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  const bar   = document.getElementById('pwStrengthBar');
  const label = document.getElementById('pwStrengthLabel');

  const levels = [
    { pct:'20%', color:'var(--danger)',  text:'Very weak'  },
    { pct:'40%', color:'var(--danger)',  text:'Weak'       },
    { pct:'60%', color:'var(--warning)', text:'Fair'       },
    { pct:'80%', color:'var(--primary)', text:'Good'       },
    { pct:'100%',color:'var(--success)', text:'Strong 💪'  },
  ];

  const lvl = levels[Math.min(score, 4)];
  bar.style.width      = lvl.pct;
  bar.style.background = lvl.color;
  label.textContent    = pw.length ? lvl.text : '';
  label.style.color    = lvl.color;
}

async function submitRegister(e) {
  e.preventDefault();
  const btn   = document.getElementById('regBtn');
  const errEl = document.getElementById('regError');
  const form  = e.target;

  const pw  = form.querySelector('[name=password]').value;
  const cpw = form.querySelector('[name=confirm_password]').value;

  if (pw !== cpw) {
    errEl.style.display = 'block';
    errEl.textContent   = 'Passwords do not match.';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Creating account...';
  errEl.style.display = 'none';

  const data = await fetch('/register', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(form),
  }).then(r => r.json());

  if (data.success) {
    btn.innerHTML = '<i class="fas fa-check"></i> Account Created!';
    window.location.href = data.redirect || '/register/verify';
  } else {
    if (data.errors) {
      const msgs = Object.values(data.errors).join(' ');
      errEl.textContent = msgs;
    } else {
      errEl.textContent = data.message || 'Registration failed.';
    }
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-rocket"></i> Create Account';
  }
}
</script>
