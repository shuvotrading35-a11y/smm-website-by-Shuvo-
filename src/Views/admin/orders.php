<?php /* Admin Orders View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-bag-shopping" style="color:var(--primary);"></i> Orders Management</h1>
    <p class="page-subtitle">Monitor and manage all customer orders</p>
  </div>
</div>

<div class="admin-filter-card">
  <div>
    <label class="form-label">Search (link or #ID)</label>
    <input type="text" class="form-control" id="searchInput" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" onkeydown="if(event.key==='Enter') applyOrderFilters()">
  </div>
  <div>
    <label class="form-label">Status</label>
    <select class="form-control" id="statusFilter" onchange="applyOrderFilters()">
      <option value="">All</option>
      <?php foreach (['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'error'] as $s): ?>
        <option value="<?= $s ?>" <?= (($filters['status'] ?? '') === $s ? 'selected' : '') ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);padding-top:28px;">
    Total: <?= number_format($pagination['total']) ?> orders
  </div>
</div>

<div class="admin-table-card">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-inbox" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No orders found.</p>
    </div>
  <?php else: ?>
    <div style="padding:10px 20px;border-bottom:1px solid var(--border);">
      <button class="btn btn-sm btn-ghost" onclick="resyncOrders(this)"><i class="fas fa-rotate"></i> Resync Selected</button>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="toggleSelectAll(this,'orderCheckbox')"></th>
            <th>#</th><th>User</th><th>Service</th><th>Qty</th><th>Charge</th><th>Progress</th><th>Status</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagination['data'] as $o): ?>
            <tr>
              <td><input type="checkbox" class="orderCheckbox" value="<?= (int) $o['id'] ?>"></td>
              <td>#<?= (int) $o['id'] ?><?= $o['api_order_id'] ? '<div style="font-size:0.7rem;color:var(--text-muted);">API #' . htmlspecialchars($o['api_order_id']) . '</div>' : '' ?></td>
              <td style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($o['username']) ?></td>
              <td style="max-width:180px;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.85rem;"><?= htmlspecialchars($o['service_name']) ?></div>
                <a href="<?= htmlspecialchars($o['link']) ?>" target="_blank" style="font-size:0.72rem;color:var(--primary);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;max-width:180px;"><?= htmlspecialchars($o['link']) ?></a>
              </td>
              <td><?= number_format((int) $o['quantity']) ?></td>
              <td style="font-weight:700;color:var(--success);">$<?= number_format((float) $o['charge'], 2) ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);">
                <?= $o['start_count'] !== null ? 'Start: ' . number_format((int) $o['start_count']) : '—' ?><br>
                <?= $o['remains'] !== null ? 'Remains: ' . number_format((int) $o['remains']) : '' ?>
              </td>
              <td>
                <select class="form-control" style="padding:4px 8px;font-size:0.78rem;width:auto;" onchange="updateOrderStatus(<?= (int) $o['id'] ?>, this)">
                  <?php foreach (['pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'error'] as $s): ?>
                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pagination['last_page'] > 1): ?>
      <div class="admin-pagination">
        <div class="page-info">Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= number_format($pagination['total']) ?></div>
        <div class="pagination">
          <?php if ($pagination['current_page'] > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p = max(1, $pagination['current_page'] - 2); $p <= min($pagination['last_page'], $pagination['current_page'] + 2); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="page-btn <?= $p === $pagination['current_page'] ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
function applyOrderFilters() {
  window.location.href = '?q=' + encodeURIComponent(document.getElementById('searchInput').value) + '&status=' + document.getElementById('statusFilter').value;
}
</script>
