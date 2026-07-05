<?php /* Public Services Page */ ?>

<div style="padding-top:70px;">
  <!-- Hero -->
  <div style="background:linear-gradient(180deg,rgba(124,92,255,0.08) 0%,transparent 100%);padding:60px 40px 40px;text-align:center;" data-aos="fade-up">
    <h1 style="font-family:var(--font-heading);font-size:clamp(2rem,5vw,3.2rem);font-weight:700;letter-spacing:-0.03em;margin-bottom:12px;">
      All <span class="gradient-text">SMM Services</span>
    </h1>
    <p style="color:var(--text-secondary);font-size:1rem;max-width:500px;margin:0 auto 32px;">
      Browse our full catalog of social media growth services. Best prices guaranteed.
    </p>

    <!-- Category Tabs -->
    <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;max-width:900px;margin:0 auto;" id="categoryTabs">
      <?php foreach ($categories as $cat): ?>
      <button class="btn <?= $cat['id'] === $activeCatId ? 'btn-primary' : 'btn-ghost' ?> btn-sm category-tab"
              data-id="<?= $cat['id'] ?>"
              onclick="switchCategory(<?= $cat['id'] ?>, this)">
        <i class="<?= htmlspecialchars($cat['icon'] ?? 'fas fa-globe') ?>"
           style="color:<?= $cat['id'] === $activeCatId ? '#fff' : htmlspecialchars($cat['color'] ?? 'var(--primary)') ?>;"></i>
        <?= htmlspecialchars($cat['name']) ?>
        <span style="font-size:0.72rem;opacity:0.7;">(<?= number_format($cat['service_count']) ?>)</span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="container-xl" style="padding-bottom:80px;">
    <!-- Search + Filter Bar -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:24px;padding:16px 0;">
      <div class="input-icon-wrap" style="flex:1;min-width:240px;max-width:400px;">
        <i class="input-icon fas fa-search"></i>
        <input type="text" class="form-control" id="svcSearch" placeholder="Search services…" oninput="filterServiceTable()">
      </div>
      <select class="form-control" style="width:auto;" id="svcSort" onchange="filterServiceTable()">
        <option value="name">Sort: Name</option>
        <option value="rate_asc">Price: Low → High</option>
        <option value="rate_desc">Price: High → Low</option>
        <option value="min_asc">Min: Low → High</option>
      </select>
      <div style="display:flex;gap:6px;align-items:center;">
        <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;color:var(--text-secondary);cursor:pointer;">
          <input type="checkbox" id="refillFilter" onchange="filterServiceTable()" style="accent-color:var(--primary);">
          ♻ Refill only
        </label>
      </div>
      <div style="margin-left:auto;font-size:0.85rem;color:var(--text-muted);" id="svcCount"></div>
    </div>

    <!-- Services Table -->
    <div class="glass-flat" id="svcTableWrap">
      <div class="table-wrap" style="border:none;border-radius:0;">
        <table class="table" id="svcTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Service Name</th>
              <th style="text-align:right;">Rate / 1K</th>
              <th style="text-align:right;">Min</th>
              <th style="text-align:right;">Max</th>
              <th>Features</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="svcTableBody">
            <?php foreach ($services as $s): ?>
            <tr class="svc-row"
                data-name="<?= htmlspecialchars(strtolower($s['name'])) ?>"
                data-rate="<?= (float)$s['user_rate'] ?>"
                data-min="<?= (int)$s['min_quantity'] ?>"
                data-refill="<?= (int)$s['refill'] ?>">
              <td style="font-size:0.82rem;color:var(--text-muted);"><?= (int)$s['api_service_id'] ?></td>
              <td>
                <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($s['name']) ?></div>
                <?php if (!empty($s['description'])): ?>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:3px;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars($s['description']) ?>
                </div>
                <?php endif; ?>
              </td>
              <td style="text-align:right;font-weight:700;color:var(--primary);">
                $<?= number_format((float)$s['user_rate'], 4) ?>
              </td>
              <td style="text-align:right;font-size:0.88rem;"><?= number_format((int)$s['min_quantity']) ?></td>
              <td style="text-align:right;font-size:0.88rem;"><?= number_format((int)$s['max_quantity']) ?></td>
              <td>
                <?php if ($s['refill']): ?>
                  <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:var(--success-bg);color:var(--success);border-radius:99px;font-size:0.72rem;font-weight:700;">♻ Refill</span>
                <?php endif; ?>
                <?php if ($s['cancel']): ?>
                  <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:var(--danger-bg);color:var(--danger);border-radius:99px;font-size:0.72rem;font-weight:700;margin-left:4px;">✕ Cancel</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="/dashboard/new-order?service=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
                  Order
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div id="svcEmpty" style="display:none;padding:60px;text-align:center;color:var(--text-muted);">
        <i class="fas fa-search" style="font-size:2.5rem;opacity:0.3;"></i>
        <p style="margin-top:16px;">No services match your search.</p>
      </div>
    </div>
  </div>
</div>

<script>
let currentCategoryId = <?= (int)$activeCatId ?>;

async function switchCategory(catId, btn) {
  document.querySelectorAll('.category-tab').forEach(b => {
    b.className = b.className.replace('btn-primary', 'btn-ghost');
  });
  btn.className = btn.className.replace('btn-ghost', 'btn-primary');
  currentCategoryId = catId;

  // Show skeleton
  document.getElementById('svcTableBody').innerHTML =
    Array(6).fill('<tr>' + ['ID','Service','Rate','Min','Max','Features',''].map((_, i) =>
      `<td><div style="height:20px;border-radius:6px;" class="skeleton"></div></td>`
    ).join('') + '</tr>').join('');

  const data = await fetch(`/services?category_id=${catId}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
  }).then(r => r.json());

  if (data.success) {
    const tbody = document.getElementById('svcTableBody');
    tbody.innerHTML = data.data.map(s => `
      <tr class="svc-row"
          data-name="${escHtml(s.name.toLowerCase())}"
          data-rate="${parseFloat(s.user_rate)}"
          data-min="${parseInt(s.min_quantity)}"
          data-refill="${s.refill ? 1 : 0}">
        <td style="font-size:0.82rem;color:var(--text-muted);">${s.api_service_id}</td>
        <td>
          <div style="font-weight:600;font-size:0.9rem;">${escHtml(s.name)}</div>
          ${s.description ? `<div style="font-size:0.78rem;color:var(--text-muted);margin-top:3px;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(s.description)}</div>` : ''}
        </td>
        <td style="text-align:right;font-weight:700;color:var(--primary);">$${parseFloat(s.user_rate).toFixed(4)}</td>
        <td style="text-align:right;font-size:0.88rem;">${parseInt(s.min_quantity).toLocaleString()}</td>
        <td style="text-align:right;font-size:0.88rem;">${parseInt(s.max_quantity).toLocaleString()}</td>
        <td>
          ${s.refill ? '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:var(--success-bg);color:var(--success);border-radius:99px;font-size:0.72rem;font-weight:700;">♻ Refill</span>' : ''}
          ${s.cancel ? '<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:var(--danger-bg);color:var(--danger);border-radius:99px;font-size:0.72rem;font-weight:700;margin-left:4px;">✕ Cancel</span>' : ''}
        </td>
        <td><a href="/dashboard/new-order?service=${s.id}" class="btn btn-primary btn-sm">Order</a></td>
      </tr>
    `).join('');

    filterServiceTable();
  }
}

function filterServiceTable() {
  const q      = document.getElementById('svcSearch').value.toLowerCase();
  const sort   = document.getElementById('svcSort').value;
  const refill = document.getElementById('refillFilter').checked;

  let rows = [...document.querySelectorAll('.svc-row')];

  // Filter
  rows.forEach(row => {
    const name    = row.dataset.name || '';
    const hasRefill = row.dataset.refill === '1';
    const show    = (!q || name.includes(q)) && (!refill || hasRefill);
    row.style.display = show ? '' : 'none';
  });

  // Sort visible rows
  const tbody   = document.getElementById('svcTableBody');
  const visible = rows.filter(r => r.style.display !== 'none');

  visible.sort((a, b) => {
    switch (sort) {
      case 'rate_asc':  return parseFloat(a.dataset.rate) - parseFloat(b.dataset.rate);
      case 'rate_desc': return parseFloat(b.dataset.rate) - parseFloat(a.dataset.rate);
      case 'min_asc':   return parseInt(a.dataset.min)  - parseInt(b.dataset.min);
      default:          return a.dataset.name.localeCompare(b.dataset.name);
    }
  });

  visible.forEach(row => tbody.appendChild(row));

  const count = visible.length;
  document.getElementById('svcCount').textContent = count + ' service' + (count !== 1 ? 's' : '');
  document.getElementById('svcEmpty').style.display = count === 0 ? 'block' : 'none';
  document.getElementById('svcTable').style.display  = count === 0 ? 'none' : '';
}

document.addEventListener('DOMContentLoaded', () => {
  filterServiceTable();
});
</script>
