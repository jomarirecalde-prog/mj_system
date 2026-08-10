@extends('layouts.guest')

@section('title', 'Sign in')
@section('body_class', 'login-page')
@section('guest_wrap_class', 'guest-wrap--login')
@section('guest_card_class', 'guest-card--login')

@section('content')
    <div class="login">
        <header class="login__header">
            <div class="login__logo" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm10-2h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm0 4h2v2h-2v-2z"/>
                </svg>
            </div>
            <h1 class="login__title">{{ setting('organization_name', 'QR Inventory System') }}</h1>
            <p class="login__subtitle">Sign in to manage inventory and scan QR codes</p>
        </header>

        <form method="post" action="{{ route('login') }}" class="login__form" id="login-form">
            @csrf

            <div class="login__field">
                <label class="login__label" for="email">Email</label>
                <div class="login__control @error('email') login__control--invalid @enderror">
                    <span class="login__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 7l9 6 9-6"/>
                        </svg>
                    </span>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="login__input"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                        autocomplete="username"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @else aria-invalid="false" @enderror
                    >
                </div>
                @error('email')
                    <p class="login__error" id="email-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="login__field">
                <label class="login__label" for="password">Password</label>
                <div class="login__control @error('password') login__control--invalid @enderror">
                    <span class="login__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="11" width="16" height="10" rx="2"/>
                            <path d="M8 11V8a4 4 0 118 0v3"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="login__input"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @else aria-invalid="false" @enderror
                    >
                    <button
                        type="button"
                        class="login__toggle"
                        id="toggle-password"
                        aria-label="Show password"
                        aria-controls="password"
                        aria-pressed="false"
                    >
                        <svg class="login__toggle-icon login__toggle-icon--show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="login__toggle-icon login__toggle-icon--hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                            <path d="M17.94 17.94A10.94 10.94 0 0112 19c-7 0-11-7-11-7a21.77 21.77 0 015.06-5.94"/>
                            <path d="M9.9 4.24A10.94 10.94 0 0112 4c7 0 11 7 11 7a21.8 21.8 0 01-2.16 3.19"/>
                            <path d="M14.12 14.12a3 3 0 01-4.24-4.24"/>
                            <path d="M1 1l22 22"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="login__error" id="password-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="login__meta">
                <label class="login__remember" for="remember">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    <span>Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="login__forgot" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="login__submit" id="login-submit">
                <span class="login__submit-idle">Sign in</span>
                <span class="login__submit-loading" aria-hidden="true">
                    <span class="login__spinner" aria-hidden="true"></span>
                    Signing in...
                </span>
            </button>
        </form>

        <p class="login__footer">
            © {{ date('Y') }} {{ setting('organization_name', 'QR Inventory System') }}
        </p>
    </div>
@endsection

@push('styles')
<style>
    .login-page .guest-wrap--login {
        background: #f8fafc;
        padding: 1.5rem 1rem;
        overflow-x: hidden;
    }

    .login-page .guest-card--login {
        max-width: 420px;
        width: 100%;
        padding: 2rem 1.75rem 1.5rem;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(17, 24, 39, 0.04);
    }

    /* Keep flash messages; hide duplicate validation list from layout alerts */
    .login-page .guest-card--login > .alert {
        margin-bottom: 1rem;
    }

    .login-page .guest-card--login > .alert.alert--error:has(ul) {
        display: none;
    }

    .login {
        color: #111827;
    }

    .login__header {
        text-align: center;
        margin-bottom: 1.75rem;
    }

    .login__logo {
        width: 40px;
        height: 40px;
        margin: 0 auto 0.75rem;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #2563eb;
        display: grid;
        place-items: center;
    }

    .login__title {
        margin: 0 0 0.35rem;
        font-family: var(--font-display);
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.3;
        color: #111827;
        text-align: center;
    }

    .login__subtitle {
        margin: 0;
        font-size: 0.875rem;
        line-height: 1.45;
        color: #6b7280;
    }

    .login__form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .login__field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }

    .login__label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #111827;
    }

    .login__control {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 48px;
        padding: 0 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .login__control:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .login__control--invalid {
        border-color: #dc2626;
    }

    .login__control--invalid:focus-within {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .login__icon {
        display: grid;
        place-items: center;
        color: #9ca3af;
        flex-shrink: 0;
    }

    .login__icon svg {
        width: 18px;
        height: 18px;
    }

    .login__input {
        width: 100%;
        min-width: 0;
        min-height: 46px;
        border: 0;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: 0.9375rem;
        color: #111827;
        padding: 0;
    }

    .login__input::placeholder {
        color: #9ca3af;
    }

    .login__toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin-right: -0.25rem;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        flex-shrink: 0;
    }

    .login__toggle:hover,
    .login__toggle:focus-visible {
        color: #2563eb;
        background: #eff6ff;
        outline: none;
    }

    .login__toggle:focus-visible {
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
    }

    .login__toggle-icon {
        width: 18px;
        height: 18px;
    }

    .login__error {
        margin: 0;
        font-size: 0.8125rem;
        color: #dc2626;
    }

    .login__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: -0.15rem;
    }

    .login__remember {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #111827;
        cursor: pointer;
        user-select: none;
    }

    .login__remember input {
        width: 16px;
        height: 16px;
        margin: 0;
        accent-color: #2563eb;
        cursor: pointer;
    }

    .login__forgot {
        font-size: 0.875rem;
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
    }

    .login__forgot:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    .login__submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 48px;
        margin-top: 0.25rem;
        padding: 0.75rem 1rem;
        border: 1px solid #2563eb;
        border-radius: 10px;
        background: #2563eb;
        color: #ffffff;
        font-family: inherit;
        font-size: 0.9375rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .login__submit:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .login__submit:active {
        background: #1e40af;
        border-color: #1e40af;
    }

    .login__submit:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
    }

    .login__submit.is-loading,
    .login__submit:disabled {
        opacity: 0.85;
        cursor: wait;
        pointer-events: none;
    }

    .login__submit-idle {
        display: inline-flex;
    }

    .login__submit-loading {
        display: none;
        align-items: center;
        gap: 0.5rem;
    }

    .login__submit.is-loading .login__submit-idle {
        display: none;
    }

    .login__submit.is-loading .login__submit-loading {
        display: inline-flex;
    }

    .login__spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: login-spin 0.7s linear infinite;
    }

    @keyframes login-spin {
        to { transform: rotate(360deg); }
    }

    .login__footer {
        margin: 1.5rem 0 0;
        text-align: center;
        font-size: 0.75rem;
        color: #9ca3af;
    }

    @media (max-width: 480px) {
        .login-page .guest-wrap--login {
            padding: 1rem 0.75rem;
            align-items: flex-start;
        }

        .login-page .guest-card--login {
            padding: 1.5rem 1.15rem 1.25rem;
            border-radius: 10px;
        }

        .login__title {
            font-size: 1.125rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login__spinner {
            animation: none;
            opacity: 0.9;
        }

        .login__control,
        .login__submit {
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
        var showIcon = toggleBtn.querySelector('.login__toggle-icon--show');
        var hideIcon = toggleBtn.querySelector('.login__toggle-icon--hide');

        toggleBtn.addEventListener('click', function () {
            var revealing = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', revealing ? 'text' : 'password');
            toggleBtn.setAttribute('aria-pressed', revealing ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', revealing ? 'Hide password' : 'Show password');

            if (showIcon && hideIcon) {
                showIcon.hidden = revealing;
                hideIcon.hidden = !revealing;
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

            var loading = submitBtn.querySelector('.login__submit-loading');
            if (loading) {
                loading.removeAttribute('aria-hidden');
            }
        });
    }
})();
</script>
@endpush
