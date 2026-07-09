<?php /* Admin View User */ ?>

<div class="page-header admin-header">
  <div>
    <a href="/admin/users" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Users</a>
    <h1 class="page-title" style="margin-top:6px;"><?= htmlspecialchars($viewUser['full_name']) ?></h1>
    <p class="page-subtitle">@<?= htmlspecialchars($viewUser['username']) ?> · <?= htmlspecialchars($viewUser['email']) ?></p>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,230,118,0.12);color:#00E676;"><i class="fas fa-wallet"></i></div>
      <div class="stat-value">$<?= number_format((float) $viewUser['balance'], 2) ?></div>
      <div class="stat-label">Balance</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(124,92,255,0.12);color:var(--primary);"><i class="fas fa-bag-shopping"></i></div>
      <div class="stat-value"><?= (int) $viewUser['total_orders'] ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(255,214,0,0.12);color:#FFD600;"><i class="fas fa-sack-dollar"></i></div>
      <div class="stat-value">$<?= number_format((float) $viewUser['total_spent'], 2) ?></div>
      <div class="stat-label">Total Spent</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,212,255,0.12);color:#00D4FF;"><i class="fas fa-calendar"></i></div>
      <div class="stat-value" style="font-size:1rem;"><?= date('d M Y', strtotime($viewUser['created_at'])) ?></div>
      <div class="stat-label">Member Since</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="admin-table-card" style="padding:20px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 16px;">Account</h5>
      <form id="editUserForm">
        <input type="hidden" id="editUserId" value="<?= (int) $viewUser['id'] ?>">
        <input type="hidden" id="editCsrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" id="editFullName" class="form-control" value="<?= htmlspecialchars($viewUser['full_name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="editEmail" class="form-control" value="<?= htmlspecialchars($viewUser['email']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select id="editRole" class="form-control">
            <?php foreach (['user', 'admin', 'superadmin'] as $r): ?>
              <option value="<?= $r ?>" <?= $viewUser['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select id="editStatus" class="form-control">
            <?php foreach (['active', 'banned', 'suspended'] as $s): ?>
              <option value="<?= $s ?>" <?= $viewUser['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="button" class="btn btn-primary w-100" onclick="saveUserEdit(this)"><i class="fas fa-floppy-disk"></i> Save Changes</button>
      </form>

      <div style="display:flex;gap:8px;margin-top:12px;">
        <button class="btn btn-sm btn-ghost" style="flex:1;" onclick="openAdjustBalance(<?= (int) $viewUser['id'] ?>, '<?= htmlspecialchars(addslashes($viewUser['username'])) ?>', <?= (float) $viewUser['balance'] ?>)">
          <i class="fas fa-dollar-sign"></i> Balance
        </button>
        <?php if ($viewUser['role'] === 'user'): ?>
          <button class="btn btn-sm btn-danger" style="flex:1;" onclick="deleteUser(<?= (int) $viewUser['id'] ?>, this)"><i class="fas fa-trash"></i> Delete</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="admin-table-card mb-3">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Recent Orders</h5>
      </div>
      <?php if (empty($recentOrders)): ?>
        <div style="padding:30px;text-align:center;color:var(--text-muted);">No orders yet.</div>
      <?php else: ?>
        <div class="table-wrap" style="border:none;border-radius:0;">
          <table class="table">
            <thead><tr><th>#</th><th>Service</th><th>Qty</th><th>Charge</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td>#<?= (int) $o['id'] ?></td>
                  <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($o['service_name']) ?></td>
                  <td><?= number_format((int) $o['quantity']) ?></td>
                  <td>$<?= number_format((float) $o['charge'], 2) ?></td>
                  <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span></td>
                  <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-table-card">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Deposit History</h5>
      </div>
      <?php if (empty($deposits)): ?>
        <div style="padding:30px;text-align:center;color:var(--text-muted);">No deposits yet.</div>
      <?php else: ?>
        <div class="table-wrap" style="border:none;border-radius:0;">
          <table class="table">
            <thead><tr><th>#</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($deposits as $d): ?>
                <tr>
                  <td>#<?= (int) $d['id'] ?></td>
                  <td style="font-weight:600;">$<?= number_format((float) $d['amount'], 2) ?></td>
                  <td><?= strtoupper($d['method']) ?></td>
                  <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
                  <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Balance Adjust Modal (shared markup, same as Users list) -->
<div class="modal-overlay" id="balanceModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-dollar-sign" style="color:var(--success);"></i> Adjust Balance</span>
      <button class="modal-close" onclick="closeModal('balanceModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="adjustUserId">
      <p style="color:var(--text-secondary);margin-bottom:16px;">User: <strong id="adjustUsername"></strong> · Current balance: <strong id="adjustCurrent"></strong></p>
      <div class="form-group">
        <label class="form-label">Action</label>
        <select id="adjustType" class="form-control"><option value="add">Add Funds</option><option value="deduct">Deduct Funds</option></select>
      </div>
      <div class="form-group">
        <label class="form-label">Amount ($)</label>
        <input type="number" id="adjustAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00">
      </div>
      <div class="form-group">
        <label class="form-label">Reason</label>
        <textarea id="adjustReason" class="form-control" rows="2"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('balanceModal')">Cancel</button>
      <button class="btn btn-success" onclick="submitBalanceAdjust(this)"><i class="fas fa-check"></i> Confirm</button>
    </div>
  </div>
</div>

<script>
async function saveUserEdit(btn) {
  const userId = document.getElementById('editUserId').value;
  btn.disabled = true;
  const data = await ajaxPost(`/admin/users/${userId}`, {
    _csrf: document.getElementById('editCsrf').value,
    full_name: document.getElementById('editFullName').value,
    email: document.getElementById('editEmail').value,
    role: document.getElementById('editRole').value,
    status: document.getElementById('editStatus').value
  });
  showToast(data.success ? data.message : data.message, data.success ? 'success' : 'error');
  btn.disabled = false;
}
</script>
