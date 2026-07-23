<?php /* Public Blog Listing */ ?>

<section style="padding:80px 0 40px;">
  <div class="container text-center">
    <h1 class="hero-title gradient-text" style="font-size:clamp(2rem,5vw,3rem);">Our Blog</h1>
    <p style="color:var(--text-secondary);max-width:600px;margin:16px auto 0;">
      Tips, guides, and updates on growing your social presence
    </p>
  </div>
</section>

<section style="padding:20px 0 80px;">
  <div class="container">
    <?php if (empty($pagination['data'])): ?>
      <div style="text-align:center;padding:60px 20px;color:var(--text-secondary);">
        <i class="fas fa-newspaper" style="font-size:3rem;opacity:0.3;"></i>
        <p style="margin-top:16px;">No posts published yet — check back soon.</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($pagination['data'] as $p): ?>
          <div class="col-12 col-md-6 col-lg-4">
            <a href="/blog/<?= htmlspecialchars($p['slug']) ?>" style="text-decoration:none;color:inherit;display:block;height:100%;">
              <div class="glass" style="height:100%;overflow:hidden;">
                <?php if (!empty($p['featured_img'])): ?>
                  <img src="<?= htmlspecialchars($p['featured_img']) ?>" alt="" style="width:100%;height:180px;object-fit:cover;">
                <?php else: ?>
                  <div style="width:100%;height:180px;background:var(--grad-aurora);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-newspaper" style="font-size:2rem;color:rgba(255,255,255,0.6);"></i>
                  </div>
                <?php endif; ?>
                <div style="padding:20px;">
                  <?php if (!empty($p['category_name'])): ?>
                    <span class="badge" style="background:rgba(124,92,255,0.12);color:var(--primary);margin-bottom:10px;"><?= htmlspecialchars($p['category_name']) ?></span>
                  <?php endif; ?>
                  <h5 style="font-family:var(--font-heading);font-weight:700;margin:8px 0;line-height:1.3;"><?= htmlspecialchars($p['title']) ?></h5>
                  <p style="color:var(--text-secondary);font-size:0.88rem;margin:0 0 14px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                    <?= htmlspecialchars($p['excerpt'] ?? '') ?>
                  </p>
                  <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($p['author']) ?></span>
                    <span><?= date('M d, Y', strtotime($p['published_at'])) ?></span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($pagination['last_page'] > 1): ?>
        <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;">
          <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
            <a href="?page=<?= $p ?>" class="btn <?= $p === $pagination['current_page'] ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
