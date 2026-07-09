<?php /* Admin Services View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-layer-group" style="color:var(--primary);"></i> Services Management</h1>
    <p class="page-subtitle">
      <?php if ($lastSync): ?>
        Last synced <?= date('M d, Y H:i', strtotime($lastSync['synced_at'])) ?> · <?= (int) $lastSync['total_count'] ?> services (+<?= (int) $lastSync['added'] ?> / ~<?= (int) $lastSync['updated'] ?>)
      <?php else: ?>
        Never synced yet
      <?php endif; ?>
    </p>
  </div>
  <button class="btn btn-primary" onclick="syncServices(this)"><i class="fas fa-rotate"></i> Sync Services</button>
</div>

<div class="admin-filter-card">
  <div>
    <label class="form-label">Search</label>
    <input type="text" class="form-control" id="searchInput" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" onkeydown="if(event.key==='Enter') applyServiceFilters()">
  </div>
  <div>
    <label class="form-label">Status</label>
    <select class="form-control" id="activeFilter" onchange="applyServiceFilters()">
      <option value="">All</option>
      <option value="1" <?= (($_GET['active'] ?? '') === '1' ? 'selected' : '') ?>>Active</option>
      <option value="0" <?= (($_GET['active'] ?? '') === '0' ? 'selected' : '') ?>>Disabled</option>
    </select>
  </div>
  <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);padding-top:28px;">
    Total: <?= number_format($pagination['total']) ?> services
  </div>
</div>

<div class="admin-table-card">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-box-open" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No services found. Try syncing from your provider.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead><tr><th>ID</th><th>Service</th><th>Category</th><th>Rate /1000</th><th>Min/Max</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($pagination['data'] as $s): ?>
            <tr>
              <td style="font-size:0.78rem;color:var(--text-muted);">#<?= (int) $s['id'] ?></td>
              <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.85rem;"><?= htmlspecialchars($s['display_name']) ?></td>
              <td style="font-size:0.8rem;"><?= htmlspecialchars($s['category_name']) ?></td>
              <td style="font-weight:600;">$<?= number_format((float) $s['rate'], 4) ?>
                <div style="font-size:0.7rem;color:var(--text-muted);">markup: <?= $s['markup_type'] === 'percent' ? $s['markup_value'] . '%' : '$' . $s['markup_value'] ?></div>
              </td>
              <td style="font-size:0.78rem;color:var(--text-muted);"><?= number_format((int) $s['min_quantity']) ?> / <?= number_format((int) $s['max_quantity']) ?></td>
              <td><span class="badge badge-<?= $s['is_active'] ? 'active' : 'cancelled' ?>"><?= $s['is_active'] ? 'Active' : 'Disabled' ?></span></td>
              <td>
                <div style="display:flex;gap:4px;">
                  <button class="btn btn-sm btn-ghost" title="<?= $s['is_active'] ? 'Disable' : 'Enable' ?>" data-active="<?= (int) $s['is_active'] ?>" onclick="toggleService(<?= (int) $s['id'] ?>, this)">
                    <i class="fas fa-<?= $s['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                  </button>
                  <button class="btn btn-sm btn-ghost" title="Edit"
                          onclick="openServiceEdit(<?= (int) $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['display_name'])) ?>', <?= (float) $s['markup_value'] ?>, '<?= $s['markup_type'] ?>')">
                    <i class="fas fa-pen"></i>
                  </button>
                </div>
              </td>
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

<!-- Edit Service Modal -->
<div class="modal-overlay" id="serviceEditModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-pen" style="color:var(--primary);"></i> Edit Service</span>
      <button class="modal-close" onclick="closeModal('serviceEditModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editServiceId">
      <div class="form-group">
        <label class="form-label">Display Name</label>
        <input type="text" id="editServiceName" class="form-control">
      </div>
      <div class="row g-2">
        <div class="col-6">
          <label class="form-label">Markup Type</label>
          <select id="editMarkupType" class="form-control">
            <option value="percent">Percent (%)</option>
            <option value="fixed">Fixed ($)</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Markup Value</label>
          <input type="number" id="editMarkupValue" class="form-control" step="0.01">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('serviceEditModal')">Cancel</button>
      <button class="btn btn-primary" onclick="submitServiceEdit(this)"><i class="fas fa-check"></i> Save</button>
    </div>
  </div>
</div>

<script>
function applyServiceFilters() {
  window.location.href = '?q=' + encodeURIComponent(document.getElementById('searchInput').value) + '&active=' + document.getElementById('activeFilter').value;
}
</script>
