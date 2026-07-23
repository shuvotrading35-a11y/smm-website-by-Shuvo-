<?php /* Password Reset Link Invalid/Expired */ ?>

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:80px 16px;">
  <div style="width:100%;max-width:440px;text-align:center;">
    <div class="glass" style="padding:40px 32px;">
      <div style="width:64px;height:64px;border-radius:50%;background:var(--danger-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="fas fa-link-slash" style="font-size:1.6rem;color:var(--danger);"></i>
      </div>

      <h2 style="font-family:var(--font-heading);font-weight:700;font-size:1.4rem;margin-bottom:10px;">Link Expired</h2>
      <p style="color:var(--text-secondary);font-size:0.92rem;margin-bottom:28px;">
        This password reset link is invalid or has already expired. Reset links are only valid for a limited time and can be used once.
      </p>

      <a href="/forgot-password" class="btn btn-primary w-100 btn-lg"><i class="fas fa-key"></i> Request a New Link</a>
      <p style="margin-top:18px;">
        <a href="/login" style="color:var(--text-secondary);font-size:0.85rem;"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
      </p>
    </div>
  </div>
</div>
