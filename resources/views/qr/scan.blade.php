@extends('layouts.app')

@section('title', 'Scan QR')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/scanning.css') }}">
@endpush

@section('content')
<div class="scan-module">
    <header class="scan-header">
        <div class="scan-header__left">
            <span class="scan-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </span>
            <div>
                <h1 class="scan-header__title">QR Inventory Scanner</h1>
                <p class="scan-header__desc">Scan an item QR code to instantly view inventory information.</p>
                <p class="scan-status scan-status--initializing" id="scan-camera-status" role="status" aria-live="polite">
                    <span class="scan-status__dot" aria-hidden="true"></span>
                    <span class="scan-status__text">Initializing…</span>
                </p>
            </div>
        </div>
    </header>

    <div class="card">
        <div class="card__body">
            <div class="scan-viewport-wrap">
                <div class="scan-viewport" id="qr-reader-wrap">
                    <div id="qr-reader" aria-label="Camera QR scanner"></div>
                    <div class="scan-frame scan-frame__corners" aria-hidden="true"><span></span></div>
                    <div class="scan-line" aria-hidden="true"></div>
                    <span class="scan-viewport__label">Scan QR</span>
                </div>
                <p class="scan-viewport__hint" id="scan-viewport-hint">Position the QR code inside the frame</p>

                <div class="scan-toolbar" data-scan-toolbar hidden>
                    <div class="scan-toolbar__left">
                        <select id="scan-camera-select" class="form-select" aria-label="Select camera" hidden></select>
                        <button type="button" class="btn btn--secondary btn--sm" id="scan-camera-restart">Restart camera</button>
                        <button type="button" class="btn btn--ghost btn--sm" id="scan-camera-stop">Stop camera</button>
                    </div>
                </div>
            </div>

            <div class="scan-manual">
                <p class="scan-manual__label">Can't scan?</p>
                <form id="manual-scan-form" class="scan-manual__row">
                    <label class="sr-only" for="qr_payload">QR Code / Item Code</label>
                    <input type="text" id="qr_payload" name="qr_payload" class="scan-manual__input" placeholder="QR Code / Item Code" autocomplete="off">
                    <button type="submit" class="btn btn--primary" id="manual-scan-btn">Search Item</button>
                </form>
            </div>
        </div>
    </div>

    <div id="scan-result" class="scan-result" role="region" aria-live="polite" aria-atomic="true">
        <div class="scan-result__header">
            <span class="scan-result__icon" id="scan-result-icon" aria-hidden="true"></span>
            <h2 class="scan-result__title" id="scan-result-title">Result</h2>
        </div>
        <div class="scan-result__body" id="scan-result-body"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/scanning.js') }}"></script>
<script>
(function () {
    const lookupUrl = @json(route('qr.scan.lookup'));
    const canModify = @json(auth()->user()->canModifyInventory());
    const esc = ScanningUI.escapeHtml;
    const resultPanel = document.getElementById('scan-result');
    const resultTitle = document.getElementById('scan-result-title');
    const resultBody = document.getElementById('scan-result-body');
    const resultIcon = document.getElementById('scan-result-icon');
    const manualInput = document.getElementById('qr_payload');
    const hintEl = document.getElementById('scan-viewport-hint');
    const statusEl = document.getElementById('scan-camera-status');
    let lookupBusy = false;
    let lookupAbort = null;

    const icons = {
        success: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        error: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
        warn: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
    };

    function updateHint(status) {
        const hints = {
            initializing: 'Starting camera…',
            ready: 'Position the QR code inside the frame',
            unavailable: 'Camera unavailable — use manual entry or USB scanner.',
            denied: 'Allow camera access to start scanning.',
            failed: 'Unable to start camera — use manual entry below.',
            stopped: 'Camera stopped — use manual entry or restart camera.',
        };
        if (hintEl) hintEl.textContent = hints[status] || hints.ready;
    }

    function showResult(type, title, html) {
        resultPanel.className = 'scan-result is-visible scan-result--' + type;
        resultTitle.textContent = title;
        resultBody.innerHTML = html;
        resultIcon.innerHTML = icons[type === 'success' ? 'success' : (type === 'warn' ? 'warn' : 'error')];
        resultPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function itemActions(data) {
        const item = data.item;
        const actions = data.actions || {};
        const lowStockHtml = data.is_low_stock
            ? '<div class="scan-low-stock" role="status"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> Low Stock</div>'
            : '';

        let html = '<p class="scan-item-name">' + esc(item.name) + '</p>';
        html += '<dl class="scan-item-grid">';
        html += '<div class="scan-item-field"><dt>Item Code</dt><dd>' + esc(item.item_code) + '</dd></div>';
        html += '<div class="scan-item-field"><dt>Current Stock</dt><dd>' + esc(item.quantity) + ' ' + esc(item.unit) + lowStockHtml + '</dd></div>';
        html += '<div class="scan-item-field"><dt>Status</dt><dd>' + esc(item.status) + '</dd></div>';
        html += '<div class="scan-item-field"><dt>Condition</dt><dd>' + esc(item.condition) + '</dd></div>';
        html += '<div class="scan-item-field"><dt>Location</dt><dd>' + esc(item.location ? item.location.name : '—') + '</dd></div>';
        html += '<div class="scan-item-field"><dt>Type</dt><dd>' + esc(data.is_consumable ? 'Consumable' : 'Asset') + '</dd></div>';
        if (data.current_holder) {
            html += '<div class="scan-item-field"><dt>Current Holder</dt><dd>' + esc(data.current_holder) + '</dd></div>';
        }
        html += '</dl>';

        html += '<div class="scan-actions">';
        html += '<a href="' + esc(data.redirect_url) + '" class="btn btn--primary">View Item</a>';
        if (canModify) {
            html += '<div class="scan-actions__secondary">';
            if (actions.stock_in) html += '<a href="' + esc(actions.stock_in) + '" class="btn btn--secondary btn--sm">Stock In</a>';
            if (data.is_consumable) {
                if (actions.issue) html += '<a href="' + esc(actions.issue) + '" class="btn btn--secondary btn--sm">Issue</a>';
                if (actions.consume) html += '<a href="' + esc(actions.consume) + '" class="btn btn--secondary btn--sm">Consume</a>';
                if (actions.return) html += '<a href="' + esc(actions.return) + '" class="btn btn--secondary btn--sm">Return</a>';
            } else {
                if (actions.borrow) html += '<a href="' + esc(actions.borrow) + '" class="btn btn--secondary btn--sm">Borrow</a>';
            }
            if (actions.transfer) html += '<a href="' + esc(actions.transfer) + '" class="btn btn--secondary btn--sm">Transfer</a>';
            if (actions.adjust) html += '<a href="' + esc(actions.adjust) + '" class="btn btn--ghost btn--sm">Adjust</a>';
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    async function lookup(payload) {
        payload = String(payload || '').trim();
        if (!payload || lookupBusy) return;
        lookupBusy = true;
        if (lookupAbort) lookupAbort.abort();
        lookupAbort = new AbortController();

        const btn = document.getElementById('manual-scan-btn');
        App.setLoading(btn, true);
        try {
            const fd = new FormData();
            fd.append('qr_payload', payload);
            const res = await fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': App.csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
                signal: lookupAbort.signal,
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                const code = data.code || '';
                let type = 'error';
                let title = 'Item Not Found';
                if (code === 'archived') { type = 'warn'; title = 'Archived Item'; }
                else if (code === 'invalid') { type = 'error'; title = 'Invalid QR Code'; }
                else if (code === 'not_found') { type = 'error'; title = 'Item Not Found'; }
                showResult(type, title, '<p>' + esc(data.message || 'Unable to find item.') + '</p>');
                App.toast(data.message || 'Lookup failed', 'error');
                return;
            }
            showResult('success', 'Item Found', itemActions(data));
            App.toast('Item matched successfully', 'success');
            manualInput.value = '';
            manualInput.focus();
        } catch (e) {
            if (e.name === 'AbortError') return;
            showResult('error', 'Network Error', '<p>' + esc(e.message || 'Unable to reach the server.') + '</p>');
            App.toast(e.message || 'Lookup failed', 'error');
        } finally {
            lookupBusy = false;
            App.setLoading(btn, false);
        }
    }

    document.getElementById('manual-scan-form').addEventListener('submit', (e) => {
        e.preventDefault();
        lookup(manualInput.value);
    });

    const camera = ScanningUI.createCameraManager({
        readerId: 'qr-reader',
        onScan: lookup,
        pauseAfterScanMs: 2500,
        fps: 10,
        qrbox: { width: 260, height: 260 },
        onStatusChange(status, message) {
            ScanningUI.updateStatusEl(statusEl, status, message ? '● ' + message : null);
            updateHint(status);
            if (status === 'ready' || status === 'initializing') {
                document.querySelector('[data-scan-toolbar]')?.removeAttribute('hidden');
            }
        },
    });

    camera.init().then(({ cameras }) => {
        if (cameras.length > 1) {
            const sel = document.getElementById('scan-camera-select');
            sel.hidden = false;
        }
        camera.bindControls({
            cameraSelectId: 'scan-camera-select',
            restartBtnId: 'scan-camera-restart',
            stopBtnId: 'scan-camera-stop',
        });
    });

    camera.registerCleanup();
})();
</script>
@endpush
