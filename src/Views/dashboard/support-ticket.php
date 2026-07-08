<?php
/* Support Ticket Thread View */
$statusColors = [
    'open'        => ['bg' => 'rgba(0,212,255,0.12)',  'text' => '#00D4FF'],
    'in_progress' => ['bg' => 'rgba(124,92,255,0.12)', 'text' => '#7C5CFF'],
    'waiting'     => ['bg' => 'rgba(255,214,0,0.12)',  'text' => '#FFD600'],
    'resolved'    => ['bg' => 'rgba(0,230,118,0.12)',  'text' => '#00E676'],
    'closed'      => ['bg' => 'rgba(90,101,128,0.15)', 'text' => '#5A6580'],
];
$sc = $statusColors[$ticket['status']] ?? $statusColors['open'];
$isClosed = $ticket['status'] === 'closed';
?>

<div class="page-header">
  <div>
    <a href="/dashboard/support" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;">
      <i class="fas fa-arrow-left"></i> Back to Support
    </a>
    <h1 class="page-title" style="margin-top:6px;">#<?= (int) $ticket['id'] ?> — <?= htmlspecialchars($ticket['subject']) ?></h1>
    <p class="page-subtitle">
      <?= ucfirst($ticket['category']) ?> ·
      <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
    </p>
  </div>
</div>

<div class="glass-flat" style="padding:20px;margin-bottom:16px;">
  <?php foreach ($replies as $r): ?>
    <div style="display:flex;gap:12px;margin-bottom:18px;<?= $r['is_admin'] ? 'flex-direction:row-reverse;' : '' ?>">
      <img src="<?= htmlspecialchars($r['avatar'] ?? '/assets/img/default-avatar.svg') ?>"
           style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
      <div style="max-width:75%;">
        <div style="background:<?= $r['is_admin'] ? 'rgba(124,92,255,0.12)' : 'var(--glass-bg-flat, rgba(255,255,255,0.03))' ?>;border:1px solid var(--border);border-radius:12px;padding:12px 14px;">
          <div style="font-size:0.8rem;font-weight:600;margin-bottom:4px;color:<?= $r['is_admin'] ? 'var(--primary)' : 'inherit' ?>;">
            <?= htmlspecialchars($r['full_name']) ?><?= $r['is_admin'] ? ' <span style="font-weight:400;opacity:0.7;">(Support)</span>' : '' ?>
          </div>
          <div style="white-space:pre-wrap;font-size:0.9rem;"><?= htmlspecialchars($r['body']) ?></div>
        </div>
        <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:4px;<?= $r['is_admin'] ? 'text-align:right;' : '' ?>">
          <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($isClosed): ?>
  <div class="glass-flat" style="padding:18px 20px;text-align:center;color:var(--text-secondary);">
    <i class="fas fa-lock"></i> This ticket is closed. Open a new ticket if you need further help.
  </div>
<?php else: ?>
  <div class="glass-flat" style="padding:20px;">
    <form id="replyForm">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label class="form-label">Reply</label>
      <textarea name="body" class="form-control" rows="3" required placeholder="Type your reply..."></textarea>
      <button type="submit" class="btn btn-primary" style="margin-top:12px;">
        <i class="fas fa-paper-plane"></i> Send Reply
      </button>
    </form>
  </div>
<?php endif; ?>

<script>
const replyForm = document.getElementById('replyForm');
if (replyForm) {
  replyForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;

    fetch('/dashboard/support/<?= (int) $ticket['id'] ?>/reply', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        showToast(data.message, 'error');
        btn.disabled = false;
      }
    })
    .catch(() => { showToast('Network error. Please try again.', 'error'); btn.disabled = false; });
  });
}
</script>
