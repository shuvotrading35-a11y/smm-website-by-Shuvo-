<?php /* Referrals View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-people-group" style="color:var(--primary);"></i> Referral Program</h1>
    <p class="page-subtitle">Earn <?= htmlspecialchars((string) $percent) ?>% commission on every order your referrals place</p>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(0,230,118,0.12);color:#00E676;"><i class="fas fa-sack-dollar"></i></div>
      <div class="stat-value">$<?= number_format((float) $totalEarned, 2) ?></div>
      <div class="stat-label">Total Earned</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(124,92,255,0.12);color:var(--primary);"><i class="fas fa-users"></i></div>
      <div class="stat-value"><?= count($referrals) ?></div>
      <div class="stat-label">Total Referrals</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(255,214,0,0.12);color:#FFD600;"><i class="fas fa-bolt"></i></div>
      <div class="stat-value"><?= count(array_filter($referrals, fn($r) => $r['status'] === 'active')) ?></div>
      <div class="stat-label">Active</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(255,75,85,0.12);color:#FF4B55;"><i class="fas fa-percent"></i></div>
      <div class="stat-value"><?= htmlspecialchars((string) $percent) ?>%</div>
      <div class="stat-label">Commission Rate</div>
    </div>
  </div>
</div>

<!-- Referral Link -->
<div class="glass-flat mb-3" style="padding:20px;">
  <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 12px;">
    <i class="fas fa-link" style="color:var(--primary);"></i> Your Referral Link
  </h5>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <input type="text" id="refLinkInput" class="form-control" value="<?= htmlspecialchars($referralLink) ?>" readonly style="flex:1;min-width:200px;">
    <button type="button" class="btn btn-primary" onclick="copyRefLink()"><i class="fas fa-copy"></i> Copy</button>
  </div>
  <p style="font-size:0.8rem;color:var(--text-secondary);margin:10px 0 0;">
    Share this link — when someone signs up and orders, you earn <?= htmlspecialchars((string) $percent) ?>% of every order they place.
  </p>
</div>

<!-- Referred Users -->
<div class="glass-flat">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Your Referrals</h5>
  </div>

  <?php if (empty($referrals)): ?>
    <div style="padding:48px 24px;text-align:center;">
      <i class="fas fa-user-plus" style="font-size:2.5rem;color:var(--text-secondary);opacity:0.5;"></i>
      <p style="margin:16px 0 0;color:var(--text-secondary);">No referrals yet. Share your link above to start earning.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table" style="width:100%;margin:0;">
        <thead>
          <tr>
            <th style="padding:12px 20px;">User</th>
            <th style="padding:12px 20px;">Joined</th>
            <th style="padding:12px 20px;">Status</th>
            <th style="padding:12px 20px;">Orders</th>
            <th style="padding:12px 20px;">Earned</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($referrals as $r): ?>
            <tr>
              <td style="padding:12px 20px;">
                <div style="font-weight:600;"><?= htmlspecialchars($r['full_name']) ?></div>
                <div style="font-size:0.8rem;color:var(--text-secondary);">@<?= htmlspecialchars($r['username']) ?></div>
              </td>
              <td style="padding:12px 20px;color:var(--text-secondary);font-size:0.85rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td style="padding:12px 20px;"><span class="badge badge-<?= $r['status'] === 'active' ? 'completed' : 'pending' ?>"><?= ucfirst($r['status']) ?></span></td>
              <td style="padding:12px 20px;"><?= (int) $r['order_count'] ?></td>
              <td style="padding:12px 20px;font-weight:600;color:#00E676;">$<?= number_format((float) $r['total_earned'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
function copyRefLink() {
  const input = document.getElementById('refLinkInput');
  navigator.clipboard.writeText(input.value).then(() => showToast('Referral link copied!', 'success'));
}
</script>
