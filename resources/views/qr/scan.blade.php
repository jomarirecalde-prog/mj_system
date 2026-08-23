@extends('layouts.app')

@section('title', 'Scan QR')

@push('styles')
<style>
    .scan-page { padding-bottom: 2rem; }
    #qr-reader { width: 100%; }
    #qr-reader video { border-radius: 8px; }
</style>
@endpush

@section('content')
<div class="scan-page">
    <div class="page-header">
        <div>
            <h1>Scan QR code</h1>
            <p class="page-header__meta">Point your camera at an item label or enter a code manually</p>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__body">
            <div id="qr-reader" class="scan-viewport" aria-label="Camera scanner"></div>
            <p class="form-hint mt-1">Allow camera access when prompted. Works best in good lighting.</p>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__header"><h2 class="card__title">Manual entry</h2></div>
        <div class="card__body">
            <form id="manual-scan-form">
                <div class="form-group">
                    <label class="form-label" for="qr_payload">QR payload / code</label>
                    <input type="text" id="qr_payload" name="qr_payload" class="form-control" placeholder="Paste or type QR content" autocomplete="off">
                </div>
                <button type="submit" class="btn btn--primary btn--block mt-1" id="manual-scan-btn">Look up item</button>
            </form>
        </div>
    </div>

    <div id="scan-result" class="card scan-result" style="display:none;">
        <div class="card__header">
            <h2 class="card__title" id="scan-result-title">Result</h2>
        </div>
        <div class="card__body" id="scan-result-body"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const lookupUrl = @json(route('qr.scan.lookup'));
    const canModify = @json(auth()->user()->canModifyInventory());
    const inventoryBase = @json(url('inventory'));
    const resultPanel = document.getElementById('scan-result');
    const resultTitle = document.getElementById('scan-result-title');
    const resultBody = document.getElementById('scan-result-body');
    let scanner = null;
    let scanning = false;

    function showResult(success, title, html) {
        resultPanel.style.display = 'block';
        resultPanel.classList.toggle('is-success', success);
        resultPanel.classList.toggle('is-error', !success);
        resultTitle.textContent = title;
        resultBody.innerHTML = html;
        resultPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function esc(v) {
        return String(v ?? '—').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function itemActions(data) {
        const item = data.item;
        const actions = data.actions || {};
        const lowStock = data.is_low_stock ? ' <span class="badge badge--warn">⚠️ Low Stock</span>' : '';
        let html = '<dl class="dl-grid">' +
            '<div class="dl-item"><dt>Item</dt><dd>' + esc(item.name) + '</dd></div>' +
            '<div class="dl-item"><dt>Item code</dt><dd>' + esc(item.item_code) + '</dd></div>' +
            '<div class="dl-item"><dt>Type</dt><dd>' + esc(data.is_consumable ? 'Consumable' : 'Asset') + '</dd></div>' +
            '<div class="dl-item"><dt>Current Stock</dt><dd><strong>' + esc(item.quantity) + ' ' + esc(item.unit) + '</strong>' + lowStock + '</dd></div>' +
            '<div class="dl-item"><dt>Location</dt><dd>' + esc(item.location ? item.location.name : '—') + '</dd></div>' +
            '<div class="dl-item"><dt>Status</dt><dd>' + esc(item.status) + '</dd></div>' +
            '<div class="dl-item"><dt>Condition</dt><dd>' + esc(item.condition) + '</dd></div>' +
            '<div class="dl-item"><dt>Current holder</dt><dd>' + esc(data.current_holder || '—') + '</dd></div>' +
            '</dl>';
        html += '<div class="btn-group mt-2" style="flex-wrap:wrap;">';
        html += '<a href="' + esc(data.redirect_url) + '" class="btn btn--primary">View History</a>';
        if (canModify) {
            if (actions.stock_in) html += '<a href="' + esc(actions.stock_in) + '" class="btn btn--secondary">Stock In</a>';
            if (data.is_consumable) {
                if (actions.issue) html += '<a href="' + esc(actions.issue) + '" class="btn btn--secondary">Issue Item</a>';
                if (actions.consume) html += '<a href="' + esc(actions.consume) + '" class="btn btn--secondary">Consume Item</a>';
                if (actions.return) html += '<a href="' + esc(actions.return) + '" class="btn btn--secondary">Return</a>';
            } else {
                if (actions.borrow) html += '<a href="' + esc(actions.borrow) + '" class="btn btn--secondary">Borrow</a>';
                if (actions.return) html += '<a href="' + esc(data.redirect_url) + '" class="btn btn--secondary">Return</a>';
            }
            if (actions.transfer) html += '<a href="' + esc(actions.transfer) + '" class="btn btn--secondary">Transfer</a>';
            if (actions.adjust) html += '<a href="' + esc(actions.adjust) + '" class="btn btn--ghost">Adjust</a>';
        }
        html += '</div>';
        return html;
    }

    async function lookup(payload) {
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
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                const title = data.code === 'archived' ? 'Archived item' : (data.code === 'invalid' ? 'Invalid QR' : 'Not found');
                showResult(false, title, '<p>' + esc(data.message || 'Unable to find item.') + '</p>');
                App.toast(data.message || 'Lookup failed', 'error');
                return;
            }
            showResult(true, 'QR scanned successfully', itemActions(data));
            App.toast('Item matched successfully', 'success');
        } catch (e) {
            showResult(false, 'Error', '<p>' + esc(e.message) + '</p>');
            App.toast(e.message, 'error');
        } finally {
            App.setLoading(btn, false);
        }
    }

    document.getElementById('manual-scan-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const val = document.getElementById('qr_payload').value.trim();
        if (val) lookup(val);
    });

    function onScanSuccess(decoded) {
        if (!decoded) return;
        lookup(decoded);
        if (scanner && scanning) {
            scanner.pause(true);
            setTimeout(() => scanner.resume(), 2500);
        }
    }

    if (typeof Html5Qrcode !== 'undefined') {
        const readerId = 'qr-reader';
        scanner = new Html5Qrcode(readerId);
        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras.length) {
                document.getElementById('qr-reader').innerHTML = '<div class="empty-state"><p class="empty-state__title">No camera detected</p><p class="text-muted">Use manual entry below.</p></div>';
                return;
            }
            const camId = cameras[cameras.length - 1].id;
            scanner.start(camId, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess, () => {})
                .then(() => { scanning = true; })
                .catch(() => {
                    document.getElementById('qr-reader').innerHTML = '<div class="empty-state"><p class="text-muted">Camera could not start. Use manual entry.</p></div>';
                });
        }).catch(() => {
            document.getElementById('qr-reader').innerHTML = '<div class="empty-state"><p class="text-muted">Camera access unavailable.</p></div>';
        });

        const stopScanner = async () => {
            if (!scanner) return;
            try {
                if (scanning) await scanner.stop();
                await scanner.clear();
            } catch (_) {}
            scanning = false;
        };

        if (window.App?.registerPageCleanup) {
            App.registerPageCleanup(stopScanner);
        }
        window.addEventListener('beforeunload', stopScanner);
    }
})();
</script>
@endpush
