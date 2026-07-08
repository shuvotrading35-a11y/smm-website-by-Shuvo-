<?php /* Coupons View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-ticket" style="color:var(--primary);"></i> Coupons</h1>
    <p class="page-subtitle">Your coupon redemption history</p>
  </div>
</div>

<div class="glass-flat" style="padding:18px 20px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
  <i class="fas fa-circle-info" style="color:var(--primary);"></i>
  <span style="font-size:0.9rem;color:var(--text-secondary);">
    Have a coupon code? Apply it at checkout on the <a href="/dashboard/new-order" style="color:var(--primary);">New Order</a> page.
  </span>
</div>

<div class="glass-flat">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Redemption History</h5>
  </div>

  <?php if (empty($usedCoupons)): ?>
    <div style="padding:48px 24px;text-align:center;">
      <i class="fas fa-ticket" style="font-size:2.5rem;color:var(--text-secondary);opacity:0.5;"></i>
      <p style="margin:16px 0 0;color:var(--text-secondary);">You haven't used any coupons yet.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table" style="width:100%;margin:0;">
        <thead>
          <tr>
            <th style="padding:12px 20px;">Code</th>
            <th style="padding:12px 20px;">Type</th>
            <th style="padding:12px 20px;">Value</th>
            <th style="padding:12px 20px;">Discount Applied</th>
            <th style="padding:12px 20px;">Used On</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usedCoupons as $c): ?>
            <tr>
              <td style="padding:12px 20px;"><code style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($c['code']) ?></code></td>
              <td style="padding:12px 20px;"><?= ucfirst(str_replace('_', ' ', $c['type'])) ?></td>
              <td style="padding:12px 20px;">
                <?= $c['type'] === 'percent' ? number_format((float) $c['value'], 2) . '%' : '$' . number_format((float) $c['value'], 2) ?>
              </td>
              <td style="padding:12px 20px;font-weight:600;color:#00E676;">
                -$<?= number_format((float) ($c['discount'] ?? 0), 2) ?>
              </td>
              <td style="padding:12px 20px;color:var(--text-secondary);font-size:0.85rem;">
                <?= date('d M Y, h:i A', strtotime($c['used_at'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
