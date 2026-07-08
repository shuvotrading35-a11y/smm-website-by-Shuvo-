<?php /* Favorites View */ ?>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-heart" style="color:var(--primary);"></i> Favorites</h1>
    <p class="page-subtitle">Your saved services for quick reordering</p>
  </div>
</div>

<?php if (empty($favorites)): ?>
  <div class="glass-flat" style="padding:48px 24px;text-align:center;">
    <i class="fas fa-heart-crack" style="font-size:2.5rem;color:var(--text-secondary);opacity:0.5;"></i>
    <p style="margin:16px 0 20px;color:var(--text-secondary);">You haven't favorited any services yet.</p>
    <a href="/dashboard/new-order" class="btn btn-primary"><i class="fas fa-rocket"></i> Browse Services</a>
  </div>
<?php else: ?>
  <div class="row g-3" id="favoritesGrid">
    <?php foreach ($favorites as $fav): ?>
      <div class="col-12 col-md-6 col-lg-4" data-fav-id="<?= (int) $fav['id'] ?>">
        <div class="glass-flat" style="padding:18px;height:100%;display:flex;flex-direction:column;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
            <span class="badge" style="background:rgba(124,92,255,0.12);color:var(--primary);">
              <i class="<?= htmlspecialchars($fav['icon'] ?? 'fas fa-layer-group') ?>"></i> <?= htmlspecialchars($fav['category']) ?>
            </span>
            <button type="button" class="btn btn-ghost btn-icon remove-fav-btn" data-service-id="<?= (int) $fav['service_id'] ?>" title="Remove from favorites">
              <i class="fas fa-heart" style="color:#FF4B55;"></i>
            </button>
          </div>

          <h6 style="font-weight:600;margin:12px 0 6px;flex:1;"><?= htmlspecialchars($fav['name']) ?></h6>

          <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;color:var(--text-secondary);margin-bottom:12px;">
            <span>Min: <?= number_format((int) $fav['min_quantity']) ?></span>
            <span>Max: <?= number_format((int) $fav['max_quantity']) ?></span>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-weight:700;font-family:var(--font-heading);">$<?= number_format((float) $fav['user_rate'], 4) ?> <small style="font-weight:400;color:var(--text-secondary);">/1000</small></span>
            <a href="/dashboard/new-order?service=<?= (int) $fav['service_id'] ?>" class="btn btn-primary btn-sm">
              <i class="fas fa-rocket"></i> Order
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
document.querySelectorAll('.remove-fav-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const card = this.closest('[data-fav-id]');
    const serviceId = this.dataset.serviceId;

    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('_csrf', <?= json_encode($csrf) ?>);

    fetch('/dashboard/favorites/toggle', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        card.style.opacity = '0';
        setTimeout(() => {
          card.remove();
          if (!document.querySelectorAll('[data-fav-id]').length) location.reload();
        }, 200);
        showToast('Removed from favorites.', 'success');
      } else {
        showToast(data.message || 'Something went wrong.', 'error');
      }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
  });
});
</script>
