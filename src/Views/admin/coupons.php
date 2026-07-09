<?php /* Admin Coupons View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-ticket" style="color:var(--primary);"></i> Coupons</h1>
    <p class="page-subtitle">Create and manage discount codes</p>
  </div>
  <a href="/admin/coupons/create" class="btn btn-primary"><i class="fas fa-plus"></i> New Coupon</a>
</div>

<div class="admin-table-card">
  <?php if (empty($coupons)): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-ticket" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No coupons yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Uses</th><th>Expires</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
            <tr>
              <td><code style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($c['code']) ?></code></td>
              <td><?= ucfirst($c['type']) ?></td>
              <td style="font-weight:600;"><?= $c['type'] === 'percent' ? number_format((float) $c['value'], 2) . '%' : '$' . number_format((float) $c['value'], 2) ?></td>
              <td>$<?= number_format((float) $c['min_order'], 2) ?></td>
              <td><?= (int) $c['usage_count'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
              <td style="font-size:0.8rem;color:var(--text-muted);"><?= $c['expires_at'] ? date('M d, Y', strtotime($c['expires_at'])) : 'Never' ?></td>
              <td>
                <span class="badge badge-<?= $c['is_active'] ? 'active' : 'cancelled' ?>" style="cursor:pointer;" onclick="toggleCoupon(<?= (int) $c['id'] ?>, this)">
                  <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
