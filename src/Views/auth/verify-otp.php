<?php /* OTP Verify View */ ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:420px;">

    <div style="text-align:center;margin-bottom:36px;" data-aos="fade-down">
      <a href="/" style="display:inline-flex;flex-direction:column;align-items:center;gap:12px;">
        <div style="width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;box-shadow:0 0 40px rgba(124,92,255,0.5);">
          <i class="fas fa-bolt"></i>
        </div>
      </a>
    </div>

    <div class="glass" style="padding:36px;text-align:center;" data-aos="fade-up">
      <div style="width:64px;height:64px;border-radius:50%;background:var(--primary-light);border:2px solid rgba(124,92,255,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.6rem;color:var(--primary);">
        <i class="fas fa-envelope"></i>
      </div>

      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.5rem;margin-bottom:8px;">Check your email</h2>
      <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:6px;">We sent a 6-digit code to</p>
      <p style="color:var(--primary);font-weight:600;font-size:0.95rem;margin-bottom:0;"><?= htmlspecialchars($email ?? '') ?></p>

      <div id="otpError" style="display:none;padding:10px 16px;background:var(--danger-bg);border:1px solid rgba(255,75,85,0.3);border-radius:var(--radius);color:var(--danger);font-size:0.85rem;margin:16px 0;"></div>

      <!-- OTP Input Grid -->
      <div class="otp-inputs" id="otpInputs">
        <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" class="otp-input" id="otp<?= $i ?>"
               maxlength="1" inputmode="numeric" pattern="[0-9]"
               onkeydown="otpKeyDown(event, <?= $i ?>)"
               oninput="otpInput(event, <?= $i ?>)"
               onpaste="otpPaste(event)">
        <?php endfor; ?>
      </div>

      <button type="button" class="btn btn-aurora w-100 btn-lg" id="verifyBtn" onclick="submitOtp()" disabled>
        <i class="fas fa-shield-check"></i> Verify Email
      </button>

      <!-- Resend -->
      <div style="margin-top:20px;font-size:0.88rem;color:var(--text-muted);">
        Didn't receive the code?
        <button id="resendBtn" onclick="resendOtp()" style="background:none;border:none;color:var(--primary);font-weight:600;cursor:pointer;padding:0;font-size:0.88rem;" disabled>
          Resend in <span id="resendTimer">60</span>s
        </button>
      </div>
    </div>

    <p style="text-align:center;margin-top:20px;font-size:0.85rem;color:var(--text-muted);">
      <a href="/register" style="color:var(--primary);">← Back to Register</a>
    </p>
  </div>
</div>

<script>
let resendSeconds = 60;

// Auto-start resend countdown
const resendTimer = setInterval(() => {
  resendSeconds--;
  const el = document.getElementById('resendTimer');
  if (el) el.textContent = resendSeconds;

  if (resendSeconds <= 0) {
    clearInterval(resendTimer);
    const btn = document.getElementById('resendBtn');
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Resend code';
    }
  }
}, 1000);

// Focus first input on load
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('otp0')?.focus();
});

function getOtpValue() {
  return Array.from({length: 6}, (_, i) => document.getElementById('otp' + i)?.value || '').join('');
}

function checkOtpComplete() {
  const complete = getOtpValue().length === 6;
  document.getElementById('verifyBtn').disabled = !complete;
}

function otpInput(e, index) {
  const input = e.target;
  const val   = input.value.replace(/\D/g, '');
  input.value = val;

  if (val) {
    input.classList.add('filled');
    if (index < 5) {
      document.getElementById('otp' + (index + 1))?.focus();
    }
  } else {
    input.classList.remove('filled');
  }
  checkOtpComplete();
}

function otpKeyDown(e, index) {
  if (e.key === 'Backspace' && !e.target.value && index > 0) {
    const prev = document.getElementById('otp' + (index - 1));
    if (prev) { prev.value = ''; prev.classList.remove('filled'); prev.focus(); }
    checkOtpComplete();
  }
  if (e.key === 'ArrowLeft' && index > 0) {
    document.getElementById('otp' + (index - 1))?.focus();
  }
  if (e.key === 'ArrowRight' && index < 5) {
    document.getElementById('otp' + (index + 1))?.focus();
  }
}

function otpPaste(e) {
  e.preventDefault();
  const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
  pasted.split('').forEach((char, i) => {
    const input = document.getElementById('otp' + i);
    if (input) { input.value = char; input.classList.add('filled'); }
  });
  document.getElementById('otp' + Math.min(pasted.length, 5))?.focus();
  checkOtpComplete();
}

async function submitOtp() {
  const otp   = getOtpValue();
  const btn   = document.getElementById('verifyBtn');
  const errEl = document.getElementById('otpError');

  if (otp.length !== 6) return;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Verifying...';
  errEl.style.display = 'none';

  const data = await fetch('/register/verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: 'otp=' + encodeURIComponent(otp)
  }).then(r => r.json());

  if (data.success) {
    btn.innerHTML = '<i class="fas fa-check"></i> Verified!';
    window.location.href = data.redirect || '/dashboard';
  } else {
    errEl.style.display = 'block';
    errEl.textContent   = data.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-shield-check"></i> Verify Email';
    // Shake + clear inputs on wrong OTP
    document.getElementById('otpInputs').style.animation = 'none';
    setTimeout(() => {
      Array.from({length: 6}, (_, i) => {
        const el = document.getElementById('otp' + i);
        if (el) { el.value = ''; el.classList.remove('filled'); }
      });
      document.getElementById('otp0')?.focus();
    }, 300);
  }
}

async function resendOtp() {
  const btn = document.getElementById('resendBtn');
  btn.disabled = true;
  btn.textContent = 'Sending...';
  // POST to resend endpoint - simplified
  await fetch('/register/verify', { method: 'POST', body: 'resend=1', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } });
  btn.textContent = 'Resend in 60s';
  resendSeconds = 60;
  document.getElementById('resendTimer') && (document.getElementById('resendBtn').innerHTML = 'Resend in <span id="resendTimer">60</span>s');
}
</script>
