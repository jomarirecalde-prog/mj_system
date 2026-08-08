@extends('layouts.app')

@section('title', 'QR Attendance Scanner')

@push('styles')
<style>
.att-scan { max-width: 720px; margin: 0 auto; }
.att-feedback {
    display: none; margin-top: 1rem; padding: 1.25rem 1.5rem; border-radius: 12px;
    text-align: center; border: 2px solid transparent;
}
.att-feedback.is-show { display: block; animation: attPop .25s ease; }
.att-feedback.is-success { background: #e8f7ee; border-color: #1f9d55; color: #14532d; }
.att-feedback.is-error { background: #fff4e5; border-color: #d97706; color: #7c2d12; }
.att-feedback.is-warn { background: #fef2f2; border-color: #dc2626; color: #7f1d1d; }
.att-feedback__title { font-size: 1.35rem; font-weight: 700; margin: 0 0 .35rem; letter-spacing: .02em; }
.att-feedback__name { font-size: 1.15rem; font-weight: 600; margin: .5rem 0 .15rem; }
.att-feedback__meta { opacity: .85; font-size: .95rem; }
.att-feedback__time { font-size: 1.75rem; font-weight: 700; margin-top: .5rem; font-variant-numeric: tabular-nums; }
#att-qr-reader video { border-radius: 10px; width: 100%; }
@keyframes attPop { from { transform: scale(.97); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
@endpush

@section('content')
<div class="att-scan">
    <div class="page-header">
        <div>
            <h1>QR Attendance Scanner</h1>
            <p class="page-header__meta">Scan employee QR → automatic Time In / Time Out · Asia/Manila</p>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__body">
            <div id="att-qr-reader" aria-label="Attendance camera scanner"></div>
            <p class="form-hint mt-1">Camera, tablet, webcam, or USB scanner supported. Manual entry works too.</p>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__header"><h2 class="card__title">Manual / USB scanner entry</h2></div>
        <div class="card__body">
            <form id="att-manual-form">
                <div class="form-group">
                    <label class="form-label" for="att_qr_payload">Employee QR code</label>
                    <input type="text" id="att_qr_payload" class="form-control" placeholder="EMP-2026-000001" autocomplete="off" autofocus>
                </div>
                <button type="submit" class="btn btn--primary btn--block mt-1" id="att-punch-btn">Record attendance</button>
            </form>
        </div>
    </div>

    <div id="att-feedback" class="att-feedback" role="status" aria-live="polite">
        <div class="att-feedback__title" id="att-fb-title"></div>
        <div class="att-feedback__name" id="att-fb-name"></div>
        <div class="att-feedback__meta" id="att-fb-meta"></div>
        <div class="att-feedback__time" id="att-fb-time"></div>
        <div class="att-feedback__meta mt-1" id="att-fb-msg"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const punchUrl = @json(route('attendance.scanner.punch'));
    const input = document.getElementById('att_qr_payload');
    const feedback = document.getElementById('att-feedback');
    let lastPayload = '';
    let lastAt = 0;
    let busy = false;

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showFeedback(data) {
        feedback.classList.remove('is-success', 'is-error', 'is-warn', 'is-show');
        const code = data.code || '';
        if (data.success) feedback.classList.add('is-success');
        else if (['invalid', 'inactive'].includes(code)) feedback.classList.add('is-warn');
        else feedback.classList.add('is-error');

        document.getElementById('att-fb-title').textContent = (data.success ? '✓ ' : '⚠ ') + (data.title || 'Result');
        const emp = data.employee || {};
        document.getElementById('att-fb-name').textContent = emp.name || '';
        document.getElementById('att-fb-meta').innerHTML = emp.employee_id
            ? ('Employee ID: ' + esc(emp.employee_id) + (emp.department ? '<br>Department: ' + esc(emp.department) : ''))
            : '';
        document.getElementById('att-fb-time').textContent = data.time || (data.record ? (data.record.time_in || data.record.time_out || '') : '');
        document.getElementById('att-fb-msg').textContent = data.message || '';
        feedback.classList.add('is-show');
        feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function punch(payload) {
        payload = String(payload || '').trim();
        if (!payload || busy) return;
        const now = Date.now();
        if (payload === lastPayload && (now - lastAt) < 2500) return;
        lastPayload = payload;
        lastAt = now;
        busy = true;
        const btn = document.getElementById('att-punch-btn');
        if (window.App && App.setLoading) App.setLoading(btn, true);
        try {
            const fd = new FormData();
            fd.append('qr_payload', payload);
            const res = await fetch(punchUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                body: fd
            });
            const data = await res.json();
            showFeedback(data);
            input.value = '';
            input.focus();
        } catch (e) {
            showFeedback({ success: false, code: 'error', title: 'SCAN FAILED', message: 'Unable to reach the server.' });
        } finally {
            busy = false;
            if (window.App && App.setLoading) App.setLoading(btn, false);
        }
    }

    document.getElementById('att-manual-form').addEventListener('submit', function (e) {
        e.preventDefault();
        punch(input.value);
    });

    // USB scanner often ends with Enter
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            punch(input.value);
        }
    });

    if (window.Html5Qrcode) {
        const scanner = new Html5Qrcode('att-qr-reader');
        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || !cameras.length) return;
            const camId = cameras[cameras.length - 1].id;
            scanner.start(camId, { fps: 8, qrbox: { width: 250, height: 250 } }, (decoded) => punch(decoded), () => {});
        }).catch(() => {});
    }
})();
</script>
@endpush
