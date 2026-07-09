<?php /* Admin Create Coupon View */ ?>

<div class="page-header admin-header">
  <div>
    <a href="/admin/coupons" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Coupons</a>
    <h1 class="page-title" style="margin-top:6px;"><i class="fas fa-ticket" style="color:var(--primary);"></i> New Coupon</h1>
  </div>
</div>

<div class="admin-table-card" style="padding:24px;max-width:600px;">
  <form id="couponForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="form-group">
      <label class="form-label">Coupon Code</label>
      <input type="text" name="code" class="form-control" style="text-transform:uppercase;" placeholder="SUMMER25" required maxlength="40">
    </div>

    <div class="row g-3">
      <div class="col-6">
        <label class="form-label">Discount Type</label>
        <select name="type" class="form-control">
          <option value="percent">Percentage (%)</option>
          <option value="fixed">Fixed Amount ($)</option>
        </select>
      </div>
      <div class="col-6">
        <label class="form-label">Value</label>
        <input type="number" name="value" class="form-control" step="0.01" min="0" required>
      </div>
      <div class="col-6">
        <label class="form-label">Min Order ($)</label>
        <input type="number" name="min_order" class="form-control" step="0.01" min="0" value="0">
      </div>
      <div class="col-6">
        <label class="form-label">Max Discount ($) <span style="font-weight:400;color:var(--text-muted);">(percent only)</span></label>
        <input type="number" name="max_discount" class="form-control" step="0.01" min="0">
      </div>
      <div class="col-6">
        <label class="form-label">Max Total Uses <span style="font-weight:400;color:var(--text-muted);">(blank = unlimited)</span></label>
        <input type="number" name="max_uses" class="form-control" min="1">
      </div>
      <div class="col-6">
        <label class="form-label">Uses Per User</label>
        <input type="number" name="uses_per_user" class="form-control" min="1" value="1">
      </div>
      <div class="col-12">
        <label class="form-label">Expires At <span style="font-weight:400;color:var(--text-muted);">(blank = never)</span></label>
        <input type="date" name="expires_at" class="form-control">
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100" style="margin-top:20px;" id="createCouponBtn"><i class="fas fa-plus"></i> Create Coupon</button>
  </form>
</div>

<script>
document.getElementById('couponForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('createCouponBtn');
  btn.disabled = true;

  const data = await fetch('/admin/coupons/create', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(this)
  }).then(r => r.json());

  if (data.success) {
    showToast(data.message, 'success');
    setTimeout(() => { window.location.href = data.redirect || '/admin/coupons'; }, 600);
  } else {
    showToast(data.message || 'Failed to create coupon.', 'error');
    btn.disabled = false;
  }
});
</script>
