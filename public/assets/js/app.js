/**
 * Shuvo SMM Panel — app.js
 * Global utilities: theme, particles, toasts, page loader, dropdowns
 */

'use strict';

// ── Page Loader ──────────────────────────────────────────────
window.addEventListener('load', () => {
  setTimeout(() => {
    const loader = document.getElementById('page-loader');
    if (loader) loader.classList.add('hidden');
  }, 600);
});

// ── Theme System ─────────────────────────────────────────────
const ThemeManager = (() => {
  const STORAGE_KEY = 'smm_theme';
  let current = localStorage.getItem(STORAGE_KEY)
    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

  function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    current = theme;
    localStorage.setItem(STORAGE_KEY, theme);
    const icon = document.getElementById('themeIcon');
    if (icon) {
      icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
  }

  function toggle() { apply(current === 'dark' ? 'light' : 'dark'); }
  function init()   { apply(current); }

  return { init, toggle, current: () => current };
})();

document.addEventListener('DOMContentLoaded', () => {
  ThemeManager.init();

  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) themeBtn.addEventListener('click', ThemeManager.toggle);

  // ── AOS Init ──────────────────────────────────────────────
  if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 600, easing: 'ease-out-quart', once: true, offset: 40 });
  }

  // ── Dropdowns ─────────────────────────────────────────────
  document.querySelectorAll('[data-dropdown]').forEach(trigger => {
    const target = document.getElementById(trigger.dataset.dropdown);
    if (!target) return;
    trigger.addEventListener('click', e => {
      e.stopPropagation();
      target.classList.toggle('show');
    });
  });

  // User menu
  const userMenuBtn  = document.getElementById('userMenuBtn');
  const userMenu     = document.getElementById('userMenu');
  const notifBtn     = document.getElementById('notifBtn');
  const notifDropdown= document.getElementById('notifDropdown');

  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', e => {
      e.stopPropagation();
      userMenu.classList.toggle('show');
      if (notifDropdown) notifDropdown.classList.remove('show');
    });
  }

  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      notifDropdown.classList.toggle('show');
      if (userMenu) userMenu.classList.remove('show');
      if (notifDropdown.classList.contains('show')) loadNotifications();
    });
  }

  document.addEventListener('click', () => {
    if (userMenu)      userMenu.classList.remove('show');
    if (notifDropdown) notifDropdown.classList.remove('show');
  });

  // ── Sidebar toggle ─────────────────────────────────────────
  const sidebar       = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const overlay       = document.getElementById('sidebarOverlay');
  const isMobile      = () => window.innerWidth < 992;

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        if (overlay) overlay.style.display = sidebar.classList.contains('mobile-open') ? 'block' : 'none';
      } else {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
      }
    });

    // Restore collapsed state on desktop
    if (!isMobile() && localStorage.getItem('sidebar_collapsed') === 'true') {
      sidebar.classList.add('collapsed');
    }
  }
});

window.closeSidebar = function() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.remove('mobile-open');
  if (overlay) overlay.style.display = 'none';
};

// ── Toast Notifications ───────────────────────────────────────
window.showToast = function(message, type = 'info', duration = 4000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = {
    success: 'fa-check-circle',
    error:   'fa-times-circle',
    warning: 'fa-exclamation-triangle',
    info:    'fa-info-circle',
  };

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="toast-icon fas ${icons[type] || icons.info}"></i>
    <span class="toast-message">${message}</span>
    <button onclick="this.closest('.toast').remove()"
            style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0;margin-left:8px;font-size:1rem;">
      ×
    </button>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('removing');
    toast.addEventListener('animationend', () => toast.remove());
  }, duration);
};

// ── Notifications Loader ──────────────────────────────────────
window.loadNotifications = function() {
  const list = document.getElementById('notifList');
  if (!list) return;

  list.innerHTML = '<div style="padding:20px;text-align:center;"><div class="spinner"></div></div>';

  fetch('/api/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.data.length) {
        list.innerHTML = '<div style="padding:30px;text-align:center;color:var(--text-muted);font-size:0.88rem;">No notifications</div>';
        return;
      }

      list.innerHTML = data.data.map(n => `
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;
                    background:${n.is_read ? 'transparent' : 'var(--primary-light)'};"
             onclick="markRead(${n.id})">
          <div style="width:8px;height:8px;border-radius:50%;background:${n.is_read ? 'transparent' : 'var(--primary)'};margin-top:6px;flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:0.88rem;font-weight:600;">${escHtml(n.title)}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(n.body || '')}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">${timeAgo(n.created_at)}</div>
          </div>
        </div>
      `).join('');
    });
};

window.markRead = function(id) {
  fetch('/api/notifications/read', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: `id=${id}&_csrf=${getCsrfToken()}`
  });
};

window.markAllRead = function() {
  fetch('/api/notifications/read', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: `_csrf=${getCsrfToken()}`
  }).then(() => loadNotifications());
};

// ── Copy to Clipboard ─────────────────────────────────────────
window.copyToClipboard = function(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const original = btn ? btn.innerHTML : null;
    if (btn) {
      btn.innerHTML = '<i class="fas fa-check" style="color:var(--success);"></i>';
      setTimeout(() => { btn.innerHTML = original; }, 1500);
    }
    showToast('Copied to clipboard!', 'success', 2000);
  });
};

// ── Particles Background ──────────────────────────────────────
(function initParticles() {
  const canvas = document.getElementById('particles-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let particles = [];
  let W, H;

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function Particle() {
    this.x  = Math.random() * W;
    this.y  = Math.random() * H;
    this.vx = (Math.random() - 0.5) * 0.3;
    this.vy = (Math.random() - 0.5) * 0.3;
    this.r  = Math.random() * 1.5 + 0.5;
    this.alpha = Math.random() * 0.4 + 0.1;
    const palette = ['#7C5CFF', '#00D4FF', '#FF4D9D'];
    this.color = palette[Math.floor(Math.random() * palette.length)];
  }

  Particle.prototype.update = function() {
    this.x += this.vx; this.y += this.vy;
    if (this.x < 0) this.x = W;
    if (this.x > W) this.x = 0;
    if (this.y < 0) this.y = H;
    if (this.y > H) this.y = 0;
  };

  function init() {
    resize();
    particles = Array.from({ length: 60 }, () => new Particle());
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);

    // Draw connections
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx*dx + dy*dy);

        if (dist < 120) {
          ctx.beginPath();
          ctx.strokeStyle = `rgba(124,92,255,${0.06 * (1 - dist / 120)})`;
          ctx.lineWidth = 0.5;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }
    }

    // Draw particles
    particles.forEach(p => {
      p.update();
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.color + Math.round(p.alpha * 255).toString(16).padStart(2, '0');
      ctx.fill();
    });

    requestAnimationFrame(draw);
  }

  init();
  draw();
  window.addEventListener('resize', () => { resize(); });
})();

// ── Utility Helpers ───────────────────────────────────────────
window.escHtml = function(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
};

window.timeAgo = function(dateStr) {
  const now  = new Date();
  const date = new Date(dateStr);
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60)   return 'just now';
  if (diff < 3600) return Math.floor(diff/60) + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return Math.floor(diff/86400) + 'd ago';
};

window.getCsrfToken = function() {
  const el = document.querySelector('[name="_csrf"]');
  return el ? el.value : '';
};

window.formatMoney = function(amount, decimals = 4) {
  return '$' + parseFloat(amount).toFixed(decimals);
};

// ── AJAX Form Helper ──────────────────────────────────────────
window.ajaxPost = async function(url, data, options = {}) {
  const body = data instanceof FormData ? data : new URLSearchParams(data);
  const res  = await fetch(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
    body,
  });
  return res.json();
};

// ── Confirm Modal Utility ─────────────────────────────────────
window.confirmAction = function(message, callback) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'Confirm',
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#7C5CFF',
      cancelButtonColor: '#333',
      background: '#0A0C14',
      color: '#fff',
    }).then(r => { if (r.isConfirmed) callback(); });
  } else if (confirm(message)) {
    callback();
  }
};
