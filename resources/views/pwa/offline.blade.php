@extends('layouts.offline')

@section('title', 'Offline')

@section('content')
<div class="pwa-offline-page">
    <div class="pwa-offline-card">
        <div class="pwa-offline-card__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="36" height="36">
                <path stroke-linecap="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 8v4m0 4h.01"/>
            </svg>
        </div>
        <div class="pwa-offline-card__status" id="connection-status" role="status" data-pwa-status>
            <span class="pwa-offline-card__status-dot"></span>
            <span data-pwa-status-label>No internet connection</span>
        </div>
        <h1>You're currently offline</h1>
        <p class="text-muted">Please check your internet connection and try again.</p>
        <button type="button" class="btn btn--primary" id="offline-retry-btn">Try again</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var retryBtn = document.getElementById('offline-retry-btn');
    retryBtn?.addEventListener('click', function () {
        if (navigator.onLine) {
            window.location.href = '/';
        } else {
            retryBtn.textContent = 'Still offline…';
            setTimeout(function () { retryBtn.textContent = 'Try again'; }, 1500);
        }
    });
})();
</script>
@endpush
