(function () {
  'use strict';

  const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  window.App = {
    csrfToken,
    toast,
    confirmDialog,
    fetchJson,
    debounce,
    formatMoney,
    setLoading,
  };

  function toast(message, type = 'info', duration = 4200) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container';
      container.setAttribute('aria-live', 'polite');
      document.body.appendChild(container);
    }
    const el = document.createElement('div');
    el.className = 'toast toast--' + (type === 'success' || type === 'error' || type === 'warn' ? type : '');
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity 0.2s';
      setTimeout(() => el.remove(), 220);
    }, duration);
  }

  function confirmDialog(message, title = 'Confirm') {
    return new Promise((resolve) => {
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop is-open';
      backdrop.innerHTML =
        '<div class="modal" role="dialog" aria-modal="true">' +
        '<h3 class="modal__title">' +
        escapeHtml(title) +
        '</h3>' +
        '<p class="text-muted">' +
        escapeHtml(message) +
        '</p>' +
        '<div class="btn-group mt-2">' +
        '<button type="button" class="btn btn--secondary" data-action="cancel">Cancel</button>' +
        '<button type="button" class="btn btn--primary" data-action="ok">Confirm</button>' +
        '</div></div>';
      document.body.appendChild(backdrop);
      const close = (result) => {
        backdrop.remove();
        resolve(result);
      };
      backdrop.querySelector('[data-action="cancel"]').addEventListener('click', () => close(false));
      backdrop.querySelector('[data-action="ok"]').addEventListener('click', () => close(true));
      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) close(false);
      });
    });
  }

  async function fetchJson(url, options = {}) {
    const headers = Object.assign(
      {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      options.headers || {}
    );
    if (options.method && options.method.toUpperCase() !== 'GET') {
      headers['X-CSRF-TOKEN'] = csrfToken();
    }
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    const response = await fetch(url, Object.assign({}, options, { headers }));
    let data = null;
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
      data = await response.json();
    }
    if (!response.ok) {
      const msg = (data && (data.message || data.error)) || 'Request failed.';
      throw Object.assign(new Error(msg), { status: response.status, data });
    }
    return data;
  }

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function formatMoney(amount) {
    const value = parseFloat(amount) || 0;
    return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function setLoading(el, loading) {
    if (!el) return;
    if (loading) {
      el.classList.add('is-loading');
      el.dataset.prevDisabled = el.disabled ? '1' : '0';
      el.disabled = true;
    } else {
      el.classList.remove('is-loading');
      if (el.dataset.prevDisabled === '0') el.disabled = false;
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* Sidebar */
  function initSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar || !toggle) return;

    const open = () => {
      sidebar.classList.add('is-open');
      backdrop?.classList.add('is-visible');
    };
    const close = () => {
      sidebar.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
    };

    toggle.addEventListener('click', () => {
      sidebar.classList.contains('is-open') ? close() : open();
    });
    backdrop?.addEventListener('click', close);
  }

  /* Global search shortcut */
  function initGlobalSearch() {
    const input = document.getElementById('global-search');
    if (!input) return;
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = input.value.trim();
        if (q) {
          window.location.href = input.dataset.searchUrl + '?search=' + encodeURIComponent(q);
        }
      }
    });
  }

  /* Notification polling */
  function initNotificationBadge() {
    const badge = document.getElementById('notification-badge');
    const url = badge?.dataset.pollUrl;
    if (!badge || !url) return;

    const refresh = async () => {
      try {
        const data = await fetchJson(url);
        const count = data.count ?? 0;
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('is-visible', count > 0);
      } catch (_) {
        /* silent */
      }
    };

    refresh();
    setInterval(refresh, 60000);
  }

  /* Confirm forms */
  function initConfirmForms() {
    document.querySelectorAll('[data-confirm]').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        const msg = form.getAttribute('data-confirm');
        if (!msg) return;
        e.preventDefault();
        const ok = await confirmDialog(msg, form.getAttribute('data-confirm-title') || 'Confirm');
        if (ok) form.submit();
      });
    });
  }

  /* Live search helper */
  function bindLiveSearch(inputSelector, onSearch) {
    const input = document.querySelector(inputSelector);
    if (!input) return;
    input.addEventListener(
      'input',
      debounce(() => onSearch(input.value.trim()), 350)
    );
  }

  window.App.bindLiveSearch = bindLiveSearch;

  /* Flash to toast */
  document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initGlobalSearch();
    initNotificationBadge();
    initConfirmForms();

    document.querySelectorAll('[data-toast]').forEach((el) => {
      toast(el.textContent.trim(), el.dataset.toastType || 'info');
      el.remove();
    });
  });
})();
