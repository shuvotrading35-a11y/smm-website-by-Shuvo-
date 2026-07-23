<?php /* Admin Blog List View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-newspaper" style="color:var(--primary);"></i> Blog Posts</h1>
    <p class="page-subtitle">Manage articles and SEO content</p>
  </div>
  <a href="/admin/blog/create" class="btn btn-primary"><i class="fas fa-plus"></i> New Post</a>
</div>

<div class="admin-table-card">
  <?php if (empty($posts)): ?>
    <div style="padding:60px;text-align:center;color:var(--text-muted);">
      <i class="fas fa-newspaper" style="font-size:3rem;opacity:0.3;"></i>
      <p style="margin-top:16px;">No blog posts yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($posts as $p): ?>
            <tr>
              <td style="max-width:320px;">
                <div style="font-weight:600;font-size:0.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['title']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($p['excerpt'] ?? '') ?></div>
              </td>
              <td style="font-size:0.85rem;"><?= htmlspecialchars($p['author']) ?></td>
              <td><span class="badge badge-<?= htmlspecialchars($p['status']) ?>"><?= ucfirst($p['status']) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-muted);"><?= $p['published_at'] ? date('M d, Y', strtotime($p['published_at'])) : '—' ?></td>
              <td>
                <div style="display:flex;gap:4px;">
                  <a href="/admin/blog/<?= (int) $p['id'] ?>/edit" class="btn btn-sm btn-ghost" title="Edit"><i class="fas fa-pen"></i></a>
                  <button class="btn btn-sm btn-danger" title="Delete" onclick="deletePost(<?= (int) $p['id'] ?>, this)"><i class="fas fa-trash"></i></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
