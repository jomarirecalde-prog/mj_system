@extends('layouts.app')

@section('title', 'QR Attendance Scanner')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/scanning.css') }}">
@endpush

@section('content')
<div class="aa-module scan-module scan-attendance">
    <header class="scan-header">
        <div class="scan-header__left">
            <span class="scan-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </span>
            <div>
                <h1 class="scan-header__title">QR Attendance Scanner</h1>
                <p class="scan-header__desc">Position employee QR code inside the frame.</p>
                <p class="scan-status scan-status--initializing" id="att-camera-status" role="status" aria-live="polite">
                    <span class="scan-status__dot" aria-hidden="true"></span>
                    <span class="scan-status__text">Initializing…</span>
                </p>
            </div>
        </div>
    </header>

    <div class="card mb-2">
        <div class="card__body">
            <div class="scan-viewport-wrap">
                <div class="scan-viewport">
                    <div id="att-qr-reader" aria-label="Attendance camera scanner"></div>
                    <div class="scan-frame scan-frame__corners" aria-hidden="true"><span></span></div>
                    <div class="scan-line" aria-hidden="true"></div>
                    <span class="scan-viewport__label">Scan QR</span>
                </div>
                <p class="scan-viewport__hint" id="att-viewport-hint">Position the QR code inside the frame.</p>
            </div>

            <div class="scan-manual">
                <p class="scan-manual__label">Manual / USB scanner</p>
                <form id="att-manual-form" class="scan-manual__row">
                    <label class="sr-only" for="att_qr_payload">Employee QR code</label>
                    <input type="text" id="att_qr_payload" class="scan-manual__input" placeholder="Employee QR code" autocomplete="off" autofocus>
                    <button type="submit" class="btn btn--primary" id="att-punch-btn">Record</button>
                </form>
            </div>
        </div>
    </div>

    <div id="att-feedback" class="scan-feedback" role="status" aria-live="polite" aria-atomic="true">
        <div class="scan-feedback__icon" id="att-fb-icon" aria-hidden="true"></div>
        <div class="scan-feedback__title" id="att-fb-title"></div>
        <div class="scan-feedback__name" id="att-fb-name"></div>
        <div class="scan-feedback__meta" id="att-fb-meta"></div>
        <div class="scan-feedback__time" id="att-fb-time"></div>
        <div class="scan-feedback__msg" id="att-fb-msg"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/scanning.js') }}"></script>
<script>
(function () {
    const punchUrl = @json(route('attendance.scanner.punch'));
    const input = document.getElementById('att_qr_payload');
    const feedback = document.getElementById('att-feedback');
    const hintEl = document.getElementById('att-viewport-hint');
    const statusEl = document.getElementById('att-camera-status');
    const esc = ScanningUI.escapeHtml;

    let lastPayload = '';
    let lastAt = 0;
    let busy = false;
    let dismissTimer = null;
    let punchAbort = null;

    const fbIcons = {
        success: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        warn: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
        error: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
    };

    function feedbackType(data) {
        const code = data.code || '';
        if (data.success) return code === 'late' ? 'warn' : 'success';
        if (['invalid', 'inactive'].includes(code)) return 'warn';
        if (['already_in', 'already_out', 'cooldown'].includes(code)) return 'warn';
        return 'error';
    }

    function showFeedback(data) {
        clearTimeout(dismissTimer);
        const type = feedbackType(data);
        feedback.className = 'scan-feedback is-visible scan-feedback--' + type;
        document.getElementById('att-fb-icon').innerHTML = fbIcons[type];

        const prefix = data.success ? '✓ ' : (type === 'warn' ? '⚠ ' : '✕ ');
        document.getElementById('att-fb-title').textContent = prefix + (data.title || 'Result');

        const emp = data.employee || {};
        document.getElementById('att-fb-name').textContent = emp.name || '';
        document.getElementById('att-fb-meta').innerHTML = emp.employee_id
            ? ('Employee ID: ' + esc(emp.employee_id) + (emp.department ? '<br>Department: ' + esc(emp.department) : ''))
            : '';
        document.getElementById('att-fb-time').textContent = data.time || (data.record ? (data.record.time_in || data.record.time_out || '') : '');
        document.getElementById('att-fb-msg').textContent = data.message || '';

        feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        const dismissMs = data.success ? 3500 : 4000;
        dismissTimer = setTimeout(hideFeedback, dismissMs);
    }

    function hideFeedback() {
        feedback.classList.remove('is-visible');
        input.focus();
    }

    async function punch(payload) {
        payload = String(payload || '').trim();
        if (!payload || busy) return;
        const now = Date.now();
        if (payload === lastPayload && (now - lastAt) < 2500) return;
        lastPayload = payload;
        lastAt = now;
        busy = true;

        if (punchAbort) punchAbort.abort();
        punchAbort = new AbortController();

        const btn = document.getElementById('att-punch-btn');
        if (window.App?.setLoading) App.setLoading(btn, true);
        try {
            const fd = new FormData();
            fd.append('qr_payload', payload);
            const res = await fetch(punchUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: fd,
                signal: punchAbort.signal,
            });
            const data = await res.json();
            showFeedback(data);
            input.value = '';
            input.focus();
        } catch (e) {
            if (e.name === 'AbortError') return;
            showFeedback({ success: false, code: 'error', title: 'SCAN FAILED', message: 'Unable to reach the server.' });
        } finally {
            busy = false;
            if (window.App?.setLoading) App.setLoading(btn, false);
        }
    }

    document.getElementById('att-manual-form').addEventListener('submit', (e) => {
        e.preventDefault();
        punch(input.value);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            punch(input.value);
        }
    });

    const camera = ScanningUI.createCameraManager({
        readerId: 'att-qr-reader',
        onScan: punch,
        fps: 8,
        qrbox: { width: 260, height: 260 },
        onStatusChange(status, message) {
            ScanningUI.updateStatusEl(statusEl, status, message ? '● ' + message : null);
            const hints = {
                initializing: 'Starting camera…',
                ready: 'Position the QR code inside the frame',
                unavailable: 'Camera unavailable — use manual entry or USB scanner.',
                denied: 'Allow camera access to start scanning.',
            };
            if (hintEl) hintEl.textContent = hints[status] || hints.ready;
        },
    });

    camera.init();
    camera.registerCleanup();
})();
</script>
@endpush
