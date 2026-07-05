<?php /* Dashboard Home View */ ?>

<!-- Balance + Quick Stats -->
<div class="row g-4 mb-4">
  <div class="col-12 col-md-5 col-lg-4">
    <div class="balance-card h-100">
      <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.5);margin-bottom:8px;">
        <i class="fas fa-wallet"></i> &nbsp;Available Balance
      </div>
      <div class="balance-amount">$<?= number_format((float)$user['balance'], 2) ?></div>
      <div style="margin-top:16px;display:flex;gap:10px;">
        <a href="/dashboard/add-funds" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i> Add Funds
        </a>
        <a href="/dashboard/transactions" class="btn btn-ghost btn-sm">
          <i class="fas fa-receipt"></i> History
        </a>
      </div>
      <!-- Mini sparkline placeholder -->
      <canvas id="balanceSparkline" style="margin-top:16px;opacity:0.6;" height="40"></canvas>
    </div>
  </div>

  <div class="col-12 col-md-7 col-lg-8">
    <div class="row g-3 h-100">
      <div class="col-6">
        <div class="stat-card h-100">
          <div class="stat-icon" style="background:rgba(124,92,255,0.15);color:var(--primary);">
            <i class="fas fa-box"></i>
          </div>
          <div class="stat-value counter" data-target="<?= (int)($stats['total_orders'] ?? 0) ?>">0</div>
          <div class="stat-label">Total Orders</div>
          <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= (int)($stats['today_orders'] ?? 0) ?> today</div>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-card h-100">
          <div class="stat-icon" style="background:rgba(0,212,255,0.15);color:var(--secondary);">
            <i class="fas fa-hourglass-half"></i>
          </div>
          <div class="stat-value counter" data-target="<?= (int)($stats['pending'] ?? 0) ?>">0</div>
          <div class="stat-label">Pending</div>
          <div class="stat-change" style="background:rgba(255,214,0,0.1);color:var(--warning);">
            <i class="fas fa-clock"></i> Active
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-card h-100">
          <div class="stat-icon" style="background:rgba(0,230,118,0.15);color:var(--success);">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-value">$<?= number_format((float)($stats['today_spent'] ?? 0), 2) ?></div>
          <div class="stat-label">Spent Today</div>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-card h-100">
          <div class="stat-icon" style="background:rgba(255,77,157,0.15);color:var(--accent);">
            <i class="fas fa-spinner"></i>
          </div>
          <div class="stat-value counter" data-target="<?= (int)($stats['in_progress'] ?? 0) ?>">0</div>
          <div class="stat-label">In Progress</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Spending Chart + Quick Order -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="glass-flat p-4 h-100">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Spending Overview</h5>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">Last 30 days</div>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="btn btn-ghost btn-sm" onclick="setChartRange(7)">7D</button>
          <button class="btn btn-ghost btn-sm active" onclick="setChartRange(30)">30D</button>
        </div>
      </div>
      <canvas id="spendingChart" height="160"></canvas>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="glass-flat p-4 h-100">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:20px;">Quick Order</h5>
      <div class="form-group">
        <label class="form-label">Service Link</label>
        <input type="url" class="form-control" id="quickLink" placeholder="https://instagram.com/yourprofile">
      </div>
      <div class="form-group">
        <label class="form-label">Service Type</label>
        <select class="form-control" id="quickService">
          <option value="">Select a service...</option>
        </select>
      </div>
      <a href="/dashboard/new-order" class="btn btn-primary w-100 mt-2">
        <i class="fas fa-cart-plus"></i> Full Order Form
      </a>
    </div>
  </div>
</div>

<!-- Recent Orders -->
<div class="glass-flat">
  <div class="p-4 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Recent Orders</h5>
    <a href="/dashboard/orders" class="btn btn-ghost btn-sm">View All <i class="fas fa-arrow-right"></i></a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <div style="padding:60px;text-align:center;">
      <i class="fas fa-box-open" style="font-size:3rem;color:var(--text-muted);"></i>
      <p style="color:var(--text-muted);margin-top:16px;">No orders yet.</p>
      <a href="/dashboard/new-order" class="btn btn-primary mt-2">Place First Order</a>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:none;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>#ID</th>
            <th>Service</th>
            <th>Link</th>
            <th>Qty</th>
            <th>Charge</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td><span style="color:var(--primary);font-weight:600;">#<?= $order['id'] ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <i class="<?= htmlspecialchars($order['category_icon'] ?? 'fas fa-globe') ?>" style="color:var(--primary);width:16px;"></i>
                <span style="font-size:0.88rem;"><?= htmlspecialchars(mb_strimwidth($order['service_name'], 0, 35, '…')) ?></span>
              </div>
            </td>
            <td>
              <a href="<?= htmlspecialchars($order['link']) ?>" target="_blank" class="table-link" title="<?= htmlspecialchars($order['link']) ?>">
                <?= htmlspecialchars(parse_url($order['link'], PHP_URL_HOST) ?: $order['link']) ?>
              </a>
            </td>
            <td><?= number_format($order['quantity']) ?></td>
            <td style="font-weight:600;color:var(--text);">$<?= number_format((float)$order['charge'], 4) ?></td>
            <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
            <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
// Spending chart
const chartData = <?= json_encode($chartData) ?>;

document.addEventListener('DOMContentLoaded', () => {
  const labels = chartData.map(d => {
    const date = new Date(d.day);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  });
  const values = chartData.map(d => parseFloat(d.total));

  const ctx = document.getElementById('spendingChart').getContext('2d');
  window.spendingChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        data: values,
        borderColor: '#7C5CFF',
        backgroundColor: 'rgba(124,92,255,0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 2.5,
        pointRadius: 0,
        pointHoverRadius: 6,
        pointHoverBackgroundColor: '#7C5CFF',
        pointHoverBorderColor: '#fff',
        pointHoverBorderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(10,12,20,0.95)',
          borderColor: 'rgba(124,92,255,0.3)',
          borderWidth: 1,
          padding: 12,
          callbacks: {
            label: ctx => ' $' + ctx.raw.toFixed(4),
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#5A6580', font: { size: 11 }, maxTicksLimit: 7 }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#5A6580', font: { size: 11 }, callback: v => '$' + v }
        }
      }
    }
  });

  // Animated counters
  document.querySelectorAll('.counter').forEach(el => {
    const target = parseInt(el.dataset.target, 10);
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 40));
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current.toLocaleString();
      if (current >= target) clearInterval(timer);
    }, 30);
  });
});
</script>
