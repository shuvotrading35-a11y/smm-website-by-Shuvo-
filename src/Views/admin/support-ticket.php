<?php
/* Admin Support Ticket Detail */
$statusColors = [
    'open'        => ['bg' => 'rgba(0,212,255,0.12)',  'text' => '#00D4FF'],
    'in_progress' => ['bg' => 'rgba(124,92,255,0.12)', 'text' => '#7C5CFF'],
    'waiting'     => ['bg' => 'rgba(255,214,0,0.12)',  'text' => '#FFD600'],
    'resolved'    => ['bg' => 'rgba(0,230,118,0.12)',  'text' => '#00E676'],
    'closed'      => ['bg' => 'rgba(90,101,128,0.15)', 'text' => '#5A6580'],
];
$sc = $statusColors[$ticket['status']] ?? $statusColors['open'];
?>

<div class="page-header admin-header">
  <div>
    <a href="/admin/support" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Tickets</a>
    <h1 class="page-title" style="margin-top:6px;">#<?= (int) $ticket['id'] ?> — <?= htmlspecialchars($ticket['subject']) ?></h1>
    <p class="page-subtitle">
      <?= htmlspecialchars($ticket['user_name']) ?> (<?= htmlspecialchars($ticket['email']) ?>) ·
      <span class="badge badge-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
      <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
    </p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="admin-table-card" style="padding:20px;">
      <?php foreach ($replies as $r): ?>
        <div style="display:flex;gap:12px;margin-bottom:18px;<?= $r['is_admin'] ? 'flex-direction:row-reverse;' : '' ?>">
          <img src="<?= htmlspecialchars($r['avatar'] ?? '/assets/img/default-avatar.svg') ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
          <div style="max-width:75%;">
            <div style="background:<?= $r['is_admin'] ? 'rgba(124,92,255,0.12)' : 'var(--surface)' ?>;border:1px solid var(--border);border-radius:12px;padding:12px 14px;">
              <div style="font-size:0.8rem;font-weight:600;margin-bottom:4px;color:<?= $r['is_admin'] ? 'var(--primary)' : 'inherit' ?>;">
                <?= htmlspecialchars($r['full_name']) ?><?= $r['is_admin'] ? ' <span style="font-weight:400;opacity:0.7;">(Staff)</span>' : '' ?>
              </div>
              <div style="white-space:pre-wrap;font-size:0.9rem;"><?= htmlspecialchars($r['body']) ?></div>
            </div>
            <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px;<?= $r['is_admin'] ? 'text-align:right;' : '' ?>"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-table-card mt-3" style="padding:20px;">
      <label class="form-label">Reply</label>
      <textarea id="adminReplyBody" class="form-control" rows="4" placeholder="Type your reply to the customer..."></textarea>
      <button class="btn btn-primary" style="margin-top:12px;" onclick="adminReply(<?= (int) $ticket['id'] ?>, this)">
        <i class="fas fa-paper-plane"></i> Send Reply
      </button>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="admin-table-card" style="padding:20px;">
      <h6 style="font-weight:700;margin:0 0 14px;">Ticket Actions</h6>

      <div class="form-group">
        <label class="form-label">Status</label>
        <select id="ticketStatusSelect" class="form-control">
          <?php foreach (['open', 'in_progress', 'waiting', 'resolved', 'closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Updates when you send a reply.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Assign To</label>
        <select id="assignSelect" class="form-control">
          <option value="">Unassigned</option>
          <?php foreach ($admins as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= (($ticket['assigned_to'] ?? null) == $a['id'] ? 'selected' : '') ?>><?= htmlspecialchars($a['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="padding-top:8px;border-top:1px solid var(--border);margin-top:8px;font-size:0.82rem;color:var(--text-secondary);">
        <div style="display:flex;justify-content:space-between;padding:6px 0;"><span>Category</span><strong><?= ucfirst($ticket['category']) ?></strong></div>
        <?php if (!empty($ticket['order_id'])): ?>
          <div style="display:flex;justify-content:space-between;padding:6px 0;"><span>Order</span><a href="/admin/orders?q=<?= (int) $ticket['order_id'] ?>">#<?= (int) $ticket['order_id'] ?></a></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:6px 0;"><span>Opened</span><strong><?= date('d M Y', strtotime($ticket['created_at'])) ?></strong></div>
      </div>
    </div>
  </div>
</div>
