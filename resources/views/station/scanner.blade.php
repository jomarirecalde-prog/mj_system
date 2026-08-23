@extends('layouts.station')

@section('title', $station->station_name)

@push('styles')
<style>
.station-scanner {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    background: #0f172a;
    color: #f8fafc;
}
.station-scanner__header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1rem 1.25rem; background: rgba(15, 23, 42, 0.95);
    border-bottom: 1px solid rgba(148, 163, 184, 0.15);
    flex-wrap: wrap;
}
.station-scanner__brand { min-width: 0; }
.station-scanner__name { font-family: var(--font-display); font-size: 1.125rem; font-weight: 600; margin: 0; }
.station-scanner__meta { font-size: 0.8125rem; color: #94a3b8; margin: 0.15rem 0 0; }
.station-scanner__clock { text-align: center; flex: 1; min-width: 180px; }
.station-scanner__date { font-size: 0.875rem; color: #94a3b8; }
.station-scanner__time { font-size: 1.75rem; font-weight: 700; font-variant-numeric: tabular-nums; letter-spacing: 0.02em; }
.station-scanner__status {
    display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8125rem;
    padding: 0.35rem 0.65rem; border-radius: 999px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7;
}
.station-scanner__status.is-offline { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
.station-scanner__status-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
.station-scanner__actions { display: flex; align-items: center; gap: 0.75rem; }
.station-scanner__logout {
    min-height: 40px; padding: 0 1rem; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.35);
    background: transparent; color: #e2e8f0; font: inherit; font-size: 0.875rem; font-weight: 600; cursor: pointer;
}
.station-scanner__main {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 1.5rem 1rem 2rem; gap: 1.25rem;
}
.station-scanner__hint { font-size: 1.05rem; color: #cbd5e1; margin: 0; text-align: center; }
.station-scanner__viewport {
    width: min(100%, 520px); aspect-ratio: 1; border-radius: 20px; overflow: hidden;
    border: 3px solid rgba(45, 212, 191, 0.45); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    background: #020617; position: relative;
}
.station-scanner__viewport video { width: 100%; height: 100%; object-fit: cover; }
.station-scanner__overlay {
    position: absolute; inset: 0; pointer-events: none;
    box-shadow: inset 0 0 0 9999px rgba(2, 6, 23, 0.35);
}
.station-scanner__frame {
    position: absolute; inset: 12%; border: 2px solid rgba(45, 212, 191, 0.85);
    border-radius: 16px; box-shadow: 0 0 0 9999px rgba(2, 6, 23, 0.35);
}
.station-scanner__result {
    width: min(100%, 520px); min-height: 140px; border-radius: 16px; padding: 1.25rem 1.5rem;
    text-align: center; display: none; border: 2px solid transparent;
}
.station-scanner__result.is-show { display: block; animation: stationPop .28s ease; }
.station-scanner__result.is-success { background: #ecfdf5; color: #14532d; border-color: #10b981; }
.station-scanner__result.is-error { background: #fff7ed; color: #7c2d12; border-color: #f59e0b; }
.station-scanner__result.is-warn { background: #fef2f2; color: #7f1d1d; border-color: #ef4444; }
.station-scanner__result-title { font-size: 1.35rem; font-weight: 700; margin: 0 0 0.35rem; letter-spacing: 0.03em; }
.station-scanner__result-name { font-size: 1.2rem; font-weight: 600; margin: 0.35rem 0; }
.station-scanner__result-meta { font-size: 0.95rem; opacity: 0.9; }
.station-scanner__result-time { font-size: 2rem; font-weight: 700; margin: 0.5rem 0; font-variant-numeric: tabular-nums; }
@keyframes stationPop { from { transform: scale(.97); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@media (max-width: 640px) {
    .station-scanner__header { flex-direction: column; align-items: stretch; }
    .station-scanner__clock { order: -1; }
    .station-scanner__time { font-size: 1.5rem; }
}
</style>
@endpush

@section('content')
<div class="station-scanner">
    <header class="station-scanner__header">
        <div class="station-scanner__brand">
            <h1 class="station-scanner__name">{{ $station->station_name }}</h1>
            <p class="station-scanner__meta">{{ $station->station_code }} · {{ $station->location }}</p>
        </div>
        <div class="station-scanner__clock">
            <div class="station-scanner__date" id="station-date"></div>
            <div class="station-scanner__time" id="station-time"></div>
        </div>
        <div class="station-scanner__actions">
            <span class="station-scanner__status" id="station-status">
                <span class="station-scanner__status-dot"></span>
                <span id="station-status-text">Connected</span>
            </span>
            <form method="post" action="{{ route('station.logout') }}" class="mb-0 logout-form">
                @csrf
                <button type="submit" class="station-scanner__logout">Logout</button>
            </form>
        </div>
    </header>

    <main class="station-scanner__main">
        <p class="station-scanner__hint">Scan your employee QR Code</p>

        <div class="station-scanner__viewport" id="station-qr-reader" aria-label="QR camera scanner">
            <div class="station-scanner__frame"></div>
        </div>

        <div class="station-scanner__result" id="station-result" role="status" aria-live="polite">
            <div class="station-scanner__result-title" id="station-result-title"></div>
            <div class="station-scanner__result-name" id="station-result-name"></div>
            <div class="station-scanner__result-meta" id="station-result-meta"></div>
            <div class="station-scanner__result-time" id="station-result-time"></div>
            <div class="station-scanner__result-meta" id="station-result-msg"></div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const scanUrl = @json(route('station.scan'));
    const heartbeatUrl = @json(route('station.heartbeat'));
    const timezone = @json($station->timezone ?? 'Asia/Manila');
    const stationName = @json($station->station_name);
    const cooldownMs = 2500;
    let busy = false;
    let lastPayload = '';
    let lastAt = 0;
    let resumeTimer = null;

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function updateClock() {
        const now = new Date();
        const dateFmt = new Intl.DateTimeFormat('en-US', { timeZone: timezone, weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        const timeFmt = new Intl.DateTimeFormat('en-US', { timeZone: timezone, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
        document.getElementById('station-date').textContent = dateFmt.format(now);
        document.getElementById('station-time').textContent = timeFmt.format(now);
    }

    setInterval(updateClock, 1000);
    updateClock();

    const resultEl = document.getElementById('station-result');
    const viewport = document.getElementById('station-qr-reader');

    function showResult(data) {
        clearTimeout(resumeTimer);
        resultEl.classList.remove('is-success', 'is-error', 'is-warn', 'is-show');
        const code = data.code || '';
        if (data.success) resultEl.classList.add('is-success');
        else if (['invalid', 'inactive', 'inactive_station'].includes(code)) resultEl.classList.add('is-warn');
        else resultEl.classList.add('is-error');

        document.getElementById('station-result-title').textContent = (data.success ? '✓ ' : '') + (data.title || 'Result');
        const emp = data.employee || {};
        document.getElementById('station-result-name').textContent = emp.name || '';
        document.getElementById('station-result-meta').innerHTML = emp.employee_id
            ? ('Employee ID: ' + esc(emp.employee_id))
            : '';
        document.getElementById('station-result-time').textContent = data.time || '';
        const msgParts = [data.message || ''];
        if (data.station || stationName) msgParts.push(data.station || stationName);
        document.getElementById('station-result-msg').textContent = msgParts.filter(Boolean).join(' · ');
        resultEl.classList.add('is-show');
        viewport.style.opacity = data.success ? '0.35' : '1';

        resumeTimer = setTimeout(() => {
            resultEl.classList.remove('is-show');
            viewport.style.opacity = '1';
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

        try {
            const fd = new FormData();
            fd.append('qr_payload', payload);
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                body: fd,
                credentials: 'same-origin'
            });

            if (res.status === 401) {
                window.location.href = @json(route('login').'#qr-station');
                return;
            }

            const data = await res.json();
            showResult(data);
        } catch (e) {
            showResult({ success: false, code: 'error', title: 'SCAN FAILED', message: 'Unable to reach the server.' });
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
                credentials: 'same-origin'
            });
            if (res.status === 401) {
                window.location.href = @json(route('login').'#qr-station');
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

    if (window.Html5Qrcode) {
        const scanner = new Html5Qrcode('station-qr-reader');
        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || !cameras.length) {
                showResult({ success: false, code: 'error', title: 'NO CAMERA', message: 'No camera detected on this device.' });
                return;
            }
            const camId = cameras[cameras.length - 1].id;
            scanner.start(camId, { fps: 10, qrbox: { width: 280, height: 280 } }, decoded => scan(decoded), () => {});
        }).catch(() => {
            showResult({ success: false, code: 'error', title: 'CAMERA ERROR', message: 'Unable to access the camera.' });
        });
    }
})();
</script>
@endpush
