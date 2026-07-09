<?php /* Admin Logs View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-clipboard-list" style="color:var(--primary);"></i> Admin Logs</h1>
    <p class="page-subtitle">Audit trail of administrative actions</p>
  </div>
</div>

<div class="admin-table-card">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-clipboard-list" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No log entries yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead><tr><th>Admin</th><th>Action</th><th>Target</th><th>IP</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($pagination['data'] as $l): ?>
            <tr>
              <td style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($l['admin_name']) ?></td>
              <td><span class="badge" style="background:var(--surface);border:1px solid var(--border);"><?= htmlspecialchars(str_replace('_', ' ', $l['action'])) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-muted);">
                <?= $l['target_type'] ? htmlspecialchars(ucfirst($l['target_type'])) . ' #' . (int) $l['target_id'] : '—' ?>
              </td>
              <td style="font-size:0.78rem;color:var(--text-muted);font-family:monospace;"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;"><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
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
