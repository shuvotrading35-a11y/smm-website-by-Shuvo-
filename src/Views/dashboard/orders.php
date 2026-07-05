<?php /* Orders List View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-box" style="color:var(--primary);"></i> My Orders</h1>
    <p class="page-subtitle">Track all your orders in real time</p>
  </div>
  <a href="/dashboard/new-order" class="btn btn-primary">
    <i class="fas fa-cart-plus"></i> New Order
  </a>
</div>

<!-- Filters -->
<div class="glass-flat mb-4" style="padding:16px 20px;">
  <form id="filterForm" class="d-flex gap-3 flex-wrap align-items-end">
    <div>
      <label class="form-label">Status</label>
      <select class="form-control" name="status" style="width:auto;">
        <option value="">All Statuses</option>
        <?php foreach (['pending','processing','in_progress','completed','partial','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= (($_GET['status']??'') === $s ? 'selected' : '') ?>>
          <?= ucfirst(str_replace('_',' ',$s)) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label">From</label>
      <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
    </div>
    <div>
      <label class="form-label">To</label>
      <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
    </div>
    <div style="flex:1;min-width:180px;">
      <label class="form-label">Search</label>
      <div class="input-icon-wrap">
        <i class="input-icon fas fa-search"></i>
        <input type="text" class="form-control" name="search" placeholder="Order ID or link…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      </div>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-filter"></i> Filter</button>
      <button type="button" class="btn btn-ghost" onclick="clearFilters()"><i class="fas fa-times"></i></button>
    </div>
  </form>
</div>

<!-- Orders Table -->
<div class="glass-flat">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:80px;text-align:center;">
      <i class="fas fa-box-open" style="font-size:3.5rem;color:var(--text-muted);opacity:0.4;"></i>
      <p style="color:var(--text-muted);margin-top:16px;font-size:1rem;">No orders found.</p>
      <a href="/dashboard/new-order" class="btn btn-primary mt-3">Place Your First Order</a>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Service</th>
            <th>Link</th>
            <th style="text-align:right;">Qty</th>
            <th style="text-align:right;">Charge</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagination['data'] as $order): ?>
          <?php
            $progress = 0;
            if ($order['start_count'] !== null && $order['quantity'] > 0) {
              $delivered = max(0, (int)$order['quantity'] - (int)($order['remains'] ?? $order['quantity']));
              $progress  = min(100, round($delivered / $order['quantity'] * 100));
            }
            if ($order['status'] === 'completed') $progress = 100;
          ?>
          <tr>
            <td>
              <span style="color:var(--primary);font-weight:700;font-family:var(--font-heading);">#<?= $order['id'] ?></span>
              <?php if ($order['api_order_id']): ?>
              <div style="font-size:0.72rem;color:var(--text-muted);">API: <?= htmlspecialchars($order['api_order_id']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;max-width:220px;">
                <i class="<?= htmlspecialchars($order['icon'] ?? 'fas fa-globe') ?>" style="color:var(--primary);flex-shrink:0;"></i>
                <div style="overflow:hidden;">
                  <div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($order['service_name']) ?>
                  </div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($order['category'] ?? '') ?></div>
                </div>
              </div>
            </td>
            <td>
              <a href="<?= htmlspecialchars($order['link']) ?>" target="_blank" class="table-link"
                 title="<?= htmlspecialchars($order['link']) ?>">
                <?= htmlspecialchars(parse_url($order['link'], PHP_URL_HOST) ?: substr($order['link'], 0, 30)) ?>
              </a>
            </td>
            <td style="text-align:right;font-weight:600;"><?= number_format((int)$order['quantity']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--text);">$<?= number_format((float)$order['charge'], 4) ?></td>
            <td>
              <span class="badge badge-<?= $order['status'] ?>">
                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
              </span>
            </td>
            <td style="min-width:100px;">
              <?php if ($progress > 0 || $order['status'] === 'in_progress'): ?>
              <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-bottom:3px;">
                <div style="height:100%;width:<?= $progress ?>%;background:var(--grad-primary);border-radius:3px;transition:width 0.5s;"></div>
              </div>
              <div style="font-size:0.72rem;color:var(--text-muted);"><?= $progress ?>% — <?= number_format((int)($order['remains'] ?? 0)) ?> left</div>
              <?php else: ?>
              <span style="color:var(--text-muted);font-size:0.82rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap;">
              <?= date('M d, Y', strtotime($order['created_at'])) ?>
              <div style="font-size:0.72rem;"><?= date('H:i', strtotime($order['created_at'])) ?></div>
            </td>
            <td>
              <div class="table-actions">
                <a href="/dashboard/orders/<?= $order['id'] ?>" class="action-btn view" title="View Details">
                  <i class="fas fa-eye"></i>
                </a>
                <button class="action-btn"
                        onclick="toggleFavorite(<?= $order['service_id'] ?>, this)"
                        title="Add to Favorites">
                  <i class="<?= in_array($order['service_id'], []) ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;border-top:1px solid var(--border);">
      <div style="font-size:0.85rem;color:var(--text-muted);">
        Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= number_format($pagination['total']) ?> orders
      </div>
      <div class="pagination">
        <?php if ($pagination['current_page'] > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="page-btn">
          <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>

        <?php for ($p = max(1, $pagination['current_page'] - 2); $p <= min($pagination['last_page'], $pagination['current_page'] + 2); $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
           class="page-btn <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
          <?= $p ?>
        </a>
        <?php endfor; ?>

        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="page-btn">
          <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
