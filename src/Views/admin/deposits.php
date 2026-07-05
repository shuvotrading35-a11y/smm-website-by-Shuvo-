<?php /* Admin Deposits View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-money-bill-wave" style="color:var(--warning);"></i> Deposit Management</h1>
    <p class="page-subtitle">Review and approve pending deposit requests</p>
  </div>
</div>

<!-- Filters -->
<div class="admin-filter-card">
  <div>
    <label class="form-label">Status</label>
    <select class="form-control" onchange="window.location.href='?status='+this.value+'&method='+document.getElementById('methodFilter').value">
      <option value="">All Statuses</option>
      <?php foreach(['pending','approved','rejected'] as $s): ?>
      <option value="<?= $s ?>" <?= (($_GET['status']??'')===$s?'selected':'') ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="form-label">Method</label>
    <select class="form-control" id="methodFilter"
            onchange="window.location.href='?status='+document.querySelector('[onchange*=methodFilter]').previousElementSibling.value+'&method='+this.value">
      <option value="">All Methods</option>
      <?php foreach(['bkash','nagad','rocket','usdt_trc20','usdt_erc20','binance'] as $m): ?>
      <option value="<?= $m ?>" <?= (($_GET['method']??'')===$m?'selected':'') ?>><?= strtoupper($m) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);padding-top:28px;">
    Total: <?= number_format($pagination['total']) ?> deposits
  </div>
</div>

<!-- Table -->
<div class="admin-table-card">
  <?php if(empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-inbox" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No deposits found.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap" style="border:none;border-radius:0;">
    <table class="table">
      <thead>
        <tr>
          <th>#</th><th>User</th><th>Amount</th><th>Method</th>
          <th>TrxID</th><th>Proof</th><th>Status</th><th>Submitted</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($pagination['data'] as $d): ?>
        <tr class="<?= $d['status']==='pending'?'deposit-row-pending':'' ?>">
          <td style="font-size:0.82rem;color:var(--text-muted);">#<?= $d['id'] ?></td>
          <td>
            <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($d['username']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($d['email']) ?></div>
          </td>
          <td style="font-weight:700;color:var(--success);font-size:1rem;">
            $<?= number_format((float)$d['amount'],2) ?>
            <?php if($d['approved_amount'] && $d['approved_amount'] != $d['amount']): ?>
            <div style="font-size:0.75rem;color:var(--text-muted);">Credited: $<?= number_format((float)$d['approved_amount'],2) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span style="padding:3px 10px;border-radius:99px;font-size:0.75rem;font-weight:700;background:var(--surface);border:1px solid var(--border);">
              <?= strtoupper(htmlspecialchars($d['method'])) ?>
            </span>
          </td>
          <td style="font-family:monospace;font-size:0.82rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($d['transaction_id'] ?? '—') ?>
          </td>
          <td>
            <?php if($d['proof_image']): ?>
            <img src="<?= htmlspecialchars($d['proof_image']) ?>"
                 class="proof-thumb"
                 onclick="viewProof('<?= htmlspecialchars($d['proof_image']) ?>')"
                 alt="Proof">
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:0.82rem;">—</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
          <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap;">
            <?= date('M d, Y', strtotime($d['created_at'])) ?>
            <div style="font-size:0.72rem;"><?= date('H:i', strtotime($d['created_at'])) ?></div>
          </td>
          <td>
            <?php if($d['status']==='pending'): ?>
            <div style="display:flex;gap:6px;">
              <button class="btn btn-success btn-sm"
                      onclick="approveDeposit(<?= $d['id'] ?>, '<?= htmlspecialchars(strtoupper($d['method'])) ?>')">
                <i class="fas fa-check"></i> Approve
              </button>
              <button class="btn btn-danger btn-sm"
                      onclick="rejectDeposit(<?= $d['id'] ?>)">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <?php else: ?>
            <span style="font-size:0.8rem;color:var(--text-muted);">
              <?= $d['reviewed_at'] ? date('M d H:i', strtotime($d['reviewed_at'])) : '—' ?>
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if($pagination['last_page'] > 1): ?>
  <div class="admin-pagination">
    <div class="page-info">Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= number_format($pagination['total']) ?></div>
    <div class="pagination">
      <?php if($pagination['current_page']>1): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pagination['current_page']-1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php for($p=max(1,$pagination['current_page']-2);$p<=min($pagination['last_page'],$pagination['current_page']+2);$p++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="page-btn <?= $p===$pagination['current_page']?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if($pagination['current_page']<$pagination['last_page']): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$pagination['current_page']+1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Approve Modal -->
<div class="modal-overlay" id="approveModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-check-circle" style="color:var(--success);"></i> Approve Deposit</span>
      <button class="modal-close" onclick="closeModal('approveModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="approveDepositId">
      <p style="color:var(--text-secondary);margin-bottom:16px;">
        Method: <strong id="approveMethod"></strong>
      </p>
      <div class="form-group">
        <label class="form-label">Amount to Credit ($)</label>
        <div class="input-icon-wrap">
          <i class="input-icon fas fa-dollar-sign"></i>
          <input type="number" id="approveAmount" class="form-control" placeholder="0.00" step="0.01" min="0.01">
        </div>
        <div class="form-text">Enter the actual amount you verified was received.</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('approveModal')">Cancel</button>
      <button class="btn btn-success" onclick="submitApproval(this)">
        <i class="fas fa-check"></i> Approve & Credit
      </button>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Deposit</span>
      <button class="modal-close" onclick="closeModal('rejectModal')">×</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rejectDepositId">
      <div class="form-group">
        <label class="form-label">Rejection Reason</label>
        <textarea id="rejectReason" class="form-control" rows="3"
                  placeholder="e.g. Transaction ID not found, screenshot unclear…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('rejectModal')">Cancel</button>
      <button class="btn btn-danger" onclick="submitRejection(this)">
        <i class="fas fa-times"></i> Reject
      </button>
    </div>
  </div>
</div>
