<?php /* Order Status View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-magnifying-glass" style="color:var(--primary);"></i> Order Status</h1>
    <p class="page-subtitle">Check the real-time status of any of your orders</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="glass-flat p-4">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:20px;">
        <i class="fas fa-search" style="color:var(--secondary);"></i> Check Orders
      </h5>

      <div class="form-group">
        <label class="form-label">Order ID(s)</label>
        <textarea class="form-control" id="statusOrderIds" rows="4"
                  placeholder="Enter order ID(s), one per line or comma-separated&#10;e.g. 1234, 1235, 1236"></textarea>
        <div class="form-text">You can check up to 100 orders at once.</div>
      </div>

      <button class="btn btn-primary w-100 btn-lg" onclick="checkOrderStatus(this)">
        <i class="fas fa-search"></i> Check Status
      </button>

      <!-- Quick info boxes -->
      <div style="margin-top:24px;">
        <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:12px;">Status Meanings</div>
        <?php
        $statusInfo = [
          ['pending',     '#FFD600', 'Order queued, starting soon'],
          ['processing',  '#00D4FF', 'Order submitted to provider'],
          ['in_progress', '#00D4FF', 'Delivery actively in progress'],
          ['completed',   '#00E676', 'Order fully delivered'],
          ['partial',     '#aaaaaa', 'Partially delivered, stopped'],
          ['cancelled',   '#FF4B55', 'Order cancelled'],
        ];
        foreach ($statusInfo as $s): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);">
          <span class="badge badge-<?= $s[0] ?>"><?= ucfirst(str_replace('_',' ',$s[0])) ?></span>
          <span style="font-size:0.82rem;color:var(--text-secondary);"><?= $s[2] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div id="statusResults" style="display:none;"></div>

    <!-- Recent orders shortcut -->
    <div class="glass-flat p-4">
      <h6 style="font-family:var(--font-heading);font-weight:700;margin-bottom:16px;color:var(--text-muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.06em;">
        Recent Orders
      </h6>
      <p style="color:var(--text-secondary);font-size:0.88rem;margin-bottom:16px;">
        Click an order ID below to check it instantly.
      </p>
      <?php
      $db = \SMMPanel\Core\Database::getInstance();
      $recent = $db->fetchAll(
        'SELECT o.id, o.status, o.quantity, s.name AS service_name
         FROM smmPanel_orders o
         JOIN smmPanel_services s ON s.id = o.service_id
         WHERE o.user_id = ?
         ORDER BY o.created_at DESC LIMIT 10',
        [$_SESSION['user_id'] ?? 0]
      );
      ?>
      <?php if (empty($recent)): ?>
        <div style="text-align:center;padding:30px;color:var(--text-muted);">No orders yet.</div>
      <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php foreach ($recent as $o): ?>
          <button class="btn btn-ghost btn-sm"
                  onclick="document.getElementById('statusOrderIds').value='<?= $o['id'] ?>';checkOrderStatus(document.querySelector('.btn-primary'));"
                  title="<?= htmlspecialchars($o['service_name']) ?>">
            #<?= $o['id'] ?>
            <span class="badge badge-<?= $o['status'] ?>" style="padding:2px 6px;font-size:0.7rem;margin-left:4px;">
              <?= ucfirst(str_replace('_',' ',$o['status'])) ?>
            </span>
          </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
