@extends('layouts.app')
@section('title', 'Point of Sale')
@section('content')
<div class="page-header">
    <div>
        <h1>Point of Sale</h1>
        <p class="page-header__meta">Scan QR or search · Stock deducts on checkout · Next: {{ $nextNumber }}</p>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn--secondary" id="pos-scan-toggle" aria-expanded="false">Scan QR</button>
        <a href="{{ route('pos.index') }}" class="btn btn--secondary">Sale history</a>
    </div>
</div>

<div class="card mb-2 pos-scanner-panel" id="pos-scanner-panel" hidden>
    <div class="card__header">
        <h2 class="card__title">QR scanner</h2>
        <button type="button" class="btn btn--ghost btn--sm" id="pos-scan-close">Close</button>
    </div>
    <div class="card__body">
        <div id="pos-qr-reader" class="scan-viewport pos-scan-viewport" aria-label="POS camera scanner"></div>
        <p class="form-hint mt-1">Point the camera at an item QR label. Scanned consumables are added to the cart.</p>
        <div class="form-group mt-2 mb-0">
            <label class="form-label" for="pos-manual-qr">Or enter QR / item code</label>
            <div class="pos-scan-manual">
                <input type="text" id="pos-manual-qr" class="form-control" placeholder="INV-2026-000001 or item code" autocomplete="off">
                <button type="button" class="btn btn--primary" id="pos-manual-scan-btn">Add</button>
            </div>
        </div>
        <p class="form-hint mt-1" id="pos-scan-status">Camera idle</p>
    </div>
</div>

<div class="pos-layout">
    <div class="card pos-catalog">
        <div class="card__header">
            <h2 class="card__title">Catalog</h2>
        </div>
        <div class="card__body">
            <div class="form-group mb-0">
                <label class="form-label" for="pos-search">Search or scan with USB scanner</label>
                <input type="search" id="pos-search" class="form-control" placeholder="Name, code, or scan QR then Enter…" autocomplete="off" autofocus>
            </div>
            <div id="pos-results" class="pos-results mt-2">
                <p class="text-muted">Search or scan to add items to the cart.</p>
            </div>
        </div>
    </div>

    <div class="card pos-cart">
        <div class="card__header">
            <h2 class="card__title">Cart</h2>
        </div>
        <div class="card__body">
            <form method="post" action="{{ route('pos.checkout') }}" id="pos-checkout-form">
                @csrf
                <div class="form-grid form-grid--1">
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Customer</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}" placeholder="Walk-in / name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="payment_method_display">Payment</label>
                        <input type="text" id="payment_method_display" class="form-control" value="Cash" readonly>
                        <input type="hidden" name="payment_method" id="payment_method" value="cash">
                    </div>
                </div>

                <div class="table-wrap mt-2">
                    <table class="data-table" id="pos-cart-table">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width:100px;">Qty</th>
                            <th style="width:110px;">Price</th>
                            <th style="width:110px;">Line</th>
                            <th style="width:48px;"></th>
                        </tr>
                        </thead>
                        <tbody id="pos-cart-body">
                        <tr id="pos-cart-empty">
                            <td colspan="5" class="text-muted">Cart is empty</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pos-totals mt-2">
                    <div class="pos-totals__row">
                        <span>Subtotal</span>
                        <strong id="pos-subtotal">₱0.00</strong>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="discount">Discount (₱)</label>
                        <input type="number" step="0.01" min="0" inputmode="decimal" name="discount" id="discount" class="form-control" value="{{ old('discount', 0) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tax">Tax (₱)</label>
                        <input type="number" step="0.01" min="0" inputmode="decimal" name="tax" id="tax" class="form-control" value="{{ old('tax', 0) }}">
                    </div>
                    <div class="pos-totals__row pos-totals__row--grand">
                        <span>Total amount</span>
                        <strong id="pos-total">₱0.00</strong>
                    </div>

                    <div class="pos-payment" id="pos-payment">
                        <div class="pos-payment__title">Payment</div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="amount_tendered" id="amount_tendered_label">
                                Cash received <span class="req">*</span>
                            </label>
                            <div class="pos-money-input">
                                <span class="pos-money-input__prefix" aria-hidden="true">₱</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    name="amount_tendered"
                                    id="amount_tendered"
                                    class="form-control pos-money-input__field"
                                    value="{{ old('amount_tendered') }}"
                                    placeholder="0.00"
                                    required
                                    autocomplete="off"
                                >
                            </div>
                            <span class="form-hint" id="amount_tendered_hint">Enter the cash given by the customer.</span>
                        </div>

                        <div class="pos-totals__row pos-change-row" id="pos-change-row">
                            <span id="pos-change-label">Change</span>
                            <strong id="pos-change">₱0.00</strong>
                        </div>
                        <p class="pos-payment__alert" id="pos-payment-alert" hidden role="alert"></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-textarea" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div id="pos-hidden-items"></div>

                <button type="submit" class="btn btn--primary btn--block mt-2" id="pos-checkout-btn" disabled>
                    Complete sale
                </button>
                <p class="form-hint mt-1">Enter cash received equal to or greater than the total before completing the sale.</p>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const searchInput = document.getElementById('pos-search');
    const resultsEl = document.getElementById('pos-results');
    const cartBody = document.getElementById('pos-cart-body');
    const hiddenItems = document.getElementById('pos-hidden-items');
    const checkoutBtn = document.getElementById('pos-checkout-btn');
    const discountEl = document.getElementById('discount');
    const taxEl = document.getElementById('tax');
    const tenderedEl = document.getElementById('amount_tendered');
    const changeRow = document.getElementById('pos-change-row');
    const changeEl = document.getElementById('pos-change');
    const paymentAlert = document.getElementById('pos-payment-alert');
    const paymentPanel = document.getElementById('pos-payment');
    const scannerPanel = document.getElementById('pos-scanner-panel');
    const scanToggle = document.getElementById('pos-scan-toggle');
    const scanClose = document.getElementById('pos-scan-close');
    const scanStatus = document.getElementById('pos-scan-status');
    const manualQr = document.getElementById('pos-manual-qr');
    const manualScanBtn = document.getElementById('pos-manual-scan-btn');
    const searchUrl = @json(route('pos.search'));
    const scanUrl = @json(route('pos.scan'));
    const moneyFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

    /** @type {Map<number, {id:number,name:string,item_code:string,unit:string,available:number,qty:number,unit_price:number}>} */
    const cart = new Map();
    let debounceTimer = null;
    let scanner = null;
    let scanning = false;
    let scanBusy = false;
    let lastScanValue = '';
    let lastScanAt = 0;

    function money(n) {
        return moneyFmt.format(Number(n) || 0);
    }

    function toast(message, type) {
        window.App?.toast?.(message, type || 'info');
    }

    function setScanStatus(text) {
        if (scanStatus) scanStatus.textContent = text;
    }

    function renderResults(items) {
        window.__posSearchItems = {};
        if (!items.length) {
            resultsEl.innerHTML = '<p class="text-muted">No consumable items found.</p>';
            return;
        }

        resultsEl.innerHTML = items.map((item) => {
            window.__posSearchItems[item.id] = item;
            const stockClass = item.is_out_of_stock ? 'badge--danger' : (item.is_low_stock ? 'badge--warn' : 'badge--available');
            const disabled = item.is_out_of_stock || item.quantity <= 0;
            return `
                <button type="button" class="pos-result ${disabled ? 'is-disabled' : ''}" data-id="${item.id}" ${disabled ? 'disabled' : ''}>
                    <div class="pos-result__main">
                        <strong>${escapeHtml(item.name)}</strong>
                        <span class="text-muted">${escapeHtml(item.item_code)} · ${money(item.selling_price)} / ${escapeHtml(item.unit)}</span>
                    </div>
                    <span class="badge ${stockClass}">${item.quantity} ${escapeHtml(item.unit)}</span>
                </button>`;
        }).join('');

        resultsEl.querySelectorAll('.pos-result').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = window.__posSearchItems[btn.getAttribute('data-id')];
                if (item) addToCart(item);
            });
        });
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[ch]));
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
                item_code: item.item_code,
                unit: item.unit,
                available: available,
                qty: 1,
                unit_price: Number(item.selling_price) || 0,
            });
        }
        renderCart();
        if (fromScan) {
            toast(item.name + ' added to cart', 'success');
        }
        searchInput?.focus();
        return true;
    }

    function renderCart() {
        const empty = document.getElementById('pos-cart-empty');
        [...cartBody.querySelectorAll('tr.pos-cart-row')].forEach((tr) => tr.remove());

        if (cart.size === 0) {
            if (empty) empty.style.display = '';
            syncHidden();
            updateTotals();
            return;
        }

        if (empty) empty.style.display = 'none';

        cart.forEach((line) => {
            const tr = document.createElement('tr');
            tr.className = 'pos-cart-row';
            tr.innerHTML = `
                <td>
                    <strong>${escapeHtml(line.name)}</strong>
                    <div class="text-muted">${escapeHtml(line.item_code)} · avail ${line.available} ${escapeHtml(line.unit)}</div>
                </td>
                <td><input type="number" step="0.01" min="0.01" max="${line.available}" class="form-control cart-qty" data-id="${line.id}" value="${line.qty}"></td>
                <td><input type="number" step="0.01" min="0" class="form-control cart-price" data-id="${line.id}" value="${line.unit_price}"></td>
                <td class="cart-line-total">${money(line.qty * line.unit_price)}</td>
                <td><button type="button" class="btn btn--ghost btn--sm cart-remove" data-id="${line.id}">×</button></td>`;
            cartBody.appendChild(tr);
        });

        cartBody.querySelectorAll('.cart-qty').forEach((el) => {
            el.addEventListener('change', () => {
                const id = Number(el.dataset.id);
                const line = cart.get(id);
                if (!line) return;
                let qty = Number(el.value) || 0;
                if (qty > line.available) {
                    qty = line.available;
                    el.value = qty;
                    toast('Insufficient Inventory. Only ' + line.available + ' units are currently available.', 'error');
                }
                if (qty <= 0) {
                    cart.delete(id);
                } else {
                    line.qty = qty;
                }
                renderCart();
            });
        });

        cartBody.querySelectorAll('.cart-price').forEach((el) => {
            el.addEventListener('change', () => {
                const id = Number(el.dataset.id);
                const line = cart.get(id);
                if (!line) return;
                line.unit_price = Math.max(0, Number(el.value) || 0);
                renderCart();
            });
        });

        cartBody.querySelectorAll('.cart-remove').forEach((el) => {
            el.addEventListener('click', () => {
                cart.delete(Number(el.dataset.id));
                renderCart();
            });
        });

        syncHidden();
        updateTotals();
    }

    function syncHidden() {
        hiddenItems.innerHTML = '';
        let i = 0;
        cart.forEach((line) => {
            hiddenItems.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="items[${i}][inventory_item_id]" value="${line.id}">
                <input type="hidden" name="items[${i}][quantity]" value="${line.qty}">
                <input type="hidden" name="items[${i}][unit_price]" value="${line.unit_price}">
            `);
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
        changeEl.textContent = money(Math.max(0, change));

        paymentPanel?.classList.remove('is-ok', 'is-short', 'is-empty');
        changeRow?.classList.remove('is-ok', 'is-short');

        if (!cartReady) {
            paymentAlert.hidden = true;
            paymentAlert.textContent = '';
            paymentPanel?.classList.add('is-empty');
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Complete sale';
            return;
        }

        if (!hasTendered) {
            paymentAlert.hidden = false;
            paymentAlert.textContent = 'Enter cash received to calculate change.';
            paymentAlert.className = 'pos-payment__alert is-info';
            paymentPanel?.classList.add('is-empty');
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Complete sale';
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
            checkoutBtn.textContent = 'Insufficient cash';
            return;
        }

        paymentAlert.hidden = false;
        paymentAlert.textContent = change > 0
            ? 'Change due: ' + money(change)
            : 'Exact amount received.';
        paymentAlert.className = 'pos-payment__alert is-success';
        paymentPanel?.classList.add('is-ok');
        changeRow?.classList.add('is-ok');
        checkoutBtn.disabled = false;
        checkoutBtn.textContent = 'Complete sale';
    }

    async function runSearch(q) {
        resultsEl.innerHTML = '<p class="text-muted">Searching…</p>';
        try {
            const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            renderResults(data.items || []);
        } catch (e) {
            resultsEl.innerHTML = '<p class="text-muted">Search failed. Try again.</p>';
        }
    }

    async function lookupScan(payload, opts) {
        const options = opts || {};
        const value = String(payload || '').trim();
        if (!value || scanBusy) return false;

        const now = Date.now();
        if (value === lastScanValue && now - lastScanAt < 1800) {
            return false;
        }

        scanBusy = true;
        lastScanValue = value;
        lastScanAt = now;
        setScanStatus('Looking up ' + value + '…');

        try {
            const fd = new FormData();
            fd.append('qr_payload', value);
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.App?.csrfToken?.() || document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                setScanStatus(data.message || 'Scan failed');
                toast(data.message || 'Scan failed', 'error');
                return false;
            }

            const ok = addToCart(data.item, true);
            setScanStatus(ok ? ('Added ' + data.item.name) : 'Could not add item');
            if (ok && searchInput) searchInput.value = '';
            if (ok && manualQr) manualQr.value = '';
            return ok;
        } catch (e) {
            setScanStatus(e.message || 'Scan error');
            toast(e.message || 'Scan error', 'error');
            return false;
        } finally {
            scanBusy = false;
            if (scanning && scanner) {
                try { scanner.pause(true); } catch (_) {}
                setTimeout(() => {
                    try { scanner.resume(); } catch (_) {}
                }, 1600);
            }
        }
    }

    async function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            setScanStatus('Scanner library failed to load. Use manual entry.');
            return;
        }

        const readerId = 'pos-qr-reader';
        const readerEl = document.getElementById(readerId);
        if (!readerEl) return;

        if (!scanner) {
            scanner = new Html5Qrcode(readerId);
        }

        if (scanning) return;

        setScanStatus('Starting camera…');

        try {
            const cameras = await Html5Qrcode.getCameras();
            if (!cameras.length) {
                readerEl.innerHTML = '<div class="empty-state"><p class="empty-state__title">No camera detected</p><p class="text-muted">Use manual QR entry or a USB scanner.</p></div>';
                setScanStatus('No camera detected');
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
            setScanStatus('Camera ready — scan a label');
            scanToggle?.setAttribute('aria-expanded', 'true');
            scanToggle.textContent = 'Stop scanner';
        } catch (e) {
            readerEl.innerHTML = '<div class="empty-state"><p class="text-muted">Camera could not start. Use manual entry or a USB scanner.</p></div>';
            setScanStatus('Camera unavailable');
            toast('Camera could not start', 'error');
        }
    }

    async function stopScanner() {
        if (!scanner || !scanning) {
            scanning = false;
            scanToggle?.setAttribute('aria-expanded', 'false');
            if (scanToggle) scanToggle.textContent = 'Scan QR';
            setScanStatus('Camera idle');
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
    }

    function openScannerPanel() {
        if (!scannerPanel) return;
        scannerPanel.hidden = false;
        startScanner();
    }

    async function closeScannerPanel() {
        await stopScanner();
        if (scannerPanel) scannerPanel.hidden = true;
    }

    scanToggle?.addEventListener('click', () => {
        if (scannerPanel?.hidden) {
            openScannerPanel();
        } else if (scanning) {
            closeScannerPanel();
        } else {
            openScannerPanel();
        }
    });

    scanClose?.addEventListener('click', () => closeScannerPanel());

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
        const q = searchInput.value.trim();
        debounceTimer = setTimeout(() => runSearch(q), 250);
    });

    searchInput?.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const q = searchInput.value.trim();
        if (!q) return;

        // Prefer exact QR / item-code match (USB wedge scanners send Enter).
        const matched = await lookupScan(q);
        if (!matched) {
            const first = resultsEl.querySelector('.pos-result:not([disabled])');
            first?.click();
        }
    });

    [discountEl, taxEl, tenderedEl].forEach((el) => el?.addEventListener('input', updateTotals));

    tenderedEl?.addEventListener('keydown', (e) => {
        // Allow navigation / edit keys and one decimal point.
        if (['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) {
            return;
        }
        if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }
        if (!/^[0-9.]$/.test(e.key)) {
            e.preventDefault();
            return;
        }
        if (e.key === '.' && String(tenderedEl.value).includes('.')) {
            e.preventDefault();
        }
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

        syncHidden();
        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Processing…';
        stopScanner();
    });

    window.addEventListener('beforeunload', () => {
        if (scanning && scanner) {
            try { scanner.stop(); } catch (_) {}
        }
    });

    if (window.App?.registerPageCleanup) {
        App.registerPageCleanup(stopScanner);
    }

    updateTotals();
    runSearch('');
})();
</script>
@endpush
