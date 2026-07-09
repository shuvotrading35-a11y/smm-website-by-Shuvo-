<?php /* Admin Create/Edit Blog Post View */
$isEdit = isset($post);
$submitUrl = $isEdit ? "/admin/blog/{$post['id']}/edit" : '/admin/blog/create';
?>

<div class="page-header admin-header">
  <div>
    <a href="/admin/blog" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Blog</a>
    <h1 class="page-title" style="margin-top:6px;"><?= $isEdit ? 'Edit Post' : 'New Post' ?></h1>
  </div>
</div>

<form id="postForm">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="admin-table-card" style="padding:20px;">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Excerpt</label>
          <textarea name="excerpt" class="form-control" rows="2"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Body</label>
          <textarea name="body" class="form-control" rows="16" style="font-family:monospace;font-size:0.88rem;" required><?= htmlspecialchars($post['body'] ?? '') ?></textarea>
          <div class="form-text">HTML is supported.</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="admin-table-card mb-3" style="padding:20px;">
        <h6 style="font-weight:700;margin:0 0 12px;">Publish</h6>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100" id="postSubmitBtn">
          <i class="fas fa-floppy-disk"></i> <?= $isEdit ? 'Update Post' : 'Create Post' ?>
        </button>
      </div>

      <div class="admin-table-card" style="padding:20px;">
        <h6 style="font-weight:700;margin:0 0 12px;">SEO</h6>
        <div class="form-group">
          <label class="form-label">SEO Title</label>
          <input type="text" name="seo_title" class="form-control" value="<?= htmlspecialchars($post['seo_title'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">SEO Description</label>
          <textarea name="seo_desc" class="form-control" rows="3"><?= htmlspecialchars($post['seo_desc'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
document.getElementById('postForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('postSubmitBtn');
  btn.disabled = true;

  const data = await fetch(<?= json_encode($submitUrl) ?>, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(this)
  }).then(r => r.json());

  if (data.success) {
    showToast(data.message, 'success');
    setTimeout(() => { window.location.href = data.redirect || '/admin/blog'; }, 600);
  } else {
    showToast(data.message || 'Failed to save post.', 'error');
    btn.disabled = false;
  }
});
</script>
