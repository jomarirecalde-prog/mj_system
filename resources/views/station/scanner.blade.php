@extends('layouts.station')

@section('title', $station->station_name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/scanning.css') }}">
@endpush

@section('content')
<div class="scan-kiosk">
    <header class="scan-kiosk__header">
        <div>
            <h1 class="scan-kiosk__station-name">{{ $station->station_name }}</h1>
            <p class="scan-kiosk__station-meta">{{ $station->station_code }} · {{ $station->location }}</p>
        </div>
        <div class="scan-kiosk__clock">
            <div class="scan-kiosk__date" id="station-date"></div>
            <div class="scan-kiosk__time" id="station-time" aria-live="off"></div>
        </div>
        <div class="scan-kiosk__actions">
            <span class="scan-kiosk__status" id="station-status" role="status" aria-live="polite">
                <span class="scan-kiosk__status-dot" aria-hidden="true"></span>
                <span id="station-status-text">Connected</span>
            </span>
            @include('partials.pwa-install-button', ['class' => 'scan-kiosk__btn', 'showIcon' => false])
            <form method="post" action="{{ route('station.logout') }}" class="mb-0 logout-form">
                @csrf
                <button type="submit" class="scan-kiosk__btn">Logout</button>
            </form>
        </div>
    </header>

    <main class="scan-kiosk__main">
        <h2 class="scan-kiosk__heading">Scan Employee QR</h2>

        <div class="scan-viewport-wrap">
            <div class="scan-viewport" id="station-viewport-wrap">
                <div id="station-qr-reader" aria-label="QR camera scanner"></div>
                <div class="scan-frame scan-frame__corners" aria-hidden="true"><span></span></div>
                <div class="scan-line" aria-hidden="true"></div>
                <span class="scan-viewport__label">Scan QR</span>
            </div>
            <p class="scan-viewport__hint" id="station-viewport-hint" style="color:#94a3b8;">Position the QR code inside the frame</p>
            <p class="scan-status scan-status--initializing" id="station-camera-status" role="status" aria-live="polite" style="justify-content:center;margin-top:0.75rem;">
                <span class="scan-status__dot" aria-hidden="true"></span>
                <span class="scan-status__text">Initializing…</span>
            </p>
            <div style="text-align:center;margin-top:0.75rem;">
                <button type="button" class="scan-kiosk__btn" id="station-retry-camera" hidden>Retry Camera</button>
            </div>
        </div>

        <div class="scan-kiosk__result" id="station-result" role="status" aria-live="polite" aria-atomic="true">
            <div class="scan-kiosk__result-icon" id="station-result-icon" aria-hidden="true"></div>
            <div class="scan-kiosk__result-title" id="station-result-title"></div>
            <div class="scan-kiosk__result-name" id="station-result-name"></div>
            <div class="scan-kiosk__result-meta" id="station-result-meta"></div>
            <div class="scan-kiosk__result-time" id="station-result-time"></div>
            <div class="scan-kiosk__result-meta" id="station-result-msg"></div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/scanning.js') }}"></script>
<script>
(function () {
    const scanUrl = @json(route('station.scan'));
    const heartbeatUrl = @json(route('station.heartbeat'));
    const timezone = @json($station->timezone ?? 'Asia/Manila');
    const stationName = @json($station->station_name);
    const loginUrl = @json(route('login').'#qr-station');
    const esc = ScanningUI.escapeHtml;

    const cooldownMs = 2500;
    let busy = false;
    let lastPayload = '';
    let lastAt = 0;
    let resumeTimer = null;
    let scanAbort = null;

    const resultEl = document.getElementById('station-result');
    const viewportWrap = document.getElementById('station-viewport-wrap');
    const retryBtn = document.getElementById('station-retry-camera');
    const cameraStatusEl = document.getElementById('station-camera-status');
    const hintEl = document.getElementById('station-viewport-hint');

    const resultIcons = {
        success: '✓',
        warn: '⚠',
        error: '✕',
    };

    function updateClock() {
        const now = new Date();
        const dateFmt = new Intl.DateTimeFormat('en-US', { timeZone: timezone, weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const timeFmt = new Intl.DateTimeFormat('en-US', { timeZone: timezone, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
        document.getElementById('station-date').textContent = dateFmt.format(now);
        document.getElementById('station-time').textContent = timeFmt.format(now);
    }

    setInterval(updateClock, 1000);
    updateClock();

    function resultClass(data) {
        const code = data.code || '';
        if (data.success) return code === 'late' ? 'warn' : 'success';
        if (['invalid', 'inactive', 'inactive_station'].includes(code)) return 'warn';
        return 'error';
    }

    function showResult(data) {
        clearTimeout(resumeTimer);
        const type = resultClass(data);
        resultEl.className = 'scan-kiosk__result is-visible scan-kiosk__result--' + type;
        document.getElementById('station-result-icon').textContent = resultIcons[type];
        document.getElementById('station-result-title').textContent = data.title || 'Result';

        const emp = data.employee || {};
        document.getElementById('station-result-name').textContent = emp.name || '';
        document.getElementById('station-result-meta').innerHTML = emp.employee_id
            ? ('Employee ID: ' + esc(emp.employee_id))
            : '';
        document.getElementById('station-result-time').textContent = data.time || '';
        const msgParts = [data.message || ''];
        if (data.station || stationName) msgParts.push(data.station || stationName);
        document.getElementById('station-result-msg').textContent = msgParts.filter(Boolean).join(' · ');

        viewportWrap.style.opacity = data.success ? '0.35' : '1';

        resumeTimer = setTimeout(() => {
            resultEl.classList.remove('is-visible');
            viewportWrap.style.opacity = '1';
        }, data.success ? 3500 : 4000);
    }

    async function scan(payload) {
        payload = String(payload || '').trim();
        if (!payload || busy) return;
        const now = Date.now();
        if (payload === lastPayload && (now - lastAt) < cooldownMs) return;
        lastPayload = payload;
        lastAt = now;
        busy = true;

        if (scanAbort) scanAbort.abort();
        scanAbort = new AbortController();

        try {
            const fd = new FormData();
            fd.append('qr_payload', payload);
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: fd,
                credentials: 'same-origin',
                signal: scanAbort.signal,
            });

            if (res.status === 401) {
                window.location.href = loginUrl;
                return;
            }

            const data = await res.json();
            showResult(data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            showResult({ success: false, code: 'error', title: 'CONNECTION PROBLEM', message: 'Attendance could not be submitted.' });
        } finally {
            busy = false;
        }
    }

    async function heartbeat() {
        const statusEl = document.getElementById('station-status');
        const statusText = document.getElementById('station-status-text');
        try {
            const res = await fetch(heartbeatUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (res.status === 401) {
                window.location.href = loginUrl;
                return;
            }
            statusEl.classList.remove('is-offline');
            statusText.textContent = 'Connected';
        } catch (e) {
            statusEl.classList.add('is-offline');
            statusText.textContent = 'Offline';
        }
    }

    setInterval(heartbeat, 30000);
    heartbeat();

    const camera = ScanningUI.createCameraManager({
        readerId: 'station-qr-reader',
        onScan: scan,
        fps: 10,
        qrbox: { width: 280, height: 280 },
        onStatusChange(status, message) {
            ScanningUI.updateStatusEl(cameraStatusEl, status, message ? '● ' + message : null);
            const hints = {
                initializing: 'Starting camera…',
                ready: 'Position the QR code inside the frame',
                unavailable: 'Connect or enable a camera.',
                denied: 'Allow camera permission in your browser settings.',
                failed: 'Unable to start camera.',
            };
            if (hintEl) hintEl.textContent = hints[status] || hints.ready;
            if (['unavailable', 'denied', 'failed'].includes(status)) {
                retryBtn.hidden = false;
                if (status === 'unavailable') {
                    showResult({ success: false, code: 'error', title: 'NO CAMERA DETECTED', message: 'Connect or enable a camera.' });
                } else if (status === 'denied') {
                    showResult({ success: false, code: 'error', title: 'CAMERA ACCESS BLOCKED', message: 'Allow camera permission in your browser settings.' });
                }
            } else {
                retryBtn.hidden = status === 'ready';
            }
        },
    });

    camera.init();
    camera.registerCleanup();

    retryBtn.addEventListener('click', () => camera.restart());
})();
</script>
@endpush
