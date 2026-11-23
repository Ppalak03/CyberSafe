// Shared UI: toasts, mobile menu, password visibility, confirm dialogs.
(function () {
  'use strict';

  // Apply the saved reduce-motion preference on every page.
  try { if (localStorage.getItem('cs_reduce_motion') === '1') document.documentElement.dataset.reduceMotion = '1'; } catch (e) { /* ignore */ }

  const toasts = document.getElementById('toasts');
  window.showToast = function (type, message) {
    if (!toasts || !message) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const text = document.createElement('span');
    text.textContent = message; // textContent: never inject HTML
    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss');
    close.textContent = '×';
    close.addEventListener('click', () => el.remove());
    el.append(text, close);
    toasts.appendChild(el);
    setTimeout(() => el.remove(), 5000);
  };

  // Server flash messages arrive as JSON on <body data-flash>.
  try {
    const flashes = JSON.parse(document.body.dataset.flash || '{}');
    Object.entries(flashes).forEach(([type, msg]) => window.showToast(type, msg));
  } catch (e) { /* ignore malformed flash */ }

  // Warn shortly before the server-side inactivity timeout ends the session.
  const limit = (window.CYBERSAFE && window.CYBERSAFE.timeout) || 0;
  if (limit > 180) {
    setTimeout(() => window.showToast('warning', 'Your session is about to expire. Refresh the page to stay signed in.'), (limit - 120) * 1000);
  }

  // Mobile sidebar
  const toggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  function setMenu(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (backdrop) backdrop.hidden = !open;
    if (toggle) toggle.setAttribute('aria-expanded', String(open));
  }
  if (toggle) toggle.addEventListener('click', () => setMenu(!sidebar.classList.contains('open')));
  if (backdrop) backdrop.addEventListener('click', () => setMenu(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

  // Password show/hide
  document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePassword);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  // Confirmation dialogs: <button data-confirm="Are you sure?">
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-confirm]');
    if (el && !window.confirm(el.dataset.confirm)) e.preventDefault();
  });
})();
