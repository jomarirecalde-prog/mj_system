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
    initPageContent,
    registerPageCleanup: null,
    navigateTo: null,
  };

  const pageCleanupHandlers = new Set();

  window.App.registerPageCleanup = function (fn) {
    if (typeof fn === 'function') pageCleanupHandlers.add(fn);
    window.AppNavigation?.registerPageCleanup?.(fn);
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

  /* Sidebar — mobile drawer + desktop collapse */
  function initSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const collapseBtn = document.getElementById('sidebar-collapse');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar || !toggle) return;

    const MOBILE_MQ = window.matchMedia('(max-width: 960px)');
    const COLLAPSE_KEY = 'app-sidebar-collapsed';

    const isMobile = () => MOBILE_MQ.matches;

    const setMobileOpen = (open) => {
      sidebar.classList.toggle('is-open', open);
      backdrop?.classList.toggle('is-visible', open);
      document.body.classList.toggle('sidebar-mobile-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
      backdrop?.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    const openMobile = () => setMobileOpen(true);
    const closeMobile = () => setMobileOpen(false);

    const applyCollapse = (collapsed) => {
      sidebar.classList.toggle('is-collapsed', collapsed);
      document.body.classList.toggle('sidebar-is-collapsed', collapsed);
      collapseBtn?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      collapseBtn?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    };

    let collapsed = false;
    try {
      collapsed = localStorage.getItem(COLLAPSE_KEY) === '1';
    } catch (_) {
      /* ignore */
    }
    if (!isMobile()) {
      applyCollapse(collapsed);
    }

    collapseBtn?.addEventListener('click', () => {
      if (isMobile()) return;
      collapsed = !sidebar.classList.contains('is-collapsed');
      applyCollapse(collapsed);
      try {
        localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
      } catch (_) {
        /* ignore */
      }
    });

    toggle.addEventListener('click', () => {
      sidebar.classList.contains('is-open') ? closeMobile() : openMobile();
    });

    backdrop?.addEventListener('click', closeMobile);

    sidebar.querySelectorAll('.sidebar__link').forEach((link) => {
      link.addEventListener('click', () => {
        if (isMobile()) closeMobile();
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('is-open') && isMobile()) {
        closeMobile();
      }
    });

    MOBILE_MQ.addEventListener('change', () => {
      closeMobile();
      if (isMobile()) {
        applyCollapse(false);
      } else {
        try {
          collapsed = localStorage.getItem(COLLAPSE_KEY) === '1';
        } catch (_) {
          collapsed = false;
        }
        applyCollapse(collapsed);
      }
    });
  }

  /* Collapsible navigation groups */
  function initNavGroups() {
    const STORAGE_KEY = 'app-nav-groups';
    let saved = {};

    try {
      saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch (_) {
      saved = {};
    }

    const setExpanded = (group, expanded) => {
      group.classList.toggle('is-expanded', expanded);
      const trigger = group.querySelector(':scope > [data-nav-trigger]');
      trigger?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    document.querySelectorAll('[data-nav-group]').forEach((group) => {
      const id = group.dataset.navGroup;
      const isRouteActive = group.classList.contains('is-route-active');
      const expanded = Object.prototype.hasOwnProperty.call(saved, id) ? saved[id] : isRouteActive;
      setExpanded(group, expanded);

      const trigger = group.querySelector(':scope > [data-nav-trigger]');
      trigger?.addEventListener('click', () => {
        const nowExpanded = !group.classList.contains('is-expanded');
        setExpanded(group, nowExpanded);
        saved[id] = nowExpanded;
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
        } catch (_) {
          /* ignore */
        }
      });
    });
  }

  /* Global search shortcut */
  function initGlobalSearch() {
    const input = document.getElementById('global-search');
    const searchWrap = document.getElementById('topbar-search');
    const searchToggle = document.getElementById('search-toggle');
    if (!input) return;

    const isTypingTarget = (el) => {
      if (!el) return false;
      const tag = el.tagName;
      return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
    };

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = input.value.trim();
        if (q) {
          window.location.href = input.dataset.searchUrl + '?search=' + encodeURIComponent(q);
        }
      }
      if (e.key === 'Escape') {
        input.blur();
        searchWrap?.classList.remove('is-focused', 'is-mobile-open');
        searchToggle?.setAttribute('aria-expanded', 'false');
      }
    });

    input.addEventListener('focus', () => searchWrap?.classList.add('is-focused'));
    input.addEventListener('blur', () => searchWrap?.classList.remove('is-focused'));

    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        if (isTypingTarget(document.activeElement) && document.activeElement !== input) return;
        e.preventDefault();
        if (window.matchMedia('(max-width: 960px)').matches) {
          searchWrap?.classList.add('is-mobile-open');
          searchToggle?.setAttribute('aria-expanded', 'true');
        }
        input.focus();
        input.select();
      }
    });

    searchToggle?.addEventListener('click', () => {
      const open = !searchWrap?.classList.contains('is-mobile-open');
      searchWrap?.classList.toggle('is-mobile-open', open);
      searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        input.focus();
      } else {
        input.blur();
      }
    });
  }

  /* Account dropdown */
  function initAccountMenu() {
    const menu = document.getElementById('account-menu');
    const trigger = document.getElementById('account-menu-trigger');
    const dropdown = document.getElementById('account-menu-dropdown');
    if (!menu || !trigger || !dropdown) return;

    const open = () => {
      dropdown.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
    };

    const close = () => {
      dropdown.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    };

    const isOpen = () => trigger.getAttribute('aria-expanded') === 'true';

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      isOpen() ? close() : open();
    });

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) {
        close();
        trigger.focus();
      }
    });
  }

  /* Notification polling */
  function initNotificationBadge() {
    const badge = document.getElementById('notification-badge');
    const btn = document.getElementById('notification-btn');
    const url = badge?.dataset.pollUrl;
    if (!badge || !url) return;

    let prevCount = 0;

    const refresh = async () => {
      try {
        const data = await fetchJson(url);
        const count = data.count ?? 0;
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('is-visible', count > 0);

        if (count > prevCount && prevCount >= 0) {
          btn?.classList.add('has-pulse');
          setTimeout(() => btn?.classList.remove('has-pulse'), 1400);
        }
        prevCount = count;
      } catch (_) {
        /* silent */
      }
    };

    refresh();
    setInterval(refresh, 60000);
  }

  /* Confirm forms */
  function initConfirmForms(root) {
    const scope = root || document;
    scope.querySelectorAll('[data-confirm]').forEach((form) => {
      if (form.dataset.confirmBound === '1') return;
      form.dataset.confirmBound = '1';
      form.addEventListener('submit', async (e) => {
        const msg = form.getAttribute('data-confirm');
        if (!msg) return;
        e.preventDefault();
        const ok = await confirmDialog(msg, form.getAttribute('data-confirm-title') || 'Confirm');
        if (ok) form.submit();
      });
    });
  }

  function initFlashToasts(root) {
    const scope = root || document;
    scope.querySelectorAll('[data-toast]').forEach((el) => {
      toast(el.textContent.trim(), el.dataset.toastType || 'info');
      el.remove();
    });
  }

  function initPageContent(root) {
    initConfirmForms(root);
    initFlashToasts(root);
  }

  /* Logout confirmation — intercept all .logout-form submissions */
  function initLogoutConfirmation() {
    const modal = document.getElementById('logout-confirmation-modal');
    const dialog = modal?.querySelector('.logout-modal__dialog');
    const cancelBtn = document.getElementById('logout-modal-cancel');
    const confirmBtn = document.getElementById('logout-modal-confirm');
    const confirmText = confirmBtn?.querySelector('.logout-modal__confirm-text');
    const confirmSpinner = confirmBtn?.querySelector('.logout-modal__spinner');
    if (!modal || !cancelBtn || !confirmBtn || !confirmText) return;

    let pendingForm = null;
    let triggerButton = null;
    let isSubmitting = false;

    const focusables = () => [cancelBtn, confirmBtn];

    const closeAccountMenu = () => {
      const dropdown = document.getElementById('account-menu-dropdown');
      const trigger = document.getElementById('account-menu-trigger');
      if (dropdown && !dropdown.hidden) {
        dropdown.hidden = true;
        trigger?.setAttribute('aria-expanded', 'false');
      }
    };

    const open = (form, button) => {
      pendingForm = form;
      triggerButton = button;
      isSubmitting = false;
      cancelBtn.disabled = false;
      confirmBtn.disabled = false;
      confirmBtn.classList.remove('is-loading');
      confirmText.textContent = 'Sign Out';
      confirmSpinner?.setAttribute('hidden', '');
      closeAccountMenu();
      modal.removeAttribute('hidden');
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('logout-modal-open');
      cancelBtn.focus();
    };

    const close = () => {
      if (isSubmitting) return;
      modal.classList.remove('is-open');
      modal.setAttribute('hidden', '');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('logout-modal-open');
      pendingForm = null;
      if (triggerButton) {
        triggerButton.focus();
        triggerButton = null;
      }
    };

    document.querySelectorAll('form.logout-form').forEach((form) => {
      form.addEventListener('submit', (e) => {
        if (form.dataset.logoutConfirmed === '1') return;
        e.preventDefault();
        if (isSubmitting) return;
        const button = e.submitter || form.querySelector('[type="submit"]');
        open(form, button);
      });
    });

    cancelBtn.addEventListener('click', close);

    confirmBtn.addEventListener('click', () => {
      if (!pendingForm || isSubmitting) return;
      isSubmitting = true;
      cancelBtn.disabled = true;
      confirmBtn.disabled = true;
      confirmBtn.classList.add('is-loading');
      confirmText.textContent = 'Signing out...';
      confirmSpinner?.removeAttribute('hidden');
      pendingForm.dataset.logoutConfirmed = '1';
      window.PWA?.clearSensitiveClientState?.();
      pendingForm.submit();
    });

    modal.addEventListener('click', (e) => {
      if (e.target === modal && !isSubmitting) close();
    });

    modal.addEventListener('keydown', (e) => {
      if (!modal.classList.contains('is-open')) return;

      if (e.key === 'Escape' && !isSubmitting) {
        e.preventDefault();
        close();
        return;
      }

      if (e.key !== 'Tab') return;

      const items = focusables();
      const index = items.indexOf(document.activeElement);
      if (index === -1) return;

      if (e.shiftKey) {
        if (index === 0) {
          e.preventDefault();
          items[items.length - 1].focus();
        }
      } else if (index === items.length - 1) {
        e.preventDefault();
        items[0].focus();
      }
    });

    dialog?.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('is-open') && !isSubmitting) {
        e.preventDefault();
        close();
      }
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
    initNavGroups();
    initGlobalSearch();
    initAccountMenu();
    initNotificationBadge();
    initConfirmForms();
    initLogoutConfirmation();
    initFlashToasts();

    window.App.initPageContent = initPageContent;
  });
})();
