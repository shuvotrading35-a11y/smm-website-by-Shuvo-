/**
 * Shuvo SMM Panel — dashboard.js
 * Dashboard-specific interactions
 */

'use strict';

// ── Chart defaults (dark theme) ───────────────────────────────
if (typeof Chart !== 'undefined') {
  Chart.defaults.color           = '#5A6580';
  Chart.defaults.borderColor     = 'rgba(255,255,255,0.06)';
  Chart.defaults.font.family     = 'Inter, sans-serif';
}

// ── Order Status Badge Colors ─────────────────────────────────
const STATUS_COLORS = {
  pending:     '#FFD600',
  processing:  '#00D4FF',
  in_progress: '#00D4FF',
  completed:   '#00E676',
  partial:     '#aaaaaa',
  cancelled:   '#FF4B55',
  refunded:    '#7C5CFF',
  error:       '#FF4B55',
};

// ── Orders Page: Filter & Search ──────────────────────────────
window.applyFilters = function() {
  const form    = document.getElementById('filterForm');
  const params  = new URLSearchParams(new FormData(form));
  window.location.href = '/dashboard/orders?' + params.toString();
};

window.clearFilters = function() {
  window.location.href = '/dashboard/orders';
};

// ── Order Detail: Refill ──────────────────────────────────────
window.requestRefill = async function(orderId, btn) {
  if (!confirm('Request a refill for this order?')) return;

  btn.disabled = true;
  const data = await ajaxPost(`/dashboard/orders/${orderId}/refill`, { _csrf: getCsrfToken() });

  if (data.success) {
    showToast(data.message, 'success');
  } else {
    showToast(data.message, 'error');
    btn.disabled = false;
  }
};

// ── Order Detail: Cancel ──────────────────────────────────────
window.cancelOrder = async function(orderId, btn) {
  confirmAction('Cancel this order? Any eligible refund will be credited automatically.', async () => {
    btn.disabled = true;
    const data = await ajaxPost(`/dashboard/orders/${orderId}/cancel`, { _csrf: getCsrfToken() });

    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(data.message, 'error');
      btn.disabled = false;
    }
  });
};

// ── Favorite Toggle ───────────────────────────────────────────
window.toggleFavorite = async function(serviceId, btn) {
  const data = await ajaxPost('/dashboard/favorites/toggle', {
    _csrf: getCsrfToken(),
    service_id: serviceId,
  });

  if (data.success) {
    const icon = btn.querySelector('i');
    if (data.action === 'added') {
      icon.className = 'fas fa-heart';
      icon.style.color = 'var(--accent)';
      showToast('Added to favorites', 'success', 2000);
    } else {
      icon.className = 'far fa-heart';
      icon.style.color = '';
      showToast('Removed from favorites', 'info', 2000);
    }
  }
};

// ── Add Funds: Payment Method Switcher ────────────────────────
window.selectPaymentMethod = function(method) {
  document.querySelectorAll('.payment-method-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.payment-panel').forEach(p => p.style.display = 'none');

  const tab   = document.getElementById('tab-' + method);
  const panel = document.getElementById('panel-' + method);

  if (tab)   tab.classList.add('active');
  if (panel) panel.style.display = 'block';

  document.getElementById('paymentMethodInput').value = method;
};

// ── Add Funds: Submit ─────────────────────────────────────────
window.submitDeposit = async function(btn) {
  const form = document.getElementById('depositForm');
  const formData = new FormData(form);

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Submitting...';

  const data = await fetch('/dashboard/add-funds', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData,
  }).then(r => r.json());

  if (data.success) {
    showToast(data.message, 'success', 6000);
    form.reset();
    setTimeout(() => location.reload(), 2000);
  } else {
    showToast(data.message || 'Submission failed.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Payment Proof';
  }
};

// ── Settings: Tab Switcher ────────────────────────────────────
window.switchSettingsTab = function(tab) {
  document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');

  document.getElementById('tab-' + tab)?.classList.add('active');
  document.getElementById('panel-' + tab).style.display = 'block';
};

// ── API Key: Reveal/Hide ──────────────────────────────────────
window.toggleApiKey = function(btn) {
  const display = document.getElementById('apiKeyDisplay');
  const icon    = btn.querySelector('i');
  const full    = display.dataset.full;
  const masked  = display.dataset.masked;

  if (display.textContent === masked) {
    display.textContent = full;
    icon.className = 'fas fa-eye-slash';
  } else {
    display.textContent = masked;
    icon.className = 'fas fa-eye';
  }
};

// ── Support: Ticket Reply ─────────────────────────────────────
window.submitReply = async function(ticketId, btn) {
  const body = document.getElementById('replyBody').value.trim();
  if (!body) { showToast('Please enter a message.', 'error'); return; }

  btn.disabled = true;
  const data = await ajaxPost(`/dashboard/support/${ticketId}/reply`, {
    _csrf: getCsrfToken(),
    body,
  });

  if (data.success) {
    showToast('Reply sent.', 'success');
    setTimeout(() => location.reload(), 800);
  } else {
    showToast(data.message, 'error');
    btn.disabled = false;
  }
};

// ── Chart Range Toggle ────────────────────────────────────────
window.setChartRange = function(days) {
  document.querySelectorAll('[onclick^="setChartRange"]').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
  // In a real implementation, fetch new data via AJAX
  showToast(`Showing last ${days} days`, 'info', 1500);
};

// ── Order Status Checker ──────────────────────────────────────
window.checkOrderStatus = async function(btn) {
  const input    = document.getElementById('statusOrderIds').value.trim();
  const resultEl = document.getElementById('statusResults');

  if (!input) { showToast('Enter at least one Order ID', 'error'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Checking...';

  const data = await ajaxPost('/dashboard/order-status', {
    _csrf:     getCsrfToken(),
    order_ids: input,
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-search"></i> Check Status';

  if (data.success) {
    resultEl.style.display = 'block';
    resultEl.innerHTML = data.data.map(o => `
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:10px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
          <div>
            <span style="font-weight:700;color:var(--primary);">#${o.id}</span>
            <span style="margin-left:10px;font-size:0.88rem;color:var(--text-secondary);">${escHtml(o.service_name)}</span>
          </div>
          <span class="badge badge-${o.status}">${o.status.charAt(0).toUpperCase() + o.status.slice(1).replace('_',' ')}</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:12px;">
          <div style="text-align:center;background:var(--bg);border-radius:8px;padding:8px;">
            <div style="font-size:0.72rem;color:var(--text-muted);">Charge</div>
            <div style="font-weight:700;color:var(--text);">$${parseFloat(o.charge).toFixed(4)}</div>
          </div>
          <div style="text-align:center;background:var(--bg);border-radius:8px;padding:8px;">
            <div style="font-size:0.72rem;color:var(--text-muted);">Start Count</div>
            <div style="font-weight:700;">${o.start_count ?? '—'}</div>
          </div>
          <div style="text-align:center;background:var(--bg);border-radius:8px;padding:8px;">
            <div style="font-size:0.72rem;color:var(--text-muted);">Remains</div>
            <div style="font-weight:700;">${o.remains ?? '—'}</div>
          </div>
          <div style="text-align:center;background:var(--bg);border-radius:8px;padding:8px;">
            <div style="font-size:0.72rem;color:var(--text-muted);">Placed</div>
            <div style="font-weight:700;font-size:0.8rem;">${new Date(o.created_at).toLocaleDateString()}</div>
          </div>
        </div>
      </div>
    `).join('');
  } else {
    showToast(data.message, 'error');
  }
};
