(function () {
  'use strict';

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatMoney(amount) {
    if (window.App && window.App.formatMoney) {
      return window.App.formatMoney(amount);
    }
    const value = parseFloat(amount) || 0;
    return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function scrollToFirstError() {
    const first = document.querySelector('.form-control.is-invalid, .form-select.is-invalid, .form-textarea.is-invalid');
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      first.focus({ preventScroll: true });
    }
  }

  function initDropdowns(root) {
    if (window.InventoryModule && window.InventoryModule.initDropdowns) {
      window.InventoryModule.initDropdowns(root);
    }
  }

  /* ── Index filters ── */
  function initIndexFilters() {
    const form = document.getElementById('pur-filters');
    if (!form) return;

    const toggle = document.getElementById('pur-filters-toggle');
    const clearBtn = document.getElementById('pur-filters-clear');
    const advanced = document.getElementById('pur-filters-advanced');
    const activeEl = document.getElementById('pur-active-filters');
    const drawer = document.getElementById('pur-filters-mobile');
    const backdrop = document.getElementById('pur-filters-backdrop');
    const mobileApply = document.getElementById('pur-filters-mobile-apply');
    const MOBILE_MQ = window.matchMedia('(max-width: 768px)');

    const statusLabels = {
      pending: 'Pending',
      ordered: 'Ordered',
      received: 'Received',
      cancelled: 'Cancelled',
    };

    function renderActiveFilters() {
      if (!activeEl) return;
      const chips = [];
      const search = form.querySelector('[name="search"]')?.value;
      const status = form.querySelector('[name="status"]')?.value;
      if (search) chips.push({ label: 'Search', value: search });
      if (status) chips.push({ label: 'Status', value: statusLabels[status] || status });
      activeEl.innerHTML = chips
        .map(
          (c) =>
            '<span class="inv-filter-chip">' +
            escapeHtml(c.label) +
            ': ' +
            escapeHtml(c.value) +
            '</span>'
        )
        .join('');
    }

    function openDrawer() {
      drawer?.classList.add('is-open');
      backdrop?.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      drawer?.classList.remove('is-open');
      backdrop?.classList.remove('is-visible');
      document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', () => {
      if (MOBILE_MQ.matches) {
        document.querySelectorAll('[data-pur-sync]').forEach(function (el) {
          var name = el.dataset.purSync;
          var source = form.querySelector('[name="' + name + '"]');
          if (!source) return;
          el.value = source.value;
        });
        openDrawer();
      } else {
        advanced?.classList.toggle('is-open');
        const open = advanced?.classList.contains('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    });

    backdrop?.addEventListener('click', closeDrawer);
    mobileApply?.addEventListener('click', () => {
      document.querySelectorAll('[data-pur-sync]').forEach(function (el) {
        var name = el.dataset.purSync;
        var target = form.querySelector('[name="' + name + '"]');
        if (target) target.value = el.value;
      });
      form.submit();
    });

    clearBtn?.addEventListener('click', () => {
      window.location.href = form.action || window.location.pathname;
    });

    form.addEventListener('submit', renderActiveFilters);
    renderActiveFilters();

    const hasStatus = form.querySelector('[name="status"]')?.value;
    if (hasStatus) {
      advanced?.classList.add('is-open');
      toggle?.setAttribute('aria-expanded', 'true');
      toggle?.classList.add('is-active');
    }
  }

  /* ── Purchase form (create / edit) ── */
  function initPurchaseForm(config) {
    const form = document.getElementById(config.formId || 'purchase-form');
    const linesContainer = document.getElementById('pur-lines');
    const addBtn = document.getElementById('add-line');
    const statusSelect = document.getElementById('status');
    const statusHint = document.getElementById('pur-status-hint');
    const itemOptions = config.itemOptions || [];

    const statusHints = {
      pending: 'Purchase has been created but not yet ordered.',
      ordered: 'Purchase has been placed with the supplier.',
    };

    function updateStatusHint() {
      if (statusHint && statusSelect) {
        statusHint.textContent = statusHints[statusSelect.value] || '';
      }
    }

    statusSelect?.addEventListener('change', updateStatusHint);
    updateStatusHint();

    function reindex() {
      if (!linesContainer) return;
      const lines = [...linesContainer.querySelectorAll('.pur-line')];
      lines.forEach((line, i) => {
        line.querySelectorAll('select, input').forEach((el) => {
          if (!el.name) return;
          el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
        });
        const num = line.querySelector('.pur-line__num');
        if (num) num.textContent = 'Item ' + (i + 1);
        const remove = line.querySelector('.remove-line');
        if (remove) remove.disabled = lines.length === 1;
      });
    }

    function calcLineTotal(line) {
      const qty = parseFloat(line.querySelector('[name*="quantity_ordered"]')?.value) || 0;
      const cost = parseFloat(line.querySelector('.line-cost')?.value) || 0;
      const total = qty * cost;
      const formula = line.querySelector('.pur-line__total-formula');
      const amount = line.querySelector('.pur-line__total-amount');
      if (formula) {
        formula.textContent = qty + ' × ' + formatMoney(cost);
      }
      if (amount) {
        amount.textContent = '= ' + formatMoney(total);
      }
      return total;
    }

    function updateFormSummary() {
      if (!linesContainer) return;
      let subtotal = 0;
      let count = 0;
      linesContainer.querySelectorAll('.pur-line').forEach((line) => {
        subtotal += calcLineTotal(line);
        const itemId = line.querySelector('[name*="inventory_item_id"]')?.value;
        if (itemId) count++;
      });
      const subtotalEl = document.getElementById('pur-summary-subtotal');
      const itemsEl = document.getElementById('pur-summary-items');
      const grandEl = document.getElementById('pur-summary-grand');
      if (subtotalEl) subtotalEl.textContent = formatMoney(subtotal);
      if (itemsEl) itemsEl.textContent = String(count);
      if (grandEl) grandEl.textContent = formatMoney(subtotal);
    }

    function bindLine(line) {
      const select = line.querySelector('[name*="inventory_item_id"]');
      const cost = line.querySelector('.line-cost');
      const costHint = line.querySelector('.pur-cost-hint');

      select?.addEventListener('change', () => {
        const opt = itemOptions.find((o) => String(o.id) === select.value);
        if (opt && cost && !cost.value) {
          cost.value = opt.cost;
          if (costHint) costHint.classList.add('is-visible');
        } else if (costHint) {
          costHint.classList.toggle('is-visible', !!select.value && !!cost?.value);
        }
        updateFormSummary();
      });

      cost?.addEventListener('input', () => {
        if (costHint) costHint.classList.toggle('is-visible', !!cost.value);
        updateFormSummary();
      });

      line.querySelector('[name*="quantity_ordered"]')?.addEventListener('input', updateFormSummary);

      line.querySelector('.remove-line')?.addEventListener('click', () => {
        if (linesContainer.querySelectorAll('.pur-line').length <= 1) return;
        line.classList.add('is-removing');
        setTimeout(() => {
          line.remove();
          reindex();
          updateFormSummary();
        }, 180);
      });

      calcLineTotal(line);
    }

    if (linesContainer) {
      linesContainer.querySelectorAll('.pur-line').forEach(bindLine);
      updateFormSummary();
    }

    addBtn?.addEventListener('click', () => {
      const i = linesContainer.querySelectorAll('.pur-line').length;
      const options = itemOptions
        .map(
          (o) =>
            '<option value="' +
            o.id +
            '" data-cost="' +
            o.cost +
            '">' +
            escapeHtml(o.code) +
            ' — ' +
            escapeHtml(o.name) +
            ' (stock: ' +
            escapeHtml(String(o.stock)) +
            ' ' +
            escapeHtml(o.unit) +
            ')</option>'
        )
        .join('');

      const div = document.createElement('div');
      div.className = 'pur-line';
      div.innerHTML =
        '<div class="pur-line__head">' +
        '<span class="pur-line__num">Item ' +
        (i + 1) +
        '</span>' +
        '<button type="button" class="pur-remove-line remove-line" aria-label="Remove item" title="Remove item">' +
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
        '</button></div>' +
        '<div class="pur-line__grid">' +
        '<div class="form-group"><label class="form-label">Item <span class="req" aria-hidden="true">*</span></label>' +
        '<select name="items[' +
        i +
        '][inventory_item_id]" class="form-select pur-item-select" required aria-label="Select inventory item">' +
        '<option value="">— Select item —</option>' +
        options +
        '</select></div>' +
        '<div class="form-group"><label class="form-label">Qty Ordered <span class="req" aria-hidden="true">*</span></label>' +
        '<input type="number" step="0.01" min="0.01" name="items[' +
        i +
        '][quantity_ordered]" class="form-control" required aria-label="Quantity ordered"></div>' +
        '<div class="form-group"><label class="form-label">Unit Cost <span class="req" aria-hidden="true">*</span></label>' +
        '<input type="number" step="0.01" min="0" name="items[' +
        i +
        '][unit_cost]" class="form-control line-cost" required aria-label="Unit cost">' +
        '<span class="pur-cost-hint">Default cost loaded from inventory</span></div>' +
        '<div class="form-group"><label class="form-label">Remarks</label>' +
        '<input type="text" name="items[' +
        i +
        '][remarks]" class="form-control" aria-label="Line remarks"></div>' +
        '</div>' +
        '<div class="pur-line__total mt-1">' +
        '<div class="pur-line__total-formula">0 × ₱0.00</div>' +
        '<div class="pur-line__total-amount">= ₱0.00</div>' +
        '<div class="pur-line__total-preview">Line total preview</div></div>';

      linesContainer.appendChild(div);
      bindLine(div);
      reindex();
      updateFormSummary();
      div.querySelector('.pur-item-select')?.focus();
    });

    if (form) {
      form.addEventListener('submit', function () {
        const btn = form.querySelector('[type="submit"]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;
        btn.classList.add('is-loading');
        const textEl = btn.querySelector('.inv-submit-text');
        if (textEl) textEl.textContent = config.loadingText || 'Saving…';
      });
    }

    if (document.querySelector('.is-invalid')) scrollToFirstError();
  }

  /* ── Receive form ── */
  function initReceiveForm() {
    const form = document.getElementById('pur-receive-form');
    if (!form) return;

    form.querySelectorAll('.pur-receive-line').forEach((line) => {
      const input = line.querySelector('.pur-receive-qty');
      const warn = line.querySelector('.pur-qty-warn');
      const remaining = parseFloat(line.dataset.remaining) || 0;
      const currentStock = parseFloat(line.dataset.currentStock) || 0;
      const unit = line.dataset.unit || 'pcs';
      const previewNew = line.querySelector('.pur-stock-preview__new');

      function update() {
        const qty = parseFloat(input?.value) || 0;
        if (warn) {
          const over = qty > remaining;
          warn.classList.toggle('is-visible', over);
          warn.textContent = over
            ? 'Quantity exceeds remaining (' + remaining + ' ' + unit + ')'
            : '';
        }
        if (input) {
          input.setAttribute('aria-invalid', qty > remaining ? 'true' : 'false');
        }
        if (previewNew) {
          previewNew.textContent =
            (currentStock + qty).toLocaleString('en-PH', {
              minimumFractionDigits: 0,
              maximumFractionDigits: 2,
            }) +
            ' ' +
            unit;
        }
        const qtyDisplay = line.querySelector('.pur-receive-qty-display');
        if (qtyDisplay) {
          qtyDisplay.textContent = qty + ' ' + unit;
        }
      }

      input?.addEventListener('input', update);
      update();
    });

    /* Update receive totals */
    function updateReceiveTotals() {
      let total = 0;
      form.querySelectorAll('.pur-receive-qty').forEach((input) => {
        total += parseFloat(input.value) || 0;
      });
      const el = document.getElementById('pur-receive-total-qty');
      if (el) el.textContent = total.toLocaleString('en-PH', { maximumFractionDigits: 2 });
    }

    form.querySelectorAll('.pur-receive-qty').forEach((input) => {
      input.addEventListener('input', updateReceiveTotals);
    });
    updateReceiveTotals();

    const submitBtn = form.querySelector('[type="submit"]');
    const nativeSubmit = form.submit.bind(form);
    form.submit = function () {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('is-loading');
        const textEl = submitBtn.querySelector('.inv-submit-text');
        if (textEl) textEl.textContent = 'Processing…';
      }
      nativeSubmit();
    };
  }

  window.PurchasesModule = {
    initIndexFilters,
    initPurchaseForm,
    initReceiveForm,
    initDropdowns,
    scrollToFirstError,
  };
})();
