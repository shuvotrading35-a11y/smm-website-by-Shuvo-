<?php /* Admin Dashboard View */ ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
  <?php
  $kpiCards = [
    ['label'=>'Total Users',    'value'=> number_format((int)$kpis['total_users']),    'sub'=>'+'.number_format((int)$kpis['new_users_today']).' today', 'icon'=>'fas fa-users',        'color'=>'#7C5CFF', 'bg'=>'rgba(124,92,255,0.15)'],
    ['label'=>'Total Orders',   'value'=> number_format((int)$kpis['total_orders']),   'sub'=>number_format((int)$kpis['orders_today']).' today',         'icon'=>'fas fa-box',          'color'=>'#00D4FF', 'bg'=>'rgba(0,212,255,0.15)'],
    ['label'=>'Total Revenue',  'value'=>'$'.number_format((float)$kpis['total_revenue'],2), 'sub'=>'$'.number_format((float)$kpis['revenue_today'],2).' today', 'icon'=>'fas fa-dollar-sign',  'color'=>'#00E676', 'bg'=>'rgba(0,230,118,0.15)'],
    ['label'=>'Pending Deposits','value'=> number_format((int)$kpis['pending_deposits']), 'sub'=>'Awaiting review', 'icon'=>'fas fa-money-bill-wave', 'color'=>'#FFD600', 'bg'=>'rgba(255,214,0,0.15)'],
    ['label'=>'Open Tickets',   'value'=> number_format((int)$kpis['open_tickets']),   'sub'=>'Need attention',  'icon'=>'fas fa-headset',         'color'=>'#FF4D9D', 'bg'=>'rgba(255,77,157,0.15)'],
    ['label'=>'API Balance',    'value'=>'$'.htmlspecialchars((string)$providerBalance), 'sub'=>'Provider balance', 'icon'=>'fas fa-plug',         'color'=>'#9C27B0', 'bg'=>'rgba(156,39,176,0.15)'],
  ];
  foreach ($kpiCards as $i => $c): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="kpi-card" data-aos="fade-up" data-aos-delay="<?= $i * 60 ?>">
      <div class="kpi-icon" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;">
        <i class="<?= $c['icon'] ?>"></i>
      </div>
      <div>
        <div class="kpi-value"><?= $c['value'] ?></div>
        <div class="kpi-label"><?= $c['label'] ?></div>
        <div class="kpi-sub"><i class="fas fa-arrow-trend-up"></i><?= $c['sub'] ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Revenue Chart + Quick Actions -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="glass-flat p-4 h-100">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Revenue Overview</h5>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;">Last 14 days</div>
        </div>
        <div style="background:var(--success-bg);color:var(--success);padding:4px 12px;border-radius:99px;font-size:0.8rem;font-weight:700;">
          Live
        </div>
      </div>
      <canvas id="revenueChart" height="140"></canvas>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="glass-flat p-4 h-100">
      <h5 style="font-family:var(--font-heading);font-weight:700;margin-bottom:16px;">Quick Actions</h5>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <a href="/admin/deposits?status=pending" class="btn btn-ghost" style="justify-content:flex-start;gap:12px;">
          <i class="fas fa-money-bill-wave" style="color:var(--warning);width:18px;"></i>
          Pending Deposits
          <?php if ($kpis['pending_deposits'] > 0): ?>
          <span class="nav-badge" style="margin-left:auto;"><?= $kpis['pending_deposits'] ?></span>
          <?php endif; ?>
        </a>
        <a href="/admin/support?status=open" class="btn btn-ghost" style="justify-content:flex-start;gap:12px;">
          <i class="fas fa-headset" style="color:var(--accent);width:18px;"></i>
          Open Tickets
          <?php if ($kpis['open_tickets'] > 0): ?>
          <span class="nav-badge" style="margin-left:auto;"><?= $kpis['open_tickets'] ?></span>
          <?php endif; ?>
        </a>
        <a href="/admin/services" class="btn btn-ghost" style="justify-content:flex-start;gap:12px;">
          <i class="fas fa-layer-group" style="color:var(--primary);width:18px;"></i>
          Manage Services
        </a>
        <button class="btn btn-ghost" style="justify-content:flex-start;gap:12px;" onclick="syncServices(this)">
          <i class="fas fa-rotate" style="color:var(--secondary);width:18px;"></i>
          Sync Services Now
        </button>
        <a href="/admin/broadcast" class="btn btn-ghost" style="justify-content:flex-start;gap:12px;">
          <i class="fas fa-bullhorn" style="color:var(--success);width:18px;"></i>
          Send Broadcast
        </a>
        <a href="/admin/coupons/create" class="btn btn-ghost" style="justify-content:flex-start;gap:12px;">
          <i class="fas fa-tag" style="color:var(--warning);width:18px;"></i>
          Create Coupon
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Signups -->
<div class="glass-flat mb-4">
  <div class="p-4 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
    <h5 style="font-family:var(--font-heading);font-weight:700;margin:0;">Recent Signups</h5>
    <a href="/admin/users" class="btn btn-ghost btn-sm">View All <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="table-wrap" style="border:none;border-radius:0;">
    <table class="table">
      <thead>
        <tr>
          <th>User</th><th>Email</th><th>Balance</th><th>Status</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--grad-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.85rem;flex-shrink:0;">
                <?= strtoupper(substr($u['username'], 0, 1)) ?>
              </div>
              <div>
                <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($u['username']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($u['full_name']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:0.85rem;color:var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
          <td style="font-weight:700;color:var(--success);">$<?= number_format((float)$u['balance'], 2) ?></td>
          <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
          <td style="font-size:0.82rem;color:var(--text-muted);"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="table-actions">
              <a href="/admin/users/<?= $u['id'] ?>" class="action-btn view" title="View"><i class="fas fa-eye"></i></a>
              <?php if ($u['status'] === 'active'): ?>
              <button class="action-btn ban" title="Ban" onclick="banUser(<?= $u['id'] ?>, this)"><i class="fas fa-ban"></i></button>
              <?php else: ?>
              <button class="action-btn check" title="Unban" onclick="unbanUser(<?= $u['id'] ?>, this)"><i class="fas fa-check"></i></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const chartData = <?= json_encode($revenueChart) ?>;
  const labels    = chartData.map(d => {
    const dt = new Date(d.day);
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  });

  const ctx = document.getElementById('revenueChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Revenue ($)',
          data: chartData.map(d => parseFloat(d.revenue)),
          backgroundColor: 'rgba(124,92,255,0.7)',
          borderColor: '#7C5CFF',
          borderWidth: 0,
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          label: 'Orders',
          data: chartData.map(d => parseInt(d.orders)),
          type: 'line',
          borderColor: '#00D4FF',
          backgroundColor: 'rgba(0,212,255,0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 0,
          yAxisID: 'y1',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: true, labels: { color: '#A8B3CF', usePointStyle: true, padding: 20 } },
        tooltip: {
          backgroundColor: 'rgba(10,12,20,0.95)',
          borderColor: 'rgba(124,92,255,0.3)',
          borderWidth: 1,
          padding: 12,
        }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#5A6580', font: { size: 11 } } },
        y: {
          position: 'left',
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#5A6580', callback: v => '$' + v }
        },
        y1: {
          position: 'right',
          grid: { drawOnChartArea: false },
          ticks: { color: '#00D4FF', font: { size: 10 } }
        }
      }
    }
  });
});
</script>
