<?php /* Admin Users View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-users" style="color:var(--primary);"></i> User Management</h1>
    <p class="page-subtitle">Manage accounts, balances, and access</p>
  </div>
</div>

<!-- Filters -->
<div class="admin-filter-card">
  <div>
    <label class="form-label">Search</label>
    <input type="text" class="form-control" id="searchInput" placeholder="Username, email, name..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>" onkeydown="if(event.key==='Enter') applyUserFilters()">
  </div>
  <div>
    <label class="form-label">Status</label>
    <select class="form-control" id="statusFilter" onchange="applyUserFilters()">
      <option value="">All</option>
      <?php foreach (['active', 'banned', 'suspended', 'deleted'] as $s): ?>
        <option value="<?= $s ?>" <?= (($filters['status'] ?? '') === $s ? 'selected' : '') ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="form-label">Role</label>
    <select class="form-control" id="roleFilter" onchange="applyUserFilters()">
      <option value="">All</option>
      <?php foreach (['user', 'admin', 'superadmin'] as $r): ?>
        <option value="<?= $r ?>" <?= (($filters['role'] ?? '') === $r ? 'selected' : '') ?>><?= ucfirst($r) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);padding-top:28px;">
    Total: <?= number_format($pagination['total']) ?> users
  </div>
</div>

<div class="admin-table-card">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-user-slash" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No users found.</p>
    </div>
  <?php else: ?>
    <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;gap:8px;">
      <button class="btn btn-sm btn-danger" onclick="bulkUserAction('ban')"><i class="fas fa-ban"></i> Ban Selected</button>
      <button class="btn btn-sm btn-success" onclick="bulkUserAction('unban')"><i class="fas fa-check"></i> Unban Selected</button>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th><input type="checkbox" onclick="toggleSelectAll(this,'userCheckbox')"></th>
            <th>User</th><th>Balance</th><th>Orders</th><th>Spent</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagination['data'] as $u): ?>
            <tr>
              <td><input type="checkbox" class="userCheckbox" value="<?= (int) $u['id'] ?>"></td>
              <td>
                <a href="/admin/users/<?= (int) $u['id'] ?>" style="text-decoration:none;color:inherit;">
                  <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($u['full_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['email']) ?></div>
                </a>
              </td>
              <td style="font-weight:700;color:var(--success);">$<?= number_format((float) $u['balance'], 2) ?></td>
              <td><?= (int) $u['total_orders'] ?></td>
              <td>$<?= number_format((float) $u['total_spent'], 2) ?></td>
              <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= ucfirst($u['role']) ?></span></td>
              <td><span class="badge badge-<?= $u['status'] === 'active' ? 'active' : 'cancelled' ?>"><?= ucfirst($u['status']) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:4px;">
                  <button class="btn btn-sm btn-ghost" title="Adjust Balance"
                          onclick="openAdjustBalance(<?= (int) $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', <?= (float) $u['balance'] ?>)">
                    <i class="fas fa-dollar-sign"></i>
                  </button>
                  <?php if ($u['status'] === 'active'): ?>
                    <button class="btn btn-sm btn-danger" title="Ban" onclick="banUser(<?= (int) $u['id'] ?>, this)"><i class="fas fa-ban"></i></button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-success" title="Unban" onclick="unbanUser(<?= (int) $u['id'] ?>, this)"><i class="fas fa-check"></i></button>
                  <?php endif; ?>
                  <?php if ($u['role'] === 'user'): ?>
                    <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteUser(<?= (int) $u['id'] ?>, this)"><i class="fas fa-trash"></i></button>
                  <?php endif; ?>
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

<!-- Balance Adjust Modal -->
<div class="modal-overlay" id="balanceModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-dollar-sign" style="color:var(--success);"></i> Adjust Balance</span>
      <button class="modal-close" onclick="closeModal('balanceModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="adjustUserId">
      <p style="color:var(--text-secondary);margin-bottom:16px;">
        User: <strong id="adjustUsername"></strong> · Current balance: <strong id="adjustCurrent"></strong>
      </p>
      <div class="form-group">
        <label class="form-label">Action</label>
        <select id="adjustType" class="form-control">
          <option value="add">Add Funds</option>
          <option value="deduct">Deduct Funds</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Amount ($)</label>
        <input type="number" id="adjustAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00">
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <textarea id="adjustReason" class="form-control" rows="2" placeholder="e.g. Manual top-up, refund adjustment..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('balanceModal')">Cancel</button>
      <button class="btn btn-success" onclick="submitBalanceAdjust(this)"><i class="fas fa-check"></i> Confirm</button>
    </div>
  </div>
</div>

<script>
function applyUserFilters() {
  const q = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const role = document.getElementById('roleFilter').value;
  window.location.href = '?q=' + encodeURIComponent(q) + '&status=' + status + '&role=' + role;
}

async function bulkUserAction(action) {
  const ids = getSelectedIds('userCheckbox');
  if (!ids.length) { showToast('Select at least one user.', 'error'); return; }
  confirmAction(`${action === 'ban' ? 'Ban' : 'Unban'} ${ids.length} selected user(s)?`, async () => {
    const data = await ajaxPost('/admin/users/bulk', { _csrf: getCsrfToken(), action, 'user_ids[]': ids });
    showToast(data.success ? data.message : data.message, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 800);
  });
}
</script>
