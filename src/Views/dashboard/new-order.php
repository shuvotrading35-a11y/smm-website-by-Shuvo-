<?php /* New Order View */ ?>

<style>
.order-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.order-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); font-family: var(--font-heading); font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 10px; }
.order-card-body { padding: 24px; }
.service-option { padding: 14px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all var(--transition); margin-bottom: 8px; }
.service-option:hover { border-color: rgba(124,92,255,0.4); background: var(--surface-hover); }
.service-option.selected { border-color: var(--primary); background: var(--primary-light); }
.service-badges span { display: inline-block; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.72rem; font-weight: 600; margin-right: 4px; }
.refill-yes  { background: var(--success-bg); color: var(--success); }
.refill-no   { background: var(--danger-bg);  color: var(--danger); }
.step-indicator { display: flex; gap: 8px; margin-bottom: 28px; }
.step { flex: 1; height: 4px; border-radius: 2px; background: var(--border); transition: background 0.3s; }
.step.active { background: var(--grad-primary); }
</style>

<div class="page-header">
  <div>
    <h1 class="page-title"><i class="fas fa-cart-plus" style="color:var(--primary);"></i> New Order</h1>
    <p class="page-subtitle">Choose a service and place your order below</p>
  </div>
  <div class="topbar-balance">
    <i class="fas fa-wallet"></i> Balance: $<?= number_format((float)$user['balance'], 2) ?>
  </div>
</div>

<!-- Step Progress -->
<div class="step-indicator mb-4">
  <div class="step active" id="step1"></div>
  <div class="step" id="step2"></div>
  <div class="step" id="step3"></div>
</div>

<form id="orderForm" onsubmit="return false;">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="service_id" id="selectedServiceId">
  <input type="hidden" name="coupon_id"  id="appliedCouponId">

  <div class="row g-4">
    <!-- Left: Service Selection -->
    <div class="col-lg-7">

      <!-- Category Picker -->
      <div class="order-card mb-4">
        <div class="order-card-header">
          <i class="fas fa-layer-group" style="color:var(--primary);"></i>
          Step 1 — Choose Category
        </div>
        <div class="order-card-body">
          <div style="display:flex;flex-wrap:wrap;gap:8px;" id="categoryBtns">
            <?php foreach ($categories as $cat): ?>
            <button type="button"
              class="btn btn-ghost btn-sm category-btn"
              data-id="<?= $cat['id'] ?>"
              data-name="<?= htmlspecialchars($cat['name']) ?>"
              onclick="selectCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">
              <i class="<?= htmlspecialchars($cat['icon'] ?? 'fas fa-globe') ?>"
                 style="color:<?= htmlspecialchars($cat['color'] ?? 'var(--primary)') ?>;"></i>
              <?= htmlspecialchars($cat['name']) ?>
              <span style="font-size:0.75rem;color:var(--text-muted);">(<?= $cat['service_count'] ?>)</span>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Service Search + List -->
      <div class="order-card mb-4">
        <div class="order-card-header">
          <i class="fas fa-list" style="color:var(--secondary);"></i>
          Step 2 — Select Service
        </div>
        <div class="order-card-body">
          <div class="input-icon-wrap mb-3">
            <i class="input-icon fas fa-search"></i>
            <input type="text" class="form-control" id="serviceSearch" placeholder="Search services..." oninput="filterServices()">
          </div>

          <div id="serviceList">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
              <i class="fas fa-arrow-up" style="font-size:2rem;opacity:0.3;"></i>
              <p style="margin-top:12px;font-size:0.9rem;">Select a category above to load services</p>
            </div>
          </div>

          <!-- Loading skeleton -->
          <div id="serviceLoading" style="display:none;">
            <?php for($i=0;$i<4;$i++): ?>
            <div style="height:72px;border-radius:var(--radius);margin-bottom:8px;" class="skeleton"></div>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <!-- Service Info Box -->
      <div id="serviceInfoBox" class="order-card" style="display:none;">
        <div class="order-card-header">
          <i class="fas fa-info-circle" style="color:var(--info);"></i>
          Service Details
        </div>
        <div class="order-card-body">
          <div id="serviceDescription" class="service-info-box"></div>
          <div class="row g-3 mt-2">
            <div class="col-6 col-sm-3">
              <div style="background:var(--surface);border-radius:var(--radius);padding:12px;text-align:center;">
                <div style="font-size:0.75rem;color:var(--text-muted);">Min</div>
                <div style="font-weight:700;font-size:1.1rem;" id="serviceMin">—</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div style="background:var(--surface);border-radius:var(--radius);padding:12px;text-align:center;">
                <div style="font-size:0.75rem;color:var(--text-muted);">Max</div>
                <div style="font-weight:700;font-size:1.1rem;" id="serviceMax">—</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div style="background:var(--surface);border-radius:var(--radius);padding:12px;text-align:center;">
                <div style="font-size:0.75rem;color:var(--text-muted);">Rate/1K</div>
                <div style="font-weight:700;font-size:1.1rem;color:var(--primary);" id="serviceRate">—</div>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <div style="background:var(--surface);border-radius:var(--radius);padding:12px;text-align:center;">
                <div style="font-size:0.75rem;color:var(--text-muted);">Refill</div>
                <div style="font-weight:700;" id="serviceRefill">—</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Order Form -->
    <div class="col-lg-5">
      <div class="order-card" style="position:sticky;top:calc(var(--topbar-h) + 16px);">
        <div class="order-card-header">
          <i class="fas fa-pen-to-square" style="color:var(--accent);"></i>
          Step 3 — Order Details
        </div>
        <div class="order-card-body">

          <div class="form-group">
            <label class="form-label">Target Link / Username</label>
            <div class="input-icon-wrap">
              <i class="input-icon fas fa-link"></i>
              <input type="url" class="form-control" name="link" id="orderLink" placeholder="https://..." required>
            </div>
            <div class="form-text" id="linkHint">Enter the full URL</div>
          </div>

          <div class="form-group">
            <label class="form-label">Quantity</label>
            <div style="display:flex;gap:8px;">
              <button type="button" class="btn btn-ghost btn-icon" onclick="adjustQty(-100)"><i class="fas fa-minus"></i></button>
              <input type="number" class="form-control" name="quantity" id="orderQty"
                     placeholder="100" min="10" max="1000000"
                     oninput="recalcPrice()" style="text-align:center;font-weight:700;">
              <button type="button" class="btn btn-ghost btn-icon" onclick="adjustQty(100)"><i class="fas fa-plus"></i></button>
            </div>
            <div class="form-text" id="qtyRange">Min: — / Max: —</div>
          </div>

          <!-- Price Calculator -->
          <div class="price-calc-box" id="priceCalc" style="display:none;">
            <div class="price-row">
              <span class="label">Quantity</span>
              <span class="value" id="calcQty">—</span>
            </div>
            <div class="price-row">
              <span class="label">Rate per 1000</span>
              <span class="value" id="calcRate">—</span>
            </div>
            <div class="price-row" id="discountRow" style="display:none;">
              <span class="label" style="color:var(--success);">Coupon Discount</span>
              <span class="value" style="color:var(--success);" id="calcDiscount">—</span>
            </div>
            <div class="price-row total">
              <span class="label">Total Cost</span>
              <span class="value" id="calcTotal">$0.0000</span>
            </div>
            <div class="price-row">
              <span class="label">Your Balance</span>
              <span class="value" id="calcBalance">$<?= number_format((float)$user['balance'], 2) ?></span>
            </div>
            <div class="price-row">
              <span class="label">After Order</span>
              <span class="value" id="calcRemain" style="color:var(--success);">—</span>
            </div>
          </div>

          <!-- Coupon -->
          <div class="form-group mt-3">
            <label class="form-label">Coupon Code (optional)</label>
            <div style="display:flex;gap:8px;">
              <input type="text" class="form-control" id="couponInput" name="coupon" placeholder="WELCOME10"
                     oninput="this.value=this.value.toUpperCase()">
              <button type="button" class="btn btn-outline btn-sm" onclick="applyCoupon()" style="white-space:nowrap;">Apply</button>
            </div>
            <div id="couponMsg" style="font-size:0.82rem;margin-top:6px;"></div>
          </div>

          <!-- Submit -->
          <button type="button" class="btn btn-aurora w-100 btn-lg mt-2" id="placeOrderBtn" onclick="placeOrder()" disabled>
            <i class="fas fa-rocket"></i> Place Order
          </button>

          <div id="orderError" class="mt-2" style="display:none;"></div>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Order Success Modal -->
<div class="modal-overlay" id="successModal">
  <div class="modal" style="text-align:center;">
    <div class="modal-body py-5">
      <div style="width:72px;height:72px;border-radius:50%;background:var(--success-bg);border:2px solid var(--success);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:var(--success);">
        <i class="fas fa-check"></i>
      </div>
      <h4 style="font-family:var(--font-heading);font-weight:700;">Order Placed!</h4>
      <p style="color:var(--text-muted);margin:12px 0;" id="successMsg"></p>
      <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;">
        <a href="/dashboard/orders" class="btn btn-primary">Track Order</a>
        <button class="btn btn-ghost" onclick="resetForm()">New Order</button>
      </div>
    </div>
  </div>
</div>

<script>
const userBalance = <?= (float)$user['balance'] ?>;
let selectedService = null;
let allServices = [];
let couponDiscount = 0;

function selectCategory(id, name) {
  document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('btn-primary'));
  event.currentTarget.classList.add('btn-primary');
  event.currentTarget.classList.remove('btn-ghost');
  document.getElementById('serviceList').innerHTML = '';
  document.getElementById('serviceLoading').style.display = 'block';
  document.getElementById('step1').style.background = 'var(--grad-primary)';
  document.getElementById('step2').style.background = 'var(--border)';

  fetch(`/api/services?category_id=${id}`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('serviceLoading').style.display = 'none';
      if (data.success) {
        allServices = data.data;
        renderServices(allServices);
      }
    });
}

function renderServices(services) {
  const container = document.getElementById('serviceList');
  if (!services.length) {
    container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-muted);">No services found</div>';
    return;
  }

  container.innerHTML = services.map(s => `
    <div class="service-option" data-id="${s.id}" onclick="selectService(${s.id})">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
        <div style="flex:1;min-width:0;">
          <div style="font-size:0.9rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${s.name}</div>
          <div style="margin-top:4px;">
            <span style="font-size:0.75rem;color:var(--text-muted);">ID: ${s.api_service_id}</span>
            <span style="display:inline-block;padding:2px 8px;background:var(--primary-light);color:var(--primary);border-radius:99px;font-size:0.72rem;font-weight:700;margin-left:6px;">$${parseFloat(s.user_rate).toFixed(4)}/1K</span>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
          ${s.refill ? '<span style="background:var(--success-bg);color:var(--success);padding:2px 7px;border-radius:99px;font-size:0.7rem;font-weight:600;">♻ Refill</span>' : ''}
          ${s.cancel ? '<span style="background:var(--danger-bg);color:var(--danger);padding:2px 7px;border-radius:99px;font-size:0.7rem;font-weight:600;margin-left:3px;">✕ Cancel</span>' : ''}
        </div>
      </div>
    </div>
  `).join('');
}

function filterServices() {
  const q = document.getElementById('serviceSearch').value.toLowerCase();
  renderServices(allServices.filter(s => s.name.toLowerCase().includes(q)));
}

function selectService(id) {
  selectedService = allServices.find(s => s.id === id);
  if (!selectedService) return;

  document.querySelectorAll('.service-option').forEach(el => el.classList.remove('selected'));
  document.querySelector(`.service-option[data-id="${id}"]`)?.classList.add('selected');

  document.getElementById('selectedServiceId').value = id;
  document.getElementById('step2').style.background = 'var(--grad-primary)';

  // Update info box
  document.getElementById('serviceInfoBox').style.display = 'block';
  document.getElementById('serviceDescription').textContent = selectedService.description || 'No description available.';
  document.getElementById('serviceMin').textContent = parseInt(selectedService.min_quantity).toLocaleString();
  document.getElementById('serviceMax').textContent = parseInt(selectedService.max_quantity).toLocaleString();
  document.getElementById('serviceRate').textContent = '$' + parseFloat(selectedService.user_rate).toFixed(4);
  document.getElementById('serviceRefill').innerHTML = selectedService.refill
    ? '<span style="color:var(--success);">✓ Yes</span>'
    : '<span style="color:var(--danger);">✗ No</span>';

  // Set quantity constraints
  const qtyInput = document.getElementById('orderQty');
  qtyInput.min = selectedService.min_quantity;
  qtyInput.max = selectedService.max_quantity;
  qtyInput.value = selectedService.min_quantity;
  document.getElementById('qtyRange').textContent = `Min: ${parseInt(selectedService.min_quantity).toLocaleString()} / Max: ${parseInt(selectedService.max_quantity).toLocaleString()}`;

  // Link hint
  const hints = {
    'Instagram': 'https://instagram.com/username or post URL',
    'YouTube':   'https://youtube.com/channel/... or video URL',
    'TikTok':    'https://tiktok.com/@username',
    'Twitter/X': 'https://twitter.com/username or tweet URL',
    'Telegram':  'https://t.me/username',
  };
  // Scroll order form into view on mobile
  document.getElementById('orderLink').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  recalcPrice();
  document.getElementById('placeOrderBtn').disabled = false;
}

function adjustQty(delta) {
  if (!selectedService) return;
  const input = document.getElementById('orderQty');
  let val = parseInt(input.value || 0) + delta;
  val = Math.max(selectedService.min_quantity, Math.min(selectedService.max_quantity, val));
  input.value = val;
  recalcPrice();
}

function recalcPrice() {
  if (!selectedService) return;
  const qty  = parseInt(document.getElementById('orderQty').value) || 0;
  const rate = parseFloat(selectedService.user_rate);
  const cost = qty * rate / 1000;
  const final = Math.max(0, cost - couponDiscount);
  const remain = userBalance - final;

  document.getElementById('priceCalc').style.display = 'block';
  document.getElementById('calcQty').textContent   = qty.toLocaleString();
  document.getElementById('calcRate').textContent  = '$' + rate.toFixed(4);
  document.getElementById('calcTotal').textContent = '$' + final.toFixed(4);
  document.getElementById('calcBalance').textContent = '$' + userBalance.toFixed(2);
  document.getElementById('calcRemain').textContent  = '$' + remain.toFixed(4);
  document.getElementById('calcRemain').style.color  = remain >= 0 ? 'var(--success)' : 'var(--danger)';

  if (couponDiscount > 0) {
    document.getElementById('discountRow').style.display = 'flex';
    document.getElementById('calcDiscount').textContent  = '-$' + couponDiscount.toFixed(4);
  }

  document.getElementById('step3').style.background = qty > 0 ? 'var(--grad-primary)' : 'var(--border)';
}

function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim();
  const qty  = parseInt(document.getElementById('orderQty').value) || 0;
  if (!code || !selectedService) return;

  const cost = qty * parseFloat(selectedService.user_rate) / 1000;
  const msgEl = document.getElementById('couponMsg');

  fetch('/dashboard/coupons/apply', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({
      _csrf: document.querySelector('[name="_csrf"]').value,
      code, amount: cost
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      couponDiscount = data.discount;
      document.getElementById('appliedCouponId').value = data.coupon_id;
      msgEl.style.color = 'var(--success)';
      msgEl.textContent = '✓ ' + data.message;
      recalcPrice();
    } else {
      msgEl.style.color = 'var(--danger)';
      msgEl.textContent = '✗ ' + data.message;
    }
  });
}

function placeOrder() {
  const btn  = document.getElementById('placeOrderBtn');
  const link = document.getElementById('orderLink').value.trim();
  const qty  = parseInt(document.getElementById('orderQty').value);

  if (!selectedService) { showToast('Please select a service.', 'error'); return; }
  if (!link)  { showToast('Please enter a target link.', 'error'); return; }
  if (qty < selectedService.min_quantity || qty > selectedService.max_quantity) {
    showToast(`Quantity must be ${selectedService.min_quantity}–${selectedService.max_quantity}`, 'error'); return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Placing Order...';

  const formData = new FormData(document.getElementById('orderForm'));

  fetch('/dashboard/new-order', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('successMsg').textContent = data.message;
      document.getElementById('successModal').classList.add('show');
    } else {
      showToast(data.message, 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-rocket"></i> Place Order';
    }
  });
}

function resetForm() {
  document.getElementById('successModal').classList.remove('show');
  selectedService = null; couponDiscount = 0;
  document.getElementById('orderForm').reset();
  document.getElementById('priceCalc').style.display = 'none';
  document.getElementById('serviceInfoBox').style.display = 'none';
  document.getElementById('placeOrderBtn').disabled = true;
  document.getElementById('placeOrderBtn').innerHTML = '<i class="fas fa-rocket"></i> Place Order';
  document.querySelectorAll('.step').forEach(s => s.style.background = 'var(--border)');
  document.getElementById('step1').style.background = 'var(--grad-primary)';
}

// Pre-select service if passed via URL
<?php if ($preService): ?>
selectService(<?= (int)$preService['id'] ?>);
<?php endif; ?>
</script>
