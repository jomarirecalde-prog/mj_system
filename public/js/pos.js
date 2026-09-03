(function () {
  'use strict';

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));
  }

  function initTerminal(config) {
    const searchInput = document.getElementById('pos-search');
    const resultsEl = document.getElementById('pos-results');
    const cartItemsEl = document.getElementById('pos-cart-items');
    const cartEmptyEl = document.getElementById('pos-cart-empty');
    const hiddenItems = document.getElementById('pos-hidden-items');
    const checkoutBtn = document.getElementById('pos-checkout-btn');
    const checkoutHint = document.getElementById('pos-checkout-hint');
    const discountEl = document.getElementById('discount');
    const taxEl = document.getElementById('tax');
    const tenderedEl = document.getElementById('amount_tendered');
    const changeRow = document.getElementById('pos-change-row');
    const changeEl = document.getElementById('pos-change');
    const paymentAlert = document.getElementById('pos-payment-alert');
    const paymentPanel = document.getElementById('pos-payment');
    const scannerOverlay = document.getElementById('pos-scanner-overlay');
    const scanToggle = document.getElementById('pos-scan-toggle');
    const scanClose = document.getElementById('pos-scan-close');
    const scanStatus = document.getElementById('pos-scan-status');
    const scannerStatusEl = document.getElementById('pos-scanner-camera-status');
    const manualQr = document.getElementById('pos-manual-qr');
    const manualScanBtn = document.getElementById('pos-manual-scan-btn');
    const statusIndicator = document.getElementById('pos-status-indicator');
    const statusText = document.getElementById('pos-status-text');
    const mobileBar = document.getElementById('pos-mobile-bar');
    const mobileTotal = document.getElementById('pos-mobile-total');
    const mobileReview = document.getElementById('pos-mobile-review');
    const cartPanel = document.getElementById('pos-cart-panel');

    const searchUrl = config.searchUrl;
    const scanUrl = config.scanUrl;
    const moneyFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

    /** @type {Map<number, object>} */
    const cart = new Map();
    let debounceTimer = null;
    let scanner = null;
    let scanning = false;
    let scanBusy = false;
    let processing = false;
    let lastScanValue = '';
    let lastScanAt = 0;

    function money(n) {
      if (window.App?.formatMoney) return window.App.formatMoney(n);
      return moneyFmt.format(Number(n) || 0);
    }

    function toast(message, type) {
      window.App?.toast?.(message, type || 'info');
    }

    function setPosStatus(state, text) {
      if (!statusIndicator || !statusText) return;
      statusIndicator.className = 'pos-status-indicator is-' + state;
      statusText.textContent = text;
    }

    function setScanStatus(text, cameraState) {
      if (scanStatus) scanStatus.textContent = text;
      if (scannerStatusEl) {
        scannerStatusEl.className = 'pos-scanner-status' + (cameraState ? ' is-' + cameraState : '');
        const label = scannerStatusEl.querySelector('.pos-scanner-status__label');
        if (label) {
          label.textContent =
            cameraState === 'ready' ? 'Camera Ready' :
            cameraState === 'warn' ? 'Camera Unavailable' :
            text;
        }
      }
    }

    function renderResults(items) {
      window.__posSearchItems = {};
      if (!items.length) {
        resultsEl.innerHTML = '<p class="text-muted">No consumable items found.</p>';
        return;
      }

      resultsEl.innerHTML = items.map((item) => {
        window.__posSearchItems[item.id] = item;
        const disabled = item.is_out_of_stock || item.quantity <= 0;
        let stockHtml = 'Available: <strong>' + escapeHtml(String(item.quantity)) + ' ' + escapeHtml(item.unit) + '</strong>';
        if (item.is_out_of_stock || item.quantity <= 0) {
          stockHtml = '<span class="pos-stock-out">✕ Out of Stock</span>';
        } else if (item.is_low_stock) {
          stockHtml = '<span class="pos-stock-warn">⚠ Low Stock</span> ' + escapeHtml(String(item.quantity)) + ' ' + escapeHtml(item.unit);
        }
        return (
          '<button type="button" class="pos-result' + (disabled ? ' is-disabled' : '') + '" data-id="' + item.id + '"' + (disabled ? ' disabled' : '') + '>' +
          '<div class="pos-result__top">' +
          '<div><div class="pos-result__name">' + escapeHtml(item.name) + '</div>' +
          '<div class="pos-result__meta">' + escapeHtml(item.part_number || item.item_code) + '</div></div>' +
          '<span class="pos-result__price">' + money(item.selling_price) + ' / ' + escapeHtml(item.unit) + '</span></div>' +
          '<div class="pos-result__stock">' + stockHtml +
          (disabled ? '' : '<span class="pos-result__add">+ Add</span>') +
          '</div></button>'
        );
      }).join('');

      resultsEl.querySelectorAll('.pos-result').forEach((btn) => {
        btn.addEventListener('click', () => {
          const item = window.__posSearchItems[btn.getAttribute('data-id')];
          if (item) addToCart(item);
        });
      });
    }

    function addToCart(item, fromScan) {
      const available = Number(item.quantity);
      if (available <= 0 || item.is_out_of_stock) {
        toast('Insufficient Inventory. Only 0 units are currently available.', 'error');
        return false;
      }

      const existing = cart.get(item.id);
      if (existing) {
        if (existing.qty + 1 > available) {
          toast('Insufficient Inventory. Only ' + available + ' units are currently available.', 'error');
          return false;
        }
        existing.qty += 1;
        existing.available = available;
      } else {
        cart.set(item.id, {
          id: item.id,
          name: item.name,
          part_number: item.part_number,
          item_code: item.item_code,
          unit: item.unit,
          available: available,
          qty: 1,
          unit_price: Number(item.selling_price) || 0,
        });
      }
      renderCart();
      if (fromScan) toast(item.name + ' added to cart', 'success');
      searchInput?.focus();
      return true;
    }

    function updateLineQty(id, qty) {
      const line = cart.get(id);
      if (!line) return;
      if (qty > line.available) {
        qty = line.available;
        toast('Insufficient Inventory. Only ' + line.available + ' units are currently available.', 'error');
      }
      if (qty <= 0) {
        cart.delete(id);
      } else {
        line.qty = qty;
      }
      renderCart();
    }

    function renderCart() {
      if (!cartItemsEl) return;

      cartItemsEl.innerHTML = '';

      if (cart.size === 0) {
        if (cartEmptyEl) cartEmptyEl.hidden = false;
        if (mobileBar) mobileBar.classList.remove('is-visible');
        syncHidden();
        updateTotals();
        return;
      }

      if (cartEmptyEl) cartEmptyEl.hidden = true;
      if (mobileBar) mobileBar.classList.add('is-visible');

      cart.forEach((line) => {
        const el = document.createElement('div');
        el.className = 'pos-cart-item';
        el.innerHTML =
          '<div class="pos-cart-item__head">' +
          '<div><div class="pos-cart-item__name">' + escapeHtml(line.name) + '</div>' +
          '<div class="pos-cart-item__code">' + escapeHtml(line.part_number || line.item_code) + '</div></div>' +
          '<button type="button" class="pos-cart-item__remove cart-remove" data-id="' + line.id + '" aria-label="Remove ' + escapeHtml(line.name) + '">' +
          '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>' +
          '<dl class="pos-cart-item__grid">' +
          '<div><dt>Qty</dt><dd><div class="pos-qty-control">' +
          '<button type="button" class="pos-qty-btn cart-qty-dec" data-id="' + line.id + '" aria-label="Decrease quantity">−</button>' +
          '<input type="number" step="0.01" min="0.01" max="' + line.available + '" class="form-control cart-qty" data-id="' + line.id + '" value="' + line.qty + '" aria-label="Quantity">' +
          '<button type="button" class="pos-qty-btn cart-qty-inc" data-id="' + line.id + '" aria-label="Increase quantity">+</button>' +
          '</div></dd></div>' +
          '<div><dt>Unit Price</dt><dd><input type="number" step="0.01" min="0" class="form-control cart-price" data-id="' + line.id + '" value="' + line.unit_price + '" aria-label="Unit price"></dd></div>' +
          '<div><dt>Line Total</dt><dd class="pos-cart-item__line-total cart-line-total">' + money(line.qty * line.unit_price) + '</dd></div>' +
          '</dl>' +
          '<div class="pos-cart-item__avail">Available: ' + line.available + ' ' + escapeHtml(line.unit) + '</div>';
        cartItemsEl.appendChild(el);
      });

      cartItemsEl.querySelectorAll('.cart-qty').forEach((el) => {
        el.addEventListener('change', () => updateLineQty(Number(el.dataset.id), Number(el.value) || 0));
      });

      cartItemsEl.querySelectorAll('.cart-qty-dec').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = Number(btn.dataset.id);
          const line = cart.get(id);
          if (line) updateLineQty(id, line.qty - 1);
        });
      });

      cartItemsEl.querySelectorAll('.cart-qty-inc').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = Number(btn.dataset.id);
          const line = cart.get(id);
          if (line) updateLineQty(id, line.qty + 1);
        });
      });

      cartItemsEl.querySelectorAll('.cart-price').forEach((el) => {
        el.addEventListener('change', () => {
          const id = Number(el.dataset.id);
          const line = cart.get(id);
          if (!line) return;
          line.unit_price = Math.max(0, Number(el.value) || 0);
          renderCart();
        });
      });

      cartItemsEl.querySelectorAll('.cart-remove').forEach((el) => {
        el.addEventListener('click', () => {
          const row = el.closest('.pos-cart-item');
          if (row) row.classList.add('is-removing');
          setTimeout(() => {
            cart.delete(Number(el.dataset.id));
            renderCart();
          }, 150);
        });
      });

      syncHidden();
      updateTotals();
    }

    function syncHidden() {
      hiddenItems.innerHTML = '';
      let i = 0;
      cart.forEach((line) => {
        hiddenItems.insertAdjacentHTML('beforeend',
          '<input type="hidden" name="items[' + i + '][inventory_item_id]" value="' + line.id + '">' +
          '<input type="hidden" name="items[' + i + '][quantity]" value="' + line.qty + '">' +
          '<input type="hidden" name="items[' + i + '][unit_price]" value="' + line.unit_price + '">'
        );
        i++;
      });
    }

    function roundMoney(n) {
      return Math.round((Number(n) || 0) * 100) / 100;
    }

    function getCartTotal() {
      let subtotal = 0;
      cart.forEach((line) => { subtotal += line.qty * line.unit_price; });
      const discount = Math.max(0, Number(discountEl.value) || 0);
      const tax = Math.max(0, Number(taxEl.value) || 0);
      return {
        subtotal: roundMoney(subtotal),
        discount: roundMoney(discount),
        tax: roundMoney(tax),
        total: roundMoney(Math.max(0, subtotal - discount + tax)),
      };
    }

    function updateTotals() {
      const { subtotal, total } = getCartTotal();
      const rawTendered = String(tenderedEl?.value ?? '').trim();
      const hasTendered = rawTendered !== '';
      const tendered = hasTendered ? roundMoney(tenderedEl.value) : 0;
      const change = roundMoney(tendered - total);
      const cartReady = cart.size > 0 && total >= 0;

      document.getElementById('pos-subtotal').textContent = money(subtotal);
      document.getElementById('pos-total').textContent = money(total);
      if (mobileTotal) mobileTotal.textContent = money(total);
      changeEl.textContent = money(Math.max(0, change));

      paymentPanel?.classList.remove('is-ok', 'is-short', 'is-empty');
      changeRow?.classList.remove('is-ok', 'is-short');

      if (processing) {
        checkoutBtn.disabled = true;
        checkoutHint.textContent = 'Processing sale…';
        return;
      }

      if (!cartReady) {
        paymentAlert.hidden = true;
        paymentAlert.textContent = '';
        paymentPanel?.classList.add('is-empty');
        checkoutBtn.disabled = true;
        checkoutBtn.querySelector('.pos-checkout-text').textContent = 'Complete Sale';
        checkoutHint.textContent = 'Add items to continue.';
        if (!scanning && !scanBusy) setPosStatus('ready', 'POS Ready');
        return;
      }

      if (!hasTendered) {
        paymentAlert.hidden = false;
        paymentAlert.textContent = 'Enter cash received to calculate change.';
        paymentAlert.className = 'pos-payment__alert is-info';
        paymentPanel?.classList.add('is-empty');
        checkoutBtn.disabled = true;
        checkoutBtn.querySelector('.pos-checkout-text').textContent = 'Complete Sale';
        checkoutHint.textContent = 'Enter cash received.';
        return;
      }

      if (tendered < total) {
        const shortfall = roundMoney(total - tendered);
        paymentAlert.hidden = false;
        paymentAlert.textContent = 'Insufficient cash. Still need ' + money(shortfall) + '.';
        paymentAlert.className = 'pos-payment__alert is-error';
        paymentPanel?.classList.add('is-short');
        changeRow?.classList.add('is-short');
        changeEl.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        checkoutBtn.querySelector('.pos-checkout-text').textContent = 'Insufficient Cash';
        checkoutHint.textContent = 'Insufficient cash.';
        return;
      }

      paymentAlert.hidden = false;
      paymentAlert.textContent = change > 0 ? '✓ Payment sufficient — Change: ' + money(change) : '✓ Exact amount received.';
      paymentAlert.className = 'pos-payment__alert is-success';
      paymentPanel?.classList.add('is-ok');
      changeRow?.classList.add('is-ok');
      checkoutBtn.disabled = false;
      checkoutBtn.querySelector('.pos-checkout-text').textContent = '✓ Complete Sale';
      checkoutHint.textContent = 'Ready to complete sale.';
    }

    async function runSearch(q) {
      resultsEl.innerHTML = '<p class="text-muted">Searching…</p>';
      try {
        const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        renderResults(data.items || []);
      } catch (e) {
        resultsEl.innerHTML = '<p class="text-muted">Search failed. Try again.</p>';
      }
    }

    async function lookupScan(payload, opts) {
      const value = String(payload || '').trim();
      if (!value || scanBusy) return false;

      const now = Date.now();
      if (value === lastScanValue && now - lastScanAt < 1800) return false;

      scanBusy = true;
      lastScanValue = value;
      lastScanAt = now;
      setPosStatus('lookup', 'Looking up item…');
      setScanStatus('Looking up ' + value + '…');

      try {
        const fd = new FormData();
        fd.append('qr_payload', value);
        const res = await fetch(scanUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': window.App?.csrfToken?.() || '',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: fd,
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          setScanStatus(data.message || 'Scan failed', 'warn');
          toast(data.message || 'Scan failed', 'error');
          setPosStatus(scanning ? 'scanning' : 'ready', scanning ? 'Scanner Active' : 'POS Ready');
          return false;
        }

        const ok = addToCart(data.item, true);
        setScanStatus(ok ? 'Added ' + data.item.name : 'Could not add item', scanning ? 'ready' : 'warn');
        if (ok && searchInput) searchInput.value = '';
        if (ok && manualQr) manualQr.value = '';
        setPosStatus(scanning ? 'scanning' : 'ready', scanning ? 'Scanner Active' : 'POS Ready');
        return ok;
      } catch (e) {
        setScanStatus(e.message || 'Scan error', 'warn');
        toast(e.message || 'Scan error', 'error');
        setPosStatus(scanning ? 'scanning' : 'ready', scanning ? 'Scanner Active' : 'POS Ready');
        return false;
      } finally {
        scanBusy = false;
        if (scanning && scanner) {
          try { scanner.pause(true); } catch (_) {}
          setTimeout(() => { try { scanner.resume(); } catch (_) {} }, 1600);
        }
      }
    }

    async function startScanner() {
      if (typeof Html5Qrcode === 'undefined') {
        setScanStatus('Scanner library failed to load. Use manual entry.', 'warn');
        return;
      }

      const readerId = 'pos-qr-reader';
      const readerEl = document.getElementById(readerId);
      if (!readerEl) return;
      if (!scanner) scanner = new Html5Qrcode(readerId);
      if (scanning) return;

      setScanStatus('Starting camera…');

      try {
        const cameras = await Html5Qrcode.getCameras();
        if (!cameras.length) {
          readerEl.innerHTML = '<div class="pos-cart-empty"><p>No camera detected. Use manual entry or USB scanner.</p></div>';
          setScanStatus('No camera detected', 'warn');
          return;
        }

        const camId = cameras[cameras.length - 1].id;
        await scanner.start(
          camId,
          { fps: 10, qrbox: { width: 240, height: 240 } },
          (decoded) => { if (decoded) lookupScan(decoded); },
          () => {}
        );
        scanning = true;
        setScanStatus('Position QR code inside the frame', 'ready');
        setPosStatus('scanning', 'Scanner Active');
        scanToggle?.setAttribute('aria-expanded', 'true');
        if (scanToggle) scanToggle.textContent = 'Stop Scanner';
      } catch (e) {
        readerEl.innerHTML = '<div class="pos-cart-empty"><p>Camera could not start. Use manual entry.</p></div>';
        setScanStatus('Camera unavailable', 'warn');
        toast('Camera could not start', 'error');
      }
    }

    async function stopScanner() {
      if (!scanner || !scanning) {
        scanning = false;
        scanToggle?.setAttribute('aria-expanded', 'false');
        if (scanToggle) scanToggle.textContent = 'Scan QR';
        setScanStatus('Camera idle');
        if (!processing) setPosStatus('ready', 'POS Ready');
        return;
      }

      try {
        await scanner.stop();
        await scanner.clear();
      } catch (_) {}

      scanning = false;
      scanToggle?.setAttribute('aria-expanded', 'false');
      if (scanToggle) scanToggle.textContent = 'Scan QR';
      setScanStatus('Camera stopped');
      if (!processing) setPosStatus('ready', 'POS Ready');
    }

    function openScannerPanel() {
      if (!scannerOverlay) return;
      scannerOverlay.hidden = false;
      document.body.style.overflow = 'hidden';
      startScanner();
      manualQr?.focus();
    }

    async function closeScannerPanel() {
      await stopScanner();
      if (scannerOverlay) scannerOverlay.hidden = true;
      document.body.style.overflow = '';
      searchInput?.focus();
    }

    scanToggle?.addEventListener('click', () => {
      if (scannerOverlay?.hidden) openScannerPanel();
      else if (scanning) closeScannerPanel();
      else openScannerPanel();
    });

    scanClose?.addEventListener('click', () => closeScannerPanel());

    scannerOverlay?.addEventListener('click', (e) => {
      if (e.target === scannerOverlay) closeScannerPanel();
    });

    manualScanBtn?.addEventListener('click', () => {
      const val = manualQr?.value.trim();
      if (val) lookupScan(val);
    });

    manualQr?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const val = manualQr.value.trim();
        if (val) lookupScan(val);
      }
    });

    searchInput?.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => runSearch(searchInput.value.trim()), 250);
    });

    searchInput?.addEventListener('keydown', async (e) => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const q = searchInput.value.trim();
      if (!q) return;
      const matched = await lookupScan(q);
      if (!matched) {
        resultsEl.querySelector('.pos-result:not([disabled])')?.click();
      }
    });

    [discountEl, taxEl, tenderedEl].forEach((el) => el?.addEventListener('input', updateTotals));

    tenderedEl?.addEventListener('keydown', (e) => {
      if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
      if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) return;
      if (!/^[0-9.]$/.test(e.key)) { e.preventDefault(); return; }
      if (e.key === '.' && String(tenderedEl.value).includes('.')) e.preventDefault();
    });

    document.querySelectorAll('.pos-quick-cash__btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const amount = btn.dataset.amount;
        if (amount === 'exact') {
          tenderedEl.value = getCartTotal().total;
        } else {
          tenderedEl.value = amount;
        }
        updateTotals();
        tenderedEl.focus();
      });
    });

    mobileReview?.addEventListener('click', () => {
      cartPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      tenderedEl?.focus();
    });

    document.getElementById('pos-checkout-form')?.addEventListener('submit', (e) => {
      const { total } = getCartTotal();
      const tendered = roundMoney(tenderedEl?.value);
      const hasTendered = String(tenderedEl?.value ?? '').trim() !== '';

      if (cart.size === 0) {
        e.preventDefault();
        toast('Add at least one item to the cart.', 'error');
        return;
      }

      if (!hasTendered || tendered < total) {
        e.preventDefault();
        updateTotals();
        toast('Insufficient cash. Cash received must be at least the total amount.', 'error');
        tenderedEl?.focus();
        return;
      }

      processing = true;
      syncHidden();
      checkoutBtn.disabled = true;
      checkoutBtn.classList.add('is-loading');
      checkoutBtn.querySelector('.pos-checkout-text').textContent = 'Processing Sale…';
      setPosStatus('processing', 'Processing Sale');
      stopScanner();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && scannerOverlay && !scannerOverlay.hidden) {
        e.preventDefault();
        closeScannerPanel();
        return;
      }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        searchInput?.focus();
        return;
      }
      if (e.key === 'F2') {
        e.preventDefault();
        searchInput?.focus();
        return;
      }
      if (e.key === 'F4') {
        e.preventDefault();
        tenderedEl?.focus();
      }
    });

    window.addEventListener('beforeunload', () => {
      if (scanning && scanner) { try { scanner.stop(); } catch (_) {} }
    });

    if (window.App?.registerPageCleanup) {
      App.registerPageCleanup(stopScanner);
    }

    setPosStatus('ready', 'POS Ready');
    updateTotals();
    runSearch('');
  }

  window.PosModule = { initTerminal };
})();
