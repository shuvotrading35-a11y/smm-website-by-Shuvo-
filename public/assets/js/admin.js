/**
 * Shuvo SMM Panel — admin.js
 * Admin panel specific interactions
 */

'use strict';

// ── User Management ───────────────────────────────────────────

window.banUser = async function(userId, btn) {
  confirmAction('Ban this user? They will lose access immediately.', async () => {
    btn.disabled = true;
    const data = await ajaxPost(`/admin/users/${userId}`, { _csrf: getCsrfToken(), action: 'ban' });
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
    else { showToast(data.message, 'error'); btn.disabled = false; }
  });
};

window.unbanUser = async function(userId, btn) {
  btn.disabled = true;
  const data = await ajaxPost(`/admin/users/${userId}`, { _csrf: getCsrfToken(), action: 'unban' });
  if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 800); }
  else { showToast(data.message, 'error'); btn.disabled = false; }
};

window.deleteUser = async function(userId, btn) {
  confirmAction('Permanently delete this user? This cannot be undone.', async () => {
    btn.disabled = true;
    const data = await ajaxPost(`/admin/users/${userId}`, { _csrf: getCsrfToken(), action: 'delete' });
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
    else { showToast(data.message, 'error'); btn.disabled = false; }
  });
};

window.openAdjustBalance = function(userId, username, currentBalance) {
  document.getElementById('adjustUserId').value    = userId;
  document.getElementById('adjustUsername').textContent  = username;
  document.getElementById('adjustCurrent').textContent   = '$' + parseFloat(currentBalance).toFixed(2);
  document.getElementById('balanceModal').classList.add('show');
};

window.submitBalanceAdjust = async function(btn) {
  const userId = document.getElementById('adjustUserId').value;
  const amount = document.getElementById('adjustAmount').value;
  const type   = document.getElementById('adjustType').value;
  const reason = document.getElementById('adjustReason').value.trim();

  if (!amount || !reason) { showToast('Amount and reason are required.', 'error'); return; }

  btn.disabled = true;
  const data = await ajaxPost(`/admin/users/${userId}/balance`, {
    _csrf: getCsrfToken(), amount, type, reason
  });

  if (data.success) {
    showToast(data.message, 'success');
    closeModal('balanceModal');
    setTimeout(() => location.reload(), 1000);
  } else {
    showToast(data.message, 'error');
    btn.disabled = false;
  }
};

// ── Deposit Management ────────────────────────────────────────

window.approveDeposit = function(depositId, method) {
  document.getElementById('approveDepositId').value = depositId;
  document.getElementById('approveMethod').textContent = method;
  document.getElementById('approveModal').classList.add('show');
};

window.submitApproval = async function(btn) {
  const depositId = document.getElementById('approveDepositId').value;
  const amount    = document.getElementById('approveAmount').value;

  if (!amount || parseFloat(amount) <= 0) { showToast('Enter valid amount.', 'error'); return; }

  btn.disabled = true;
  const data = await ajaxPost(`/admin/deposits/${depositId}/approve`, {
    _csrf: getCsrfToken(), amount
  });

  if (data.success) {
    showToast(data.message, 'success');
    closeModal('approveModal');
    setTimeout(() => location.reload(), 1000);
  } else {
    showToast(data.message, 'error');
    btn.disabled = false;
  }
};

window.rejectDeposit = function(depositId) {
  document.getElementById('rejectDepositId').value = depositId;
  document.getElementById('rejectModal').classList.add('show');
};

window.submitRejection = async function(btn) {
  const depositId = document.getElementById('rejectDepositId').value;
  const reason    = document.getElementById('rejectReason').value.trim();

  if (!reason) { showToast('Rejection reason required.', 'error'); return; }

  btn.disabled = true;
  const data = await ajaxPost(`/admin/deposits/${depositId}/reject`, {
    _csrf: getCsrfToken(), reason
  });

  if (data.success) {
    showToast(data.message, 'success');
    closeModal('rejectModal');
    setTimeout(() => location.reload(), 1000);
  } else {
    showToast(data.message, 'error');
    btn.disabled = false;
  }
};

window.viewProof = function(src) {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
  const img = document.createElement('img');
  img.src = src;
  img.style.cssText = 'max-width:90vw;max-height:90vh;border-radius:12px;box-shadow:0 0 60px rgba(0,0,0,0.8);';
  overlay.appendChild(img);
  overlay.onclick = () => overlay.remove();
  document.body.appendChild(overlay);
};

// ── Services Management ───────────────────────────────────────

window.syncServices = async function(btn) {
  confirmAction('Sync all services from provider? This may take 30–60 seconds.', async () => {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Syncing...';
    const data = await ajaxPost('/admin/services/sync', { _csrf: getCsrfToken() });
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1500); }
    else { showToast(data.message || 'Sync failed.', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-rotate"></i> Sync Services'; }
  });
};

window.toggleService = async function(serviceId, btn) {
  const isActive = btn.dataset.active === '1';
  const data = await ajaxPost(`/admin/services/${serviceId}`, {
    _csrf: getCsrfToken(),
    is_active: isActive ? '0' : '1'
  });
  if (data.success) {
    btn.dataset.active = isActive ? '0' : '1';
    btn.innerHTML = isActive
      ? '<i class="fas fa-eye-slash"></i>'
      : '<i class="fas fa-eye"></i>';
    btn.title = isActive ? 'Enable' : 'Disable';
    showToast('Service ' + (isActive ? 'disabled' : 'enabled') + '.', 'success');
  }
};

window.openServiceEdit = function(serviceId, name, markup, markupType) {
  document.getElementById('editServiceId').value       = serviceId;
  document.getElementById('editServiceName').value     = name;
  document.getElementById('editMarkupValue').value     = markup;
  document.getElementById('editMarkupType').value      = markupType;
  document.getElementById('serviceEditModal').classList.add('show');
};

window.submitServiceEdit = async function(btn) {
  const serviceId   = document.getElementById('editServiceId').value;
  const customName  = document.getElementById('editServiceName').value.trim();
  const markupValue = document.getElementById('editMarkupValue').value;
  const markupType  = document.getElementById('editMarkupType').value;

  btn.disabled = true;
  const data = await ajaxPost(`/admin/services/${serviceId}`, {
    _csrf: getCsrfToken(), custom_name: customName, markup_value: markupValue, markup_type: markupType
  });
  if (data.success) { showToast(data.message, 'success'); closeModal('serviceEditModal'); setTimeout(() => location.reload(), 800); }
  else { showToast(data.message, 'error'); btn.disabled = false; }
};

// ── Orders Management ─────────────────────────────────────────

window.updateOrderStatus = async function(orderId, select) {
  const status = select.value;
  const data = await ajaxPost(`/admin/orders/${orderId}`, { _csrf: getCsrfToken(), status });
  if (data.success) showToast('Order status updated.', 'success');
  else { showToast(data.message, 'error'); }
};

window.resyncOrders = async function(btn) {
  const selected = getSelectedIds('orderCheckbox');
  if (!selected.length) { showToast('Select at least one order.', 'error'); return; }
  btn.disabled = true;
  const data = await ajaxPost('/admin/orders/bulk', {
    _csrf: getCsrfToken(), action: 'resync',
    'order_ids[]': selected
  });
  showToast(data.success ? 'Resync queued.' : data.message, data.success ? 'success' : 'error');
  btn.disabled = false;
};

// ── Coupon Management ─────────────────────────────────────────

window.toggleCoupon = async function(couponId, btn) {
  const data = await ajaxPost(`/admin/coupons/${couponId}/toggle`, { _csrf: getCsrfToken() });
  if (data.success) {
    const isActive = btn.classList.contains('badge-active');
    btn.className = isActive ? 'badge badge-cancelled' : 'badge badge-active';
    btn.textContent = isActive ? 'Inactive' : 'Active';
    showToast('Coupon toggled.', 'success');
  }
};

// ── Settings ──────────────────────────────────────────────────

window.saveSettings = async function(btn) {
  const form = document.getElementById('settingsForm');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Saving...';

  const data = await fetch('/admin/settings', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(form)
  }).then(r => r.json());

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Settings';
  showToast(data.success ? data.message : (data.message || 'Save failed.'), data.success ? 'success' : 'error');
};

// ── Broadcast ─────────────────────────────────────────────────

window.sendBroadcast = async function(btn) {
  const title    = document.getElementById('broadcastTitle').value.trim();
  const body     = document.getElementById('broadcastBody').value.trim();
  const channels = [...document.querySelectorAll('input[name="channels[]"]:checked')].map(el => el.value);

  if (!title || !body) { showToast('Title and body required.', 'error'); return; }
  if (!channels.length) { showToast('Select at least one channel.', 'error'); return; }

  confirmAction(`Send broadcast to all users via ${channels.join(', ')}?`, async () => {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Sending...';
    const data = await ajaxPost('/admin/broadcast', {
      _csrf: getCsrfToken(), title, body,
      'channels[]': channels, target_type: 'all'
    });
    if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
    else { showToast(data.message, 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Broadcast'; }
  });
};

// ── Admin Support Reply ───────────────────────────────────────

window.adminReply = async function(ticketId, btn) {
  const body     = document.getElementById('adminReplyBody').value.trim();
  const status   = document.getElementById('ticketStatusSelect').value;
  const assignTo = document.getElementById('assignSelect')?.value;

  if (!body) { showToast('Reply body required.', 'error'); return; }

  btn.disabled = true;
  const data = await ajaxPost(`/admin/support/${ticketId}`, {
    _csrf: getCsrfToken(), body, status, assign_to: assignTo
  });
  if (data.success) { showToast('Reply sent.', 'success'); setTimeout(() => location.reload(), 800); }
  else { showToast(data.message, 'error'); btn.disabled = false; }
};

// ── Blog ──────────────────────────────────────────────────────

window.deletePost = async function(postId, btn) {
  confirmAction('Delete this blog post permanently?', async () => {
    btn.disabled = true;
    const data = await ajaxPost(`/admin/blog/${postId}/delete`, { _csrf: getCsrfToken() });
    if (data.success) { showToast('Post deleted.', 'success'); btn.closest('tr').remove(); }
    else { showToast(data.message, 'error'); btn.disabled = false; }
  });
};

// ── Bulk Selection ────────────────────────────────────────────

window.toggleSelectAll = function(masterCheckbox, className) {
  document.querySelectorAll('.' + className).forEach(cb => {
    cb.checked = masterCheckbox.checked;
  });
};

window.getSelectedIds = function(className) {
  return [...document.querySelectorAll('.' + className + ':checked')].map(cb => cb.value);
};

// ── Modal Utilities ───────────────────────────────────────────

window.closeModal = function(id) {
  document.getElementById(id)?.classList.remove('show');
};

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
  }
});

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
  }
});
