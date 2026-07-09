<?php /* Admin Broadcast View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-bullhorn" style="color:var(--primary);"></i> Broadcast</h1>
    <p class="page-subtitle">Send a message to all users via in-app notification or email</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="admin-table-card" style="padding:24px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 16px;">Compose</h5>

      <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" id="broadcastTitle" class="form-control" placeholder="e.g. Scheduled Maintenance" maxlength="255">
      </div>
      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea id="broadcastBody" class="form-control" rows="5" placeholder="Write your message..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Channels</label>
        <div style="display:flex;gap:16px;">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
            <input type="checkbox" name="channels[]" value="inapp" checked> In-App
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
            <input type="checkbox" name="channels[]" value="email"> Email
          </label>
        </div>
      </div>

      <button class="btn btn-primary w-100" onclick="sendBroadcast(this)"><i class="fas fa-paper-plane"></i> Send Broadcast</button>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="admin-table-card">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
        <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Recent Broadcasts</h5>
      </div>
      <?php if (empty($broadcasts)): ?>
        <div style="padding:40px 24px;text-align:center;color:var(--text-muted);">No broadcasts sent yet.</div>
      <?php else: ?>
        <div style="max-height:520px;overflow-y:auto;">
          <?php foreach ($broadcasts as $b): ?>
            <div style="padding:14px 20px;border-bottom:1px solid var(--border);">
              <div style="display:flex;justify-content:space-between;align-items:start;">
                <strong style="font-size:0.9rem;"><?= htmlspecialchars($b['title']) ?></strong>
                <span class="badge badge-<?= $b['status'] === 'sent' ? 'active' : 'pending' ?>"><?= ucfirst($b['status']) ?></span>
              </div>
              <p style="font-size:0.82rem;color:var(--text-secondary);margin:6px 0;"><?= htmlspecialchars($b['body']) ?></p>
              <div style="font-size:0.72rem;color:var(--text-muted);">
                by <?= htmlspecialchars($b['admin_name']) ?> · <?= htmlspecialchars(strtoupper($b['channels'])) ?> · <?= date('M d, Y H:i', strtotime($b['created_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
