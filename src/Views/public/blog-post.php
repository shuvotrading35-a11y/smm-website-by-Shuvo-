<?php /* Public Blog Post */ ?>

<article style="padding:60px 0 80px;">
  <div class="container" style="max-width:760px;">

    <a href="/blog" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Blog</a>

    <?php if (!empty($post['category_name'])): ?>
      <div style="margin-top:20px;">
        <span class="badge" style="background:rgba(124,92,255,0.12);color:var(--primary);"><?= htmlspecialchars($post['category_name']) ?></span>
      </div>
    <?php endif; ?>

    <h1 style="font-family:var(--font-heading);font-weight:700;font-size:clamp(1.6rem,4vw,2.4rem);margin:14px 0 20px;line-height:1.25;">
      <?= htmlspecialchars($post['title']) ?>
    </h1>

    <div style="display:flex;align-items:center;gap:12px;padding-bottom:24px;margin-bottom:32px;border-bottom:1px solid var(--border);">
      <img src="<?= htmlspecialchars($post['author_avatar'] ?? '/assets/img/default-avatar.svg') ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" alt="">
      <div>
        <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($post['author']) ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted);">
          <?= date('d M Y', strtotime($post['published_at'])) ?> · <?= number_format((int) $post['views']) ?> views
        </div>
      </div>
    </div>

    <?php if (!empty($post['featured_img'])): ?>
      <img src="<?= htmlspecialchars($post['featured_img']) ?>" alt="" style="width:100%;border-radius:var(--radius);margin-bottom:32px;">
    <?php endif; ?>

    <div style="font-size:1rem;line-height:1.8;color:var(--text);">
      <?= $post['body'] ?>
    </div>

    <?php if (!empty($post['tags'])): ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:32px;padding-top:24px;border-top:1px solid var(--border);">
        <?php foreach (array_filter(array_map('trim', explode(',', $post['tags']))) as $tag): ?>
          <span class="badge" style="background:var(--surface);border:1px solid var(--border);">#<?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</article>

<?php if (!empty($related)): ?>
<section style="padding:0 0 80px;">
  <div class="container">
    <h3 style="font-family:var(--font-heading);font-weight:700;margin-bottom:24px;">More Articles</h3>
    <div class="row g-4">
      <?php foreach ($related as $r): ?>
        <div class="col-12 col-md-4">
          <a href="/blog/<?= htmlspecialchars($r['slug']) ?>" style="text-decoration:none;color:inherit;">
            <div class="glass" style="height:100%;overflow:hidden;">
              <?php if (!empty($r['featured_img'])): ?>
                <img src="<?= htmlspecialchars($r['featured_img']) ?>" style="width:100%;height:140px;object-fit:cover;" alt="">
              <?php endif; ?>
              <div style="padding:16px;">
                <h6 style="font-weight:700;margin:0 0 6px;line-height:1.3;"><?= htmlspecialchars($r['title']) ?></h6>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($r['published_at'])) ?></div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
