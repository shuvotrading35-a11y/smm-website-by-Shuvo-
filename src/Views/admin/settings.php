<?php /* Admin Settings View */ ?>

<div class="page-header admin-header">
  <div>
    <h1 class="page-title"><i class="fas fa-sliders" style="color:var(--primary);"></i> Website Settings</h1>
    <p class="page-subtitle">Site-wide configuration</p>
  </div>
</div>

<form id="settingsForm">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

  <?php foreach ($settings as $groupName => $rows): ?>
    <div class="admin-table-card mb-3" style="padding:20px 24px;">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin:0 0 16px;text-transform:capitalize;">
        <?= htmlspecialchars($groupName) ?>
      </h5>
      <div class="row g-3">
        <?php foreach ($rows as $key => $row): ?>
          <div class="col-12 <?= in_array($row['type'], ['text', 'json'], true) ? '' : 'col-md-6' ?>">
            <label class="form-label"><?= htmlspecialchars($row['label'] ?? $key) ?></label>

            <?php if ($row['type'] === 'boolean'): ?>
              <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="0">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding-top:6px;">
                <input type="checkbox" name="<?= htmlspecialchars($key) ?>" value="1" <?= $row['value'] === '1' ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary);">
                <span style="font-size:0.85rem;color:var(--text-secondary);">Enabled</span>
              </label>

            <?php elseif ($row['type'] === 'integer'): ?>
              <input type="number" name="<?= htmlspecialchars($key) ?>" class="form-control" value="<?= htmlspecialchars($row['value'] ?? '') ?>">

            <?php elseif (in_array($row['type'], ['text', 'json'], true)): ?>
              <textarea name="<?= htmlspecialchars($key) ?>" class="form-control" rows="<?= $row['type'] === 'json' ? 4 : 3 ?>" style="<?= $row['type'] === 'json' ? 'font-family:monospace;font-size:0.85rem;' : '' ?>"><?= htmlspecialchars($row['value'] ?? '') ?></textarea>

            <?php else: ?>
              <input type="text" name="<?= htmlspecialchars($key) ?>" class="form-control" value="<?= htmlspecialchars($row['value'] ?? '') ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <button type="button" class="btn btn-primary" onclick="saveSettings(this)"><i class="fas fa-floppy-disk"></i> Save Settings</button>
</form>
