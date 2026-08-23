@extends('layouts.guest')

@section('title', 'QR Attendance Station')
@section('body_class', 'login-page station-login-page')
@section('guest_wrap_class', 'guest-wrap--login')
@section('guest_card_class', 'guest-card--login station-login-card')

@section('content')
    <div class="login station-login">
        <header class="login__header">
            <div class="login__logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="24" height="24">
                    <path stroke-linecap="round" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2M16 4h2a2 2 0 012 2v2M16 20h2a2 2 0 002-2v-2"/>
                    <rect x="7" y="7" width="10" height="10" rx="1.5"/>
                    <path stroke-linecap="round" d="M9 12h6"/>
                </svg>
            </div>
            <h1 class="login__title">QR Attendance Station</h1>
            <p class="login__subtitle">Sign in this device to start scanning employee QR codes.</p>
        </header>

        <form method="post" action="{{ route('station.login.submit') }}" class="login__form" id="station-login-form">
            @csrf

            <div class="login__field">
                <label class="login__label" for="station_code">Station ID</label>
                <div class="login__control @error('station_code') login__control--invalid @enderror">
                    <input
                        type="text"
                        name="station_code"
                        id="station_code"
                        class="login__input"
                        value="{{ old('station_code') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="STATION-001"
                        @error('station_code') aria-invalid="true" @enderror
                    >
                </div>
                @error('station_code')
                    <p class="login__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="login__field">
                <label class="login__label" for="password">Station Password</label>
                <div class="login__control">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="login__input"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="station-pw-toggle" data-target="password" aria-label="Show password">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="login__submit station-login__submit">Sign in to Scanner</button>
        </form>

        <p class="login__footer station-login__footer">
            {{ setting('organization_name', 'QR Inventory') }} · Attendance Station
        </p>
    </div>
@endsection

@push('styles')
<style>
    .station-login-page .guest-wrap--login { background: linear-gradient(160deg, #f0fdfa 0%, #f8fafc 45%, #eef2ff 100%); }
    .station-login-card { max-width: 440px; }
    .station-login .login__logo { color: #0f766e; background: #ecfdf5; border-color: #99f6e4; }
    .station-login__submit { background: #0f766e; border-color: #0f766e; }
    .station-login__submit:hover { background: #115e59; border-color: #115e59; }
    .station-login__footer { font-size: 0.8125rem; }
    .login__control { position: relative; }
    .station-pw-toggle {
        border: 0; background: transparent; color: #64748b; cursor: pointer; padding: 0.25rem;
        display: grid; place-items: center;
    }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.station-pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>
@endpush
