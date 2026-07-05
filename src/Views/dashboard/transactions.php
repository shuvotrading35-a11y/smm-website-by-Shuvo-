<?php /* Transactions History View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-receipt" style="color:var(--primary);"></i> Transactions</h1>
    <p class="page-subtitle">Complete history of all balance movements</p>
  </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <?php
  $db = \SMMPanel\Core\Database::getInstance();
  $userId = $_SESSION['user_id'] ?? 0;
  $summary = $db->fetchOne(
    'SELECT
       COALESCE(SUM(CASE WHEN type="deposit" THEN amount ELSE 0 END), 0) AS total_deposited,
       COALESCE(SUM(CASE WHEN type="order"   THEN ABS(amount) ELSE 0 END), 0) AS total_spent,
       COALESCE(SUM(CASE WHEN type="refund"  THEN amount ELSE 0 END), 0) AS total_refunded,
       COALESCE(SUM(CASE WHEN type="referral" THEN amount ELSE 0 END), 0) AS total_referral
     FROM smmPanel_transactions WHERE user_id = ?',
    [$userId]
  );
  $cards = [
    ['Total Deposited', '$'.number_format((float)$summary['total_deposited'],2), 'fas fa-arrow-down-to-line', '#00E676', 'rgba(0,230,118,0.12)'],
    ['Total Spent',     '$'.number_format((float)$summary['total_spent'],2),    'fas fa-arrow-up-from-line', '#FF4B55', 'rgba(255,75,85,0.12)'],
    ['Refunds',         '$'.number_format((float)$summary['total_refunded'],2), 'fas fa-rotate-left',        '#7C5CFF', 'rgba(124,92,255,0.12)'],
    ['Referral Earned', '$'.number_format((float)$summary['total_referral'],2), 'fas fa-people-group',       '#FFD600', 'rgba(255,214,0,0.12)'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= $c[4] ?>;color:<?= $c[3] ?>;">
        <i class="<?= $c[0] === 'Total Spent' ? 'fas fa-shopping-cart' : $c[2] ?>"></i>
      </div>
      <div class="stat-value" style="font-size:1.4rem;"><?= $c[1] ?></div>
      <div class="stat-label"><?= $c[0] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Transaction Table -->
<div class="glass-flat">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">All Transactions</h5>
    <div style="display:flex;gap:8px;">
      <select class="form-control" style="width:auto;padding:7px 12px;font-size:0.85rem;" onchange="filterTxType(this.value)">
        <option value="">All Types</option>
        <option value="deposit">Deposits</option>
        <option value="order">Orders</option>
        <option value="refund">Refunds</option>
        <option value="referral">Referrals</option>
        <option value="bonus">Bonuses</option>
        <option value="deduction">Deductions</option>
      </select>
    </div>
  </div>

  <?php if (empty($pagination['data'])): ?>
    <div style="padding:80px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-receipt" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No transactions yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table" id="txTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Description</th>
            <th style="text-align:right;">Amount</th>
            <th style="text-align:right;">Balance After</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $typeIcons = [
            'deposit'   => ['fas fa-arrow-down-to-line', '#00E676'],
            'order'     => ['fas fa-shopping-cart',      '#FF4B55'],
            'refund'    => ['fas fa-rotate-left',        '#7C5CFF'],
            'referral'  => ['fas fa-people-group',       '#FFD600'],
            'bonus'     => ['fas fa-gift',               '#00D4FF'],
            'deduction' => ['fas fa-minus-circle',       '#FF4B55'],
            'withdraw'  => ['fas fa-arrow-up-from-line', '#FF4D9D'],
          ];
          foreach ($pagination['data'] as $tx):
            $icon = $typeIcons[$tx['type']] ?? ['fas fa-circle', '#A8B3CF'];
            $isPositive = (float)$tx['amount'] > 0;
          ?>
          <tr class="tx-row" data-type="<?= $tx['type'] ?>">
            <td style="font-size:0.82rem;color:var(--text-muted);">#<?= $tx['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.85rem;background:<?= $icon[1] ?>20;color:<?= $icon[1] ?>;">
                  <i class="<?= $icon[0] ?>"></i>
                </div>
                <span style="font-size:0.85rem;font-weight:600;text-transform:capitalize;"><?= htmlspecialchars($tx['type']) ?></span>
              </div>
            </td>
            <td style="font-size:0.85rem;color:var(--text-secondary);max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($tx['description'] ?? '—') ?>
            </td>
            <td style="text-align:right;font-weight:700;font-size:0.95rem;color:<?= $isPositive ? 'var(--success)' : 'var(--danger)' ?>;">
              <?= $isPositive ? '+' : '' ?>$<?= number_format(abs((float)$tx['amount']), 4) ?>
            </td>
            <td style="text-align:right;font-size:0.88rem;color:var(--text-secondary);">
              $<?= number_format((float)$tx['balance_after'], 4) ?>
            </td>
            <td style="font-size:0.82rem;color:var(--text-muted);white-space:nowrap;">
              <?= date('M d, Y H:i', strtotime($tx['created_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-top:1px solid var(--border);">
      <div style="font-size:0.85rem;color:var(--text-muted);">
        Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= number_format($pagination['total']) ?>
      </div>
      <div class="pagination">
        <?php if ($pagination['current_page'] > 1): ?>
        <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1, $pagination['current_page'] - 2); $p <= min($pagination['last_page'], $pagination['current_page'] + 2); $p++): ?>
        <a href="?page=<?= $p ?>" class="page-btn <?= $p === $pagination['current_page'] ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
        <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
function filterTxType(type) {
  document.querySelectorAll('.tx-row').forEach(row => {
    row.style.display = (!type || row.dataset.type === type) ? '' : 'none';
  });
}
</script>
