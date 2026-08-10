@extends('layouts.guest')

@section('title', 'Sign in')
@section('body_class', 'login-page')
@section('guest_wrap_class', 'guest-wrap--login')
@section('guest_card_class', 'guest-card--login')

@section('content')
    <div class="login-shell">
        <aside class="login-brand" aria-label="About this system">
            <div class="login-brand__badge" aria-hidden="true">
                <svg class="login-brand__qr" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M6 6h14v14H6V6zm4 4v6h6v-6h-6zm18-4h14v14H28V6zm4 4v6h6v-6h-6zM6 28h14v14H6V28zm4 4v6h6v-6h-6zm22-4h4v4h-4v-4zm8 0h4v4h-4v-4zm-4 4h4v4h-4v-4zm4 4h4v4h-4v-4zm-8 0h4v4h-4v-4zm0 4h4v4h-4v-4zm8 0h4v4h-4v-4z"/>
                </svg>
            </div>

            <p class="login-brand__eyebrow">Inventory Management</p>
            <h1 class="login-brand__title">{{ setting('organization_name', 'QR Inventory System') }}</h1>
            <p class="login-brand__desc">
                A secure platform to manage inventory, track stock levels, and scan QR codes across your organization.
            </p>

            <ul class="login-features">
                <li class="login-features__item">
                    <span class="login-features__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Inventory Management</strong>
                        <span>Organize items, stock, and locations in one place.</span>
                    </div>
                </li>
                <li class="login-features__item">
                    <span class="login-features__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>QR Code Scanning</strong>
                        <span>Identify and process assets quickly with QR codes.</span>
                    </div>
                </li>
                <li class="login-features__item">
                    <span class="login-features__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18"/><path d="M7 14l4-4 4 3 5-6"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Real-time Monitoring</strong>
                        <span>Stay informed with up-to-date inventory activity.</span>
                    </div>
                </li>
            </ul>
        </aside>

        <div class="login-panel">
            <div class="login-panel__header">
                <h2 class="login-panel__title">Sign in</h2>
                <p class="login-panel__sub">Enter your credentials to continue</p>
            </div>

            @if ($errors->any())
                <div class="login-alert" role="alert">
                    <span class="login-alert__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v5"/>
                            <circle cx="12" cy="16" r="0.8" fill="currentColor" stroke="none"/>
                        </svg>
                    </span>
                    <div class="login-alert__body">
                        <strong>Unable to sign in</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="post" action="{{ route('login') }}" class="login-form" id="login-form">
                @csrf

                <div class="form-group login-field">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="login-input @error('email') login-input--invalid @enderror">
                        <span class="login-input__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 6h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                                <path d="M22 8l-10 7L2 8"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control login-input__control"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            required
                            autofocus
                            autocomplete="username"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @else aria-invalid="false" @enderror
                        >
                    </div>
                    @error('email')
                        <p class="form-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group login-field">
                    <div class="login-field__row">
                        <label class="form-label" for="password">Password</label>
                        @if (Route::has('password.request'))
                            <a class="login-forgot" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>
                    <div class="login-input @error('password') login-input--invalid @enderror">
                        <span class="login-input__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2"/>
                                <path d="M8 11V8a4 4 0 118 0v3"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control login-input__control"
                            required
                            autocomplete="current-password"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @else aria-invalid="false" @enderror
                        >
                        <button
                            type="button"
                            class="login-toggle"
                            id="toggle-password"
                            aria-label="Show password"
                            aria-controls="password"
                            aria-pressed="false"
                        >
                            <svg class="login-toggle__icon login-toggle__icon--show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="login-toggle__icon login-toggle__icon--hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.77 21.77 0 015.06-5.94"/>
                                <path d="M9.9 4.24A10.94 10.94 0 0112 4c7 0 11 7 11 7a21.8 21.8 0 01-2.16 3.19"/>
                                <path d="M14.12 14.12a3 3 0 01-4.24-4.24"/>
                                <path d="M1 1l22 22"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="form-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="login-options">
                    <label class="login-remember" for="remember">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            value="1"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span class="login-remember__box" aria-hidden="true"></span>
                        <span class="login-remember__text">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn btn--primary btn--block login-submit" id="login-submit">
                    <span class="login-submit__idle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
                            <path d="M10 17l5-5-5-5"/>
                            <path d="M15 12H3"/>
                        </svg>
                        Sign in
                    </span>
                    <span class="login-submit__loading" aria-hidden="true">
                        <span class="login-spinner" aria-hidden="true"></span>
                        Signing in...
                    </span>
                </button>
            </form>

            <p class="login-security">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l8 3v6c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V6l8-3z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
                Your account information is securely protected.
            </p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Login page — scoped overrides for guest layout */
    .login-page .guest-wrap--login {
        background:
            radial-gradient(ellipse 80% 60% at 10% 20%, rgba(37, 99, 235, 0.18), transparent 55%),
            radial-gradient(ellipse 70% 50% at 90% 80%, rgba(14, 165, 233, 0.12), transparent 50%),
            linear-gradient(160deg, #0f172a 0%, #1e3a5f 48%, #1e40af 100%);
        padding: 1.25rem;
        overflow-x: hidden;
    }

    .login-page .guest-card--login {
        max-width: 960px;
        padding: 0;
        overflow: hidden;
        border-radius: 14px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .login-page .guest-card--login > .alert {
        margin: 1.25rem 1.25rem 0;
    }

    /* Hide duplicate validation list from layout alerts; login panel shows a clearer alert */
    .login-page .guest-card--login > .alert.alert--error:has(ul) {
        display: none;
    }

    .login-shell {
        display: grid;
        grid-template-columns: 1.05fr 1fr;
        min-height: 560px;
    }

    .login-brand {
        background: linear-gradient(165deg, #1e3a8a 0%, #1d4ed8 55%, #2563eb 100%);
        color: #f8fafc;
        padding: 2.25rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
    }

    .login-brand::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.06) 0%, transparent 40%),
            radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.12), transparent 35%);
        pointer-events: none;
    }

    .login-brand > * {
        position: relative;
        z-index: 1;
    }

    .login-brand__badge {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        display: grid;
        place-items: center;
        margin-bottom: 1.25rem;
    }

    .login-brand__qr {
        width: 34px;
        height: 34px;
        color: #fff;
    }

    .login-brand__eyebrow {
        margin: 0 0 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(226, 232, 240, 0.85);
    }

    .login-brand__title {
        font-family: var(--font-display);
        font-size: clamp(1.45rem, 2.2vw, 1.85rem);
        font-weight: 700;
        line-height: 1.2;
        margin: 0 0 0.75rem;
        color: #fff;
        text-align: left;
    }

    .login-brand__desc {
        margin: 0 0 1.75rem;
        font-size: 0.95rem;
        line-height: 1.55;
        color: rgba(241, 245, 249, 0.9);
        max-width: 34ch;
    }

    .login-features {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .login-features__item {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .login-features__icon {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.12);
        display: grid;
        place-items: center;
    }

    .login-features__icon svg {
        width: 18px;
        height: 18px;
    }

    .login-features__item strong {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.1rem;
    }

    .login-features__item span {
        display: block;
        font-size: 0.8rem;
        color: rgba(226, 232, 240, 0.82);
        line-height: 1.4;
    }

    .login-panel {
        background: #fff;
        padding: 2.25rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-panel__header {
        margin-bottom: 1.35rem;
    }

    .login-panel__title {
        margin: 0 0 0.3rem;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ink);
    }

    .login-panel__sub {
        margin: 0;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .login-alert {
        display: flex;
        gap: 0.7rem;
        align-items: flex-start;
        padding: 0.85rem 0.95rem;
        margin-bottom: 1.15rem;
        border-radius: 12px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .login-alert__icon {
        flex-shrink: 0;
        width: 22px;
        height: 22px;
        margin-top: 1px;
    }

    .login-alert__icon svg {
        width: 22px;
        height: 22px;
    }

    .login-alert__body {
        min-width: 0;
    }

    .login-alert__body strong {
        display: block;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .login-alert__body ul {
        margin: 0;
        padding-left: 1.1rem;
        font-size: 0.85rem;
    }

    .login-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .login-field {
        margin: 0;
    }

    .login-field__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.35rem;
    }

    .login-field__row .form-label {
        margin: 0;
    }

    .login-forgot {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1d4ed8;
        white-space: nowrap;
    }

    .login-forgot:hover {
        color: #1e40af;
    }

    .login-input {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #f8fafc;
        padding: 0 0.75rem;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }

    .login-input:focus-within {
        border-color: #2563eb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
    }

    .login-input--invalid {
        border-color: #f87171;
        background: #fffafa;
    }

    .login-input--invalid:focus-within {
        box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.2);
    }

    .login-input__icon {
        display: grid;
        place-items: center;
        color: #64748b;
        flex-shrink: 0;
    }

    .login-input__icon svg {
        width: 18px;
        height: 18px;
    }

    .login-input__control {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        outline: none !important;
        padding: 0.7rem 0;
        min-height: 44px;
        border-radius: 0;
    }

    .login-input__control:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .login-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        margin-right: -0.35rem;
        border: 0;
        background: transparent;
        color: #64748b;
        border-radius: 10px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .login-toggle:hover,
    .login-toggle:focus-visible {
        color: #1d4ed8;
        background: rgba(37, 99, 235, 0.08);
        outline: none;
    }

    .login-toggle__icon {
        width: 20px;
        height: 20px;
    }

    .login-options {
        margin-top: -0.15rem;
    }

    .login-remember {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        cursor: pointer;
        user-select: none;
        font-size: 0.9rem;
        color: var(--ink);
        position: relative;
    }

    .login-remember input {
        position: absolute;
        opacity: 0;
        width: 1px;
        height: 1px;
        margin: 0;
    }

    .login-remember__box {
        width: 18px;
        height: 18px;
        border-radius: 5px;
        border: 1.5px solid #94a3b8;
        background: #fff;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        transition: background 0.15s, border-color 0.15s;
    }

    .login-remember__box::after {
        content: "";
        width: 9px;
        height: 5px;
        border-left: 2px solid #fff;
        border-bottom: 2px solid #fff;
        transform: rotate(-45deg) translateY(-1px);
        opacity: 0;
    }

    .login-remember input:checked + .login-remember__box {
        background: #2563eb;
        border-color: #2563eb;
    }

    .login-remember input:checked + .login-remember__box::after {
        opacity: 1;
    }

    .login-remember input:focus-visible + .login-remember__box {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
    }

    .login-submit {
        margin-top: 0.25rem;
        min-height: 46px;
        border-radius: 12px;
        background: #2563eb;
        border-color: #2563eb;
        font-size: 0.95rem;
    }

    .login-submit:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .login-submit:active {
        background: #1e40af;
        border-color: #1e40af;
    }

    .login-submit:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
    }

    .login-submit.is-loading,
    .login-submit:disabled {
        opacity: 0.85;
        pointer-events: none;
        cursor: wait;
    }

    .login-submit__idle,
    .login-submit__loading {
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }

    .login-submit__idle {
        display: inline-flex;
    }

    .login-submit__loading {
        display: none;
    }

    .login-submit.is-loading .login-submit__idle {
        display: none;
    }

    .login-submit.is-loading .login-submit__loading {
        display: inline-flex;
    }

    .login-submit__idle svg {
        width: 18px;
        height: 18px;
    }

    .login-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #fff;
        border-radius: 50%;
        animation: login-spin 0.7s linear infinite;
    }

    @keyframes login-spin {
        to { transform: rotate(360deg); }
    }

    .login-security {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        margin: 1.15rem 0 0;
        font-size: 0.78rem;
        color: var(--muted);
        text-align: center;
    }

    .login-security svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        color: #64748b;
    }

    @media (max-width: 900px) {
        .login-shell {
            grid-template-columns: 1fr;
            min-height: 0;
        }

        .login-brand {
            padding: 1.75rem 1.5rem 1.5rem;
        }

        .login-brand__desc {
            max-width: none;
            margin-bottom: 1.25rem;
        }

        .login-features {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .login-panel {
            padding: 1.75rem 1.5rem 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .login-page .guest-wrap--login {
            padding: 0.75rem;
            align-items: flex-start;
        }

        .login-page .guest-card--login {
            border-radius: 12px;
        }

        .login-brand,
        .login-panel {
            padding: 1.35rem 1.15rem;
        }

        .login-brand__title {
            font-size: 1.35rem;
        }

        .login-field__row {
            flex-wrap: wrap;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-spinner {
            animation: none;
            border-top-color: #fff;
            opacity: 0.9;
        }

        .login-input,
        .login-submit,
        .login-remember__box {
            transition: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('login-form');
    var submitBtn = document.getElementById('login-submit');
    var toggleBtn = document.getElementById('toggle-password');
    var passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        var showIcon = toggleBtn.querySelector('.login-toggle__icon--show');
        var hideIcon = toggleBtn.querySelector('.login-toggle__icon--hide');

        toggleBtn.addEventListener('click', function () {
            var isHidden = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
            toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');

            if (showIcon && hideIcon) {
                showIcon.hidden = isHidden;
                hideIcon.hidden = !isHidden;
            }
        });
    }

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            if (submitBtn.classList.contains('is-loading')) {
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
            submitBtn.setAttribute('aria-busy', 'true');

            var loading = submitBtn.querySelector('.login-submit__loading');
            if (loading) loading.removeAttribute('aria-hidden');
        });
    }
})();
</script>
@endpush
