<?php
/* Admin Support Tickets List */
$statusColors = [
    'open'        => ['bg' => 'rgba(0,212,255,0.12)',  'text' => '#00D4FF'],
    'in_progress' => ['bg' => 'rgba(124,92,255,0.12)', 'text' => '#7C5CFF'],
    'waiting'     => ['bg' => 'rgba(255,214,0,0.12)',  'text' => '#FFD600'],
    'resolved'    => ['bg' => 'rgba(0,230,118,0.12)',  'text' => '#00E676'],
    'closed'      => ['bg' => 'rgba(90,101,128,0.15)', 'text' => '#5A6580'],
];
?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-headset" style="color:var(--primary);"></i> Support Tickets</h1>
    <p class="page-subtitle">Sorted by priority, then most recently active</p>
  </div>
</div>

<div class="admin-filter-card">
  <div>
    <label class="form-label">Status</label>
    <select class="form-control" onchange="window.location.href='?status='+this.value">
      <option value="">All</option>
      <?php foreach (['open', 'in_progress', 'waiting', 'resolved', 'closed'] as $s): ?>
        <option value="<?= $s ?>" <?= (($filters['status'] ?? '') === $s ? 'selected' : '') ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);padding-top:28px;">
    Total: <?= number_format($pagination['total']) ?> tickets
  </div>
</div>

<div class="admin-table-card">
  <?php if (empty($pagination['data'])): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-comments" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No tickets found.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead><tr><th>#</th><th>Subject</th><th>User</th><th>Priority</th><th>Status</th><th>Replies</th><th>Last Activity</th></tr></thead>
        <tbody>
          <?php foreach ($pagination['data'] as $t):
            $sc = $statusColors[$t['status']] ?? $statusColors['open'];
          ?>
            <tr style="cursor:pointer;" onclick="window.location.href='/admin/support/<?= (int) $t['id'] ?>'">
              <td>#<?= (int) $t['id'] ?></td>
              <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($t['subject']) ?></td>
              <td>
                <div style="font-size:0.85rem;"><?= htmlspecialchars($t['username']) ?></div>
                <div style="font-size:0.72rem;color:var(--text-muted);"><?= htmlspecialchars($t['email']) ?></div>
              </td>
              <td><span class="badge badge-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
              <td><span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
              <td><?= (int) $t['reply_count'] ?></td>
              <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;"><?= $t['last_reply_at'] ? date('M d, H:i', strtotime($t['last_reply_at'])) : date('M d, H:i', strtotime($t['created_at'])) ?></td>
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
