(function () {
  'use strict';

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function statusBadge(status) {
    const map = {
      Available: 'badge--available',
      Borrowed: 'badge--borrowed',
      'Under Maintenance': 'badge--maintenance',
      Archived: 'badge--archived',
      'Out of Stock': 'badge--out',
    };
    const cls = map[status] || 'badge--default';
    return '<span class="badge ' + cls + '">' + escapeHtml(status || '—') + '</span>';
  }

  function isLowStock(row) {
    return (
      row.inventory_type === 'consumable' &&
      parseFloat(row.reorder_level) > 0 &&
      parseFloat(row.quantity) <= parseFloat(row.reorder_level)
    );
  }

  function qtyCell(row) {
    const unit = row.unit || 'pcs';
    let html =
      '<div class="inv-qty"><span class="inv-qty__value">' +
      escapeHtml(String(row.quantity)) +
      ' ' +
      escapeHtml(unit) +
      '</span>';
    if (isLowStock(row)) {
      html +=
        '<span class="inv-qty__warn" role="status"><svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> Low Stock</span>';
    }
    html += '</div>';
    return html;
  }

  function initDropdowns(root) {
    const scope = root || document;
    scope.querySelectorAll('.inv-dropdown').forEach((dropdown) => {
      if (dropdown.dataset.bound === '1') return;
      dropdown.dataset.bound = '1';
      const trigger = dropdown.querySelector('.inv-dropdown__trigger');
      const menu = dropdown.querySelector('.inv-dropdown__menu');
      if (!trigger || !menu) return;

      const close = () => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
      };

      const open = () => {
        document.querySelectorAll('.inv-dropdown__menu:not([hidden])').forEach((m) => {
          if (m !== menu) {
            m.hidden = true;
            m.closest('.inv-dropdown')?.querySelector('.inv-dropdown__trigger')?.setAttribute('aria-expanded', 'false');
          }
        });
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
      };

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.hidden ? open() : close();
      });

      menu.addEventListener('click', (e) => e.stopPropagation());

      document.addEventListener('click', () => close());
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
      });
    });
  }

  function renderSkeletonRows(count) {
    let html = '';
    for (let i = 0; i < count; i++) {
      html +=
        '<tr class="inv-skeleton-row"><td><div class="inv-skeleton inv-skeleton--lg"></div><div class="inv-skeleton inv-skeleton--sm"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--md"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--md"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--sm"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--sm"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--sm"></div></td>' +
        '<td><div class="inv-skeleton inv-skeleton--md"></div></td>' +
        '<td></td></tr>';
    }
    return html;
  }

  function initIndex(config) {
    const form = document.getElementById('inventory-filters');
    if (!form) return;

    const tbody = document.getElementById('inventory-tbody');
    const table = document.getElementById('inventory-table');
    const cards = document.getElementById('inventory-cards');
    const panel = document.getElementById('inventory-table-panel');
    const empty = document.getElementById('inventory-empty');
    const error = document.getElementById('inventory-error');
    const pagination = document.getElementById('inventory-pagination');
    const countEl = document.getElementById('inventory-count');
    const activeFilters = document.getElementById('inv-active-filters');
    const filterToggle = document.getElementById('inv-filters-toggle');
    const filterClear = document.getElementById('inv-filters-clear');
    const advancedDesktop = document.getElementById('inv-filters-advanced');
    const advancedMobile = document.getElementById('inv-filters-mobile');
    const drawerBackdrop = document.getElementById('inv-filters-backdrop');
    const mobileApply = document.getElementById('inv-filters-mobile-apply');

    const baseUrl = config.baseUrl;
    const inventoryBase = config.inventoryBase;
    const canModify = config.canModify;
    const filterLabels = config.filterLabels || {};

    let currentPage = 1;
    let abortController = null;
    let hasLoaded = false;

    const MOBILE_MQ = window.matchMedia('(max-width: 768px)');

    function buildQuery(page) {
      const fd = new FormData(form);
      const params = new URLSearchParams();
      fd.forEach((v, k) => {
        if (v) params.set(k, v);
      });
      params.set('page', String(page || 1));
      return params.toString();
    }

    function getActiveFilterEntries() {
      const fd = new FormData(form);
      const entries = [];
      fd.forEach((v, k) => {
        if (!v || k === 'search') return;
        const label = filterLabels[k] || k;
        let display = v;
        if (k === 'low_stock') display = 'Low stock only';
        else if (k === 'inventory_type') display = v === 'consumable' ? 'Consumable' : 'Asset';
        else {
          const sel = form.querySelector('[name="' + k + '"]');
          if (sel && sel.tagName === 'SELECT') {
            const opt = sel.querySelector('option[value="' + CSS.escape(v) + '"]');
            if (opt) display = opt.textContent.trim();
          }
        }
        entries.push({ key: k, label: label, value: display });
      });
      const search = fd.get('search');
      if (search) entries.unshift({ key: 'search', label: 'Search', value: search });
      return entries;
    }

    function updateActiveFilters() {
      if (!activeFilters) return;
      const entries = getActiveFilterEntries();
      activeFilters.innerHTML = entries
        .map(
          (e) =>
            '<span class="inv-filter-chip">' +
            escapeHtml(e.label) +
            ': ' +
            escapeHtml(e.value) +
            '</span>'
        )
        .join('');
      const hasFilters = entries.length > 0;
      filterToggle?.classList.toggle('is-active', hasFilters);
    }

    function renderActions(showUrl, rowId) {
      let html =
        '<a href="' +
        showUrl +
        '" class="btn btn--ghost btn--sm">View</a>';
      if (canModify) {
        html +=
          '<div class="inv-dropdown">' +
          '<button type="button" class="inv-dropdown__trigger" aria-label="More actions" aria-haspopup="true" aria-expanded="false">' +
          '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' +
          '</button>' +
          '<div class="inv-dropdown__menu" hidden role="menu">' +
          '<a href="' +
          showUrl +
          '/edit" class="inv-dropdown__item" role="menuitem">Edit item</a>' +
          '</div></div>';
      }
      return html;
    }

    function renderRow(row) {
      const showUrl = inventoryBase + '/' + row.id;
      return (
        '<tr>' +
        '<td><a href="' +
        showUrl +
        '" class="inv-item-link"><span class="inv-item-link__name">' +
        escapeHtml(row.name) +
        '</span><span class="inv-item-link__code">' +
        escapeHtml(row.item_code) +
        '</span></a></td>' +
        '<td>' +
        escapeHtml(row.category ? row.category.name : '—') +
        '</td>' +
        '<td>' +
        escapeHtml(row.location ? row.location.name : '—') +
        '</td>' +
        '<td>' +
        qtyCell(row) +
        '</td>' +
        '<td>' +
        statusBadge(row.status) +
        '</td>' +
        '<td><span class="inv-condition">' +
        escapeHtml(row.condition || '—') +
        '</span></td>' +
        '<td><span class="inv-value">' +
        App.formatMoney(row.total_value) +
        '</span></td>' +
        '<td class="inv-col-actions"><div class="actions">' +
        renderActions(showUrl, row.id) +
        '</div></td>' +
        '</tr>'
      );
    }

    function renderCard(row) {
      const showUrl = inventoryBase + '/' + row.id;
      let actions =
        '<a href="' +
        showUrl +
        '" class="btn btn--primary btn--sm">View</a>';
      if (canModify) {
        actions +=
          ' <a href="' +
          showUrl +
          '/edit" class="btn btn--secondary btn--sm">Edit</a>';
      }
      return (
        '<article class="inv-card-item">' +
        '<div class="inv-card-item__head">' +
        '<div><a href="' +
        showUrl +
        '" class="inv-item-link"><span class="inv-item-link__name">' +
        escapeHtml(row.name) +
        '</span><span class="inv-item-link__code">' +
        escapeHtml(row.item_code) +
        '</span></a></div>' +
        statusBadge(row.status) +
        '</div>' +
        '<dl class="inv-card-item__meta">' +
        '<div><dt>Category</dt><dd>' +
        escapeHtml(row.category ? row.category.name : '—') +
        '</dd></div>' +
        '<div><dt>Location</dt><dd>' +
        escapeHtml(row.location ? row.location.name : '—') +
        '</dd></div>' +
        '<div><dt>Quantity</dt><dd>' +
        qtyCell(row) +
        '</dd></div>' +
        '<div><dt>Condition</dt><dd>' +
        escapeHtml(row.condition || '—') +
        '</dd></div>' +
        '<div><dt>Value</dt><dd>' +
        App.formatMoney(row.total_value) +
        '</dd></div>' +
        '</dl>' +
        '<div class="inv-card-item__actions">' +
        actions +
        '</div></article>'
      );
    }

    function renderPagination(meta) {
      if (!pagination) return;
      if (!meta || meta.last_page <= 1) {
        pagination.innerHTML = '';
        pagination.hidden = true;
        return;
      }
      pagination.hidden = false;
      const prevDisabled = meta.current_page <= 1;
      const nextDisabled = meta.current_page >= meta.last_page;
      pagination.innerHTML =
        '<div class="inv-pagination__info">Page ' +
        meta.current_page +
        ' of ' +
        meta.last_page +
        '</div>' +
        '<div class="inv-pagination__nav">' +
        '<button type="button" class="pagination__btn" data-page="' +
        (meta.current_page - 1) +
        '" ' +
        (prevDisabled ? 'disabled aria-disabled="true"' : '') +
        '>Previous</button>' +
        '<button type="button" class="pagination__btn" data-page="' +
        (meta.current_page + 1) +
        '" ' +
        (nextDisabled ? 'disabled aria-disabled="true"' : '') +
        '>Next</button></div>';
      pagination.querySelectorAll('[data-page]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const p = parseInt(btn.dataset.page, 10);
          if (p >= 1) load(p);
        });
      });
    }

    function showState(state) {
      table.style.display = state === 'data' ? 'table' : 'none';
      if (cards) cards.style.display = state === 'data' ? 'flex' : 'none';
      empty.hidden = state !== 'empty';
      error.hidden = state !== 'error';
      if (panel) panel.classList.toggle('is-loading', state === 'loading');
    }

    async function load(page) {
      currentPage = page || 1;
      if (abortController) abortController.abort();
      abortController = new AbortController();

      showState('loading');
      if (!hasLoaded) {
        tbody.innerHTML = renderSkeletonRows(5);
        table.style.display = 'table';
      }

      try {
        const url = baseUrl + '?' + buildQuery(currentPage);
        const response = await fetch(url, {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          signal: abortController.signal,
        });
        let data = null;
        const ct = response.headers.get('content-type') || '';
        if (ct.includes('application/json')) data = await response.json();
        if (!response.ok) {
          throw new Error((data && (data.message || data.error)) || 'Request failed.');
        }

        hasLoaded = true;
        const items = data.data || [];
        updateActiveFilters();

        if (countEl && data.total !== undefined) {
          countEl.innerHTML = '<strong>' + data.total.toLocaleString() + '</strong> items';
        }

        if (!items.length) {
          tbody.innerHTML = '';
          if (cards) cards.innerHTML = '';
          showState('empty');
          renderPagination(data);
          return;
        }

        tbody.innerHTML = items.map(renderRow).join('');
        if (cards) cards.innerHTML = items.map(renderCard).join('');
        showState('data');
        renderPagination(data);
        initDropdowns(panel || document);
      } catch (e) {
        if (e.name === 'AbortError') return;
        tbody.innerHTML = '';
        if (cards) cards.innerHTML = '';
        showState('error');
        const errMsg = document.getElementById('inventory-error-msg');
        if (errMsg) errMsg.textContent = e.message;
        App.toast(e.message, 'error');
      }
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      load(1);
      closeMobileDrawer();
    });

    ['category_id', 'location_id', 'inventory_type', 'status', 'condition'].forEach((id) => {
      const el = document.getElementById(id);
      el?.addEventListener('change', () => {
        if (!MOBILE_MQ.matches) load(1);
      });
    });

    form.querySelector('[name="low_stock"]')?.addEventListener('change', () => {
      if (!MOBILE_MQ.matches) load(1);
    });

    document.getElementById('search')?.addEventListener(
      'input',
      App.debounce(() => load(1), 400)
    );

    filterClear?.addEventListener('click', () => {
      form.reset();
      load(1);
    });

    function openMobileDrawer() {
      advancedMobile?.classList.add('is-open');
      drawerBackdrop?.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
      advancedMobile?.classList.remove('is-open');
      drawerBackdrop?.classList.remove('is-visible');
      document.body.style.overflow = '';
    }

    filterToggle?.addEventListener('click', () => {
      if (MOBILE_MQ.matches) {
        // Sync form values to mobile drawer
        document.querySelectorAll('[data-sync]').forEach(function (el) {
          var name = el.dataset.sync;
          var source = document.querySelector('[name="' + name + '"]');
          if (!source) return;
          if (el.type === 'checkbox') {
            el.checked = source.checked;
          } else {
            el.value = source.value;
          }
        });
        openMobileDrawer();
      } else {
        advancedDesktop?.classList.toggle('is-open');
        const open = advancedDesktop?.classList.contains('is-open');
        filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    });

    drawerBackdrop?.addEventListener('click', closeMobileDrawer);
    mobileApply?.addEventListener('click', () => {
      load(1);
      closeMobileDrawer();
    });

    document.getElementById('inventory-retry')?.addEventListener('click', () => load(currentPage));

    // Open advanced filters if any are pre-set
    const hasPresetFilters = getActiveFilterEntries().length > 0;
    if (hasPresetFilters) {
      advancedDesktop?.classList.add('is-open');
      filterToggle?.setAttribute('aria-expanded', 'true');
      filterToggle?.classList.add('is-active');
    }

    load(1);
  }

  function initTypeSelector() {
    const select = document.getElementById('inventory_type');
    const hint = document.getElementById('inv-type-hint');
    const options = document.querySelectorAll('.inv-type-option');
    if (!select || !options.length) return;

    const hints = {
      consumable: 'Stock can be issued or consumed.',
      asset: 'Assets can be borrowed, returned, and transferred.',
    };

    function syncFromSelect() {
      const val = select.value;
      options.forEach((opt) => {
        opt.classList.toggle('is-selected', opt.dataset.value === val);
        opt.setAttribute('aria-pressed', opt.dataset.value === val ? 'true' : 'false');
      });
      if (hint) hint.textContent = hints[val] || '';
    }

    options.forEach((opt) => {
      opt.addEventListener('click', () => {
        select.value = opt.dataset.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncFromSelect();
      });
      opt.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          opt.click();
        }
      });
    });

    select.addEventListener('change', syncFromSelect);
    syncFromSelect();
  }

  function initFormTotalValue(isEdit) {
    const qty = document.getElementById('quantity');
    const cost = document.getElementById('unit_cost');
    const display = document.getElementById('total_value_display');
    if (!display) return;

    function recalc() {
      let q = 0;
      if (isEdit) {
        const source = qty?.value ?? qty?.textContent ?? '';
        const match = String(source).match(/^[\d.]+/);
        q = match ? parseFloat(match[0]) : 0;
      } else {
        q = parseFloat(qty?.value) || 0;
      }
      const c = parseFloat(cost?.value) || 0;
      if (window.App) {
        const formatted = App.formatMoney(q * c);
        display.textContent = formatted;
        const hiddenInput = document.getElementById('total_value_display_input');
        if (hiddenInput) hiddenInput.value = formatted;
      }
    }

    if (!isEdit) qty?.addEventListener('input', recalc);
    cost?.addEventListener('input', recalc);
    recalc();
  }

  function scrollToFirstError() {
    const first = document.querySelector('.form-control.is-invalid, .form-select.is-invalid, .form-textarea.is-invalid');
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      first.focus({ preventScroll: true });
    }
  }

  function initFormSubmit(formId, loadingText) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function () {
      const btn = form.querySelector('[type="submit"]');
      if (!btn || btn.disabled) return;
      btn.disabled = true;
      btn.classList.add('is-loading');
      const textEl = btn.querySelector('.inv-submit-text');
      if (textEl) textEl.textContent = loadingText;
    });
  }

  function initForm(config) {
    initTypeSelector();
    initFormTotalValue(config.isEdit);
    initFormSubmit(config.formId, config.loadingText);
    if (document.querySelector('.is-invalid')) scrollToFirstError();
  }

  function initShowTabs() {
    const tabs = document.querySelectorAll('.inv-tabs__btn');
    const panels = document.querySelectorAll('.inv-tab-panel');
    if (!tabs.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach((t) => {
          t.classList.toggle('is-active', t === tab);
          t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
        });
        panels.forEach((p) => {
          p.classList.toggle('is-active', p.id === 'inv-tab-' + target);
        });
      });
    });
  }

  window.InventoryModule = {
    initIndex,
    initForm,
    initDropdowns,
    initShowTabs,
    initTypeSelector,
    initFormTotalValue,
    scrollToFirstError,
  };
})();
