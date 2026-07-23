<?php /* Order Detail View */ ?>

<div class="page-header">
  <div>
    <a href="/dashboard/orders" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Orders</a>
    <h1 class="page-title" style="margin-top:6px;">Order #<?= (int) $order['id'] ?></h1>
    <p class="page-subtitle"><?= htmlspecialchars($order['category']) ?> · <?= htmlspecialchars($order['service_name']) ?></p>
  </div>
  <span class="badge badge-<?= $order['status'] ?>" style="font-size:0.85rem;padding:6px 14px;"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(124,92,255,0.12);color:var(--primary);"><i class="fas fa-hashtag"></i></div>
      <div class="stat-value"><?= number_format((int) $order['quantity']) ?></div>
      <div class="stat-label">Quantity</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,230,118,0.12);color:#00E676;"><i class="fas fa-dollar-sign"></i></div>
      <div class="stat-value">$<?= number_format((float) $order['charge'], 2) ?></div>
      <div class="stat-label">Charge</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,212,255,0.12);color:#00D4FF;"><i class="fas fa-play"></i></div>
      <div class="stat-value"><?= $order['start_count'] !== null ? number_format((int) $order['start_count']) : '—' ?></div>
      <div class="stat-label">Start Count</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(255,214,0,0.12);color:#FFD600;"><i class="fas fa-hourglass-half"></i></div>
      <div class="stat-value"><?= $order['remains'] !== null ? number_format((int) $order['remains']) : '—' ?></div>
      <div class="stat-label">Remaining</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="glass-flat" style="padding:20px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 14px;">Order Details</h5>
      <div style="font-size:0.9rem;">
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--text-secondary);">Link</span>
          <a href="<?= htmlspecialchars($order['link']) ?>" target="_blank" style="color:var(--primary);max-width:60%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($order['link']) ?></a>
        </div>
        <?php if (!empty($order['api_order_id'])): ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--text-secondary);">Provider Order ID</span><strong>#<?= htmlspecialchars($order['api_order_id']) ?></strong>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--text-secondary);">Placed On</span><strong><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></strong>
        </div>
      </div>

      <?php if ($order['can_refill'] || $order['can_cancel']): ?>
        <div style="display:flex;gap:10px;margin-top:18px;">
          <?php if ($order['can_refill'] && in_array($order['status'], ['completed', 'partial'], true)): ?>
            <button class="btn btn-outline" onclick="requestRefill(<?= (int) $order['id'] ?>, this)"><i class="fas fa-rotate"></i> Request Refill</button>
          <?php endif; ?>
          <?php if ($order['can_cancel'] && in_array($order['status'], ['pending', 'processing'], true)): ?>
            <button class="btn btn-danger" onclick="cancelOrder(<?= (int) $order['id'] ?>, this)"><i class="fas fa-times"></i> Cancel Order</button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="glass-flat" style="padding:20px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 14px;">Status History</h5>
      <?php if (empty($logs)): ?>
        <p style="color:var(--text-secondary);font-size:0.85rem;">No status changes yet.</p>
      <?php else: ?>
        <div style="position:relative;padding-left:20px;">
          <?php foreach ($logs as $i => $l): ?>
            <div style="position:relative;padding-bottom:<?= $i === count($logs) - 1 ? '0' : '18px' ?>;">
              <?php if ($i !== count($logs) - 1): ?>
                <div style="position:absolute;left:-16px;top:14px;bottom:-4px;width:2px;background:var(--border);"></div>
              <?php endif; ?>
              <div style="position:absolute;left:-20px;top:2px;width:9px;height:9px;border-radius:50%;background:var(--primary);"></div>
              <div style="font-size:0.85rem;font-weight:600;"><?= ucfirst(str_replace('_', ' ', $l['new_status'])) ?></div>
              <?php if (!empty($l['message'])): ?>
                <div style="font-size:0.78rem;color:var(--text-secondary);"><?= htmlspecialchars($l['message']) ?></div>
              <?php endif; ?>
              <div style="font-size:0.72rem;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($l['created_at'])) ?> · <?= ucfirst($l['source']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function requestRefill(orderId, btn) {
  btn.disabled = true;
  const fd = new FormData();
  fd.append('_csrf', <?= json_encode($csrf) ?>);
  fetch(`/orders/${orderId}/refill`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json())
    .then(data => {
      showToast(data.message || (data.success ? 'Refill requested.' : 'Refill request failed.'), data.success ? 'success' : 'error');
      btn.disabled = false;
    })
    .catch(() => { showToast('Network error.', 'error'); btn.disabled = false; });
}

function cancelOrder(orderId, btn) {
  if (!confirm('Cancel this order? This cannot be undone.')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('_csrf', <?= json_encode($csrf) ?>);
  fetch(`/orders/${orderId}/cancel`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
    .then(r => r.json())
    .then(data => {
      showToast(data.message || (data.success ? 'Order cancelled.' : 'Cancel failed.'), data.success ? 'success' : 'error');
      if (data.success) setTimeout(() => location.reload(), 800);
      else btn.disabled = false;
    })
    .catch(() => { showToast('Network error.', 'error'); btn.disabled = false; });
}
</script>
