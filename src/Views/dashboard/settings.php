<?php /* Account Settings View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-gear" style="color:var(--primary);"></i> Account Settings</h1>
    <p class="page-subtitle">Manage your profile, security, and account preferences</p>
  </div>
</div>

<div class="row g-3">
  <!-- Left column: Profile + Password -->
  <div class="col-12 col-lg-8">

    <!-- Profile Card -->
    <div class="glass-flat mb-3">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">
          <i class="fas fa-user" style="color:var(--primary);"></i> Profile Information
        </h5>
      </div>
      <form id="profileForm" style="padding:20px;">
        <input type="hidden" name="action" value="profile">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required minlength="2">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
          </div>
          <div class="col-12">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:16px;">
          <i class="fas fa-floppy-disk"></i> Save Changes
        </button>
      </form>
    </div>

    <!-- Password Card -->
    <div class="glass-flat">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">
          <i class="fas fa-lock" style="color:var(--primary);"></i> Change Password
        </h5>
      </div>
      <form id="passwordForm" style="padding:20px;">
        <input type="hidden" name="action" value="password">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8" autocomplete="new-password">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:8px;">
          Minimum 8 characters, must include an uppercase letter and a number.
        </p>

        <button type="submit" class="btn btn-primary" style="margin-top:8px;">
          <i class="fas fa-key"></i> Update Password
        </button>
      </form>
    </div>

  </div>

  <!-- Right column: Avatar + Account Info -->
  <div class="col-12 col-lg-4">

    <!-- Avatar Card -->
    <div class="glass-flat mb-3" style="padding:20px;text-align:center;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 16px;">
        <i class="fas fa-image" style="color:var(--primary);"></i> Profile Photo
      </h5>
      <img id="avatarPreview" src="<?= htmlspecialchars($user['avatar'] ?? '/assets/img/default-avatar.svg') ?>"
           style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid var(--border);margin-bottom:14px;" alt="Avatar">
      <form id="avatarForm">
        <input type="hidden" name="action" value="avatar">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
        <button type="button" class="btn btn-outline" style="width:100%;" onclick="document.getElementById('avatarInput').click()">
          <i class="fas fa-upload"></i> Upload New Photo
        </button>
      </form>
      <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:10px;margin-bottom:0;">
        JPEG, PNG or WebP. Max 2MB.
      </p>
    </div>

    <!-- Account Info Card -->
    <div class="glass-flat" style="padding:20px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 14px;">
        <i class="fas fa-circle-info" style="color:var(--primary);"></i> Account Info
      </h5>

      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
        <span style="color:var(--text-secondary);font-size:0.85rem;">Status</span>
        <span class="badge badge-<?= htmlspecialchars($user['status'] ?? 'active') ?>"><?= ucfirst($user['status'] ?? 'active') ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
        <span style="color:var(--text-secondary);font-size:0.85rem;">Role</span>
        <span style="font-weight:600;"><?= ucfirst($user['role'] ?? 'user') ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
        <span style="color:var(--text-secondary);font-size:0.85rem;">Member Since</span>
        <span style="font-weight:600;"><?= !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—' ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;">
        <span style="color:var(--text-secondary);font-size:0.85rem;">Referral Code</span>
        <span style="display:flex;align-items:center;gap:6px;">
          <code style="font-weight:600;"><?= htmlspecialchars($user['referral_code'] ?? '') ?></code>
          <button type="button" onclick="copyReferral()" class="btn btn-ghost btn-icon" style="width:auto;height:auto;padding:2px 6px;" title="Copy">
            <i class="fas fa-copy"></i>
          </button>
        </span>
      </div>
    </div>

  </div>
</div>

<script>
function submitSettingsForm(formEl, successMsg) {
  const formData = new FormData(formEl);
  const btn = formEl.querySelector('button[type="submit"]');
  const originalHtml = btn ? btn.innerHTML : null;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Saving...'; }

  fetch('/dashboard/settings', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    showToast(data.message || (data.success ? successMsg : 'Something went wrong.'), data.success ? 'success' : 'error');
    if (data.success && formEl.id === 'passwordForm') formEl.reset();
  })
  .catch(() => showToast('Network error. Please try again.', 'error'))
  .finally(() => { if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; } });
}

document.getElementById('profileForm').addEventListener('submit', function (e) {
  e.preventDefault();
  submitSettingsForm(this, 'Profile updated successfully.');
});

document.getElementById('passwordForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const newPass = this.querySelector('[name="new_password"]').value;
  const confirmPass = this.querySelector('[name="confirm_password"]').value;
  if (newPass !== confirmPass) { showToast('Passwords do not match.', 'error'); return; }
  submitSettingsForm(this, 'Password changed successfully.');
});

document.getElementById('avatarInput').addEventListener('change', function () {
  if (!this.files || !this.files[0]) return;

  if (this.files[0].size > 2 * 1024 * 1024) { showToast('File must be under 2MB.', 'error'); return; }

  const formData = new FormData(document.getElementById('avatarForm'));

  fetch('/dashboard/settings', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('avatarPreview').src = data.avatar + '?t=' + Date.now();
      showToast('Photo updated successfully.', 'success');
    } else {
      showToast(data.message, 'error');
    }
  })
  .catch(() => showToast('Upload failed. Please try again.', 'error'));
});

function copyReferral() {
  const code = <?= json_encode($user['referral_code'] ?? '') ?>;
  navigator.clipboard.writeText(code).then(() => showToast('Referral code copied!', 'success'));
}
</script>
