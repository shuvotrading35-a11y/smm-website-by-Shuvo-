<?php
/* Support View */
$statusColors = [
    'open'        => ['bg' => 'rgba(0,212,255,0.12)',  'text' => '#00D4FF'],
    'in_progress' => ['bg' => 'rgba(124,92,255,0.12)', 'text' => '#7C5CFF'],
    'waiting'     => ['bg' => 'rgba(255,214,0,0.12)',  'text' => '#FFD600'],
    'resolved'    => ['bg' => 'rgba(0,230,118,0.12)',  'text' => '#00E676'],
    'closed'      => ['bg' => 'rgba(90,101,128,0.15)', 'text' => '#5A6580'],
];
$priorityColors = [
    'low'    => ['bg' => 'rgba(0,230,118,0.1)',  'text' => '#00E676'],
    'medium' => ['bg' => 'rgba(0,212,255,0.1)',  'text' => '#00D4FF'],
    'high'   => ['bg' => 'rgba(255,214,0,0.1)',  'text' => '#FFD600'],
    'urgent' => ['bg' => 'rgba(255,75,85,0.12)', 'text' => '#FF4B55'],
];
?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-headset" style="color:var(--primary);"></i> Support</h1>
    <p class="page-subtitle">Get help with orders, payments, or your account</p>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('newTicketModal').classList.add('show')">
    <i class="fas fa-plus"></i> New Ticket
  </button>
</div>

<div class="glass-flat">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Your Tickets</h5>
  </div>

  <?php if (empty($tickets)): ?>
    <div style="padding:48px 24px;text-align:center;">
      <i class="fas fa-comments" style="font-size:2.5rem;color:var(--text-secondary);opacity:0.5;"></i>
      <p style="margin:16px 0 20px;color:var(--text-secondary);">No support tickets yet.</p>
      <button type="button" class="btn btn-primary" onclick="document.getElementById('newTicketModal').classList.add('show')">
        <i class="fas fa-plus"></i> Open Your First Ticket
      </button>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table" style="width:100%;margin:0;">
        <thead>
          <tr>
            <th style="padding:12px 20px;">Subject</th>
            <th style="padding:12px 20px;">Category</th>
            <th style="padding:12px 20px;">Priority</th>
            <th style="padding:12px 20px;">Status</th>
            <th style="padding:12px 20px;">Last Reply</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $t):
            $sc = $statusColors[$t['status']] ?? $statusColors['open'];
            $pc = $priorityColors[$t['priority']] ?? $priorityColors['medium'];
          ?>
            <tr style="cursor:pointer;" onclick="window.location.href='/dashboard/support/<?= (int) $t['id'] ?>'">
              <td style="padding:12px 20px;font-weight:600;">#<?= (int) $t['id'] ?> — <?= htmlspecialchars($t['subject']) ?></td>
              <td style="padding:12px 20px;"><?= ucfirst($t['category']) ?></td>
              <td style="padding:12px 20px;">
                <span class="badge" style="background:<?= $pc['bg'] ?>;color:<?= $pc['text'] ?>;"><?= ucfirst($t['priority']) ?></span>
              </td>
              <td style="padding:12px 20px;">
                <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span>
              </td>
              <td style="padding:12px 20px;color:var(--text-secondary);font-size:0.85rem;">
                <?= $t['last_reply_at'] ? date('d M Y, h:i A', strtotime($t['last_reply_at'])) : '—' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- New Ticket Modal -->
<div class="modal" id="newTicketModal">
  <div class="modal-content glass-flat" style="max-width:520px;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">New Support Ticket</h5>
      <button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('newTicketModal').classList.remove('show')"><i class="fas fa-xmark"></i></button>
    </div>
    <form id="newTicketForm" style="padding:20px;">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required maxlength="255">
        </div>
        <div class="col-6">
          <label class="form-label">Category</label>
          <select name="category" class="form-control">
            <option value="general">General</option>
            <option value="order">Order Issue</option>
            <option value="payment">Payment</option>
            <option value="technical">Technical</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-control">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Order ID <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
          <input type="number" name="order_id" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="4" required></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;">
        <i class="fas fa-paper-plane"></i> Submit Ticket
      </button>
    </form>
  </div>
</div>

<script>
document.getElementById('newTicketForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Submitting...';

  fetch('/dashboard/support', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => { window.location.href = data.redirect; }, 600);
    } else {
      showToast(data.message, 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
    }
  })
  .catch(() => {
    showToast('Network error. Please try again.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
  });
});
</script>
