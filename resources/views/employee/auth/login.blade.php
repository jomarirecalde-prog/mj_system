@extends('layouts.guest')

@section('title', 'Employee Sign in')
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
            <h1 class="login__title">Employee Portal</h1>
            <p class="login__subtitle">Sign in with your Employee ID or email</p>
        </header>

        <form method="post" action="{{ route('employee.login.submit') }}" class="login__form" id="employee-login-form">
            @csrf

            <div class="login__field">
                <label class="login__label" for="identifier">Employee ID or Email</label>
                <div class="login__control @error('identifier') login__control--invalid @enderror">
                    <input
                        type="text"
                        name="identifier"
                        id="identifier"
                        class="login__input"
                        value="{{ old('identifier') }}"
                        required
                        autofocus
                        autocomplete="username"
                        @error('identifier') aria-invalid="true" aria-describedby="identifier-error" @enderror
                    >
                </div>
                @error('identifier')
                    <p class="login__error" id="identifier-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="login__field">
                <label class="login__label" for="password">Password</label>
                <div class="login__control">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="login__input"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <div class="login__meta">
                <label class="login__remember" for="remember">
                    <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
                <a class="login__forgot" href="{{ route('employee.password.request') }}">Forgot password?</a>
            </div>

            <button type="submit" class="login__submit">Sign in</button>
        </form>

        <p class="login__footer">
            Staff / Admin? <a href="{{ route('login') }}#login">System login</a>
        </p>
    </div>
@endsection

@push('styles')
<style>
    .login-page .guest-wrap--login {
        background: #f8fafc;
        padding: 1.5rem 1rem;
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
    .login__header { text-align: center; margin-bottom: 1.75rem; }
    .login__logo {
        width: 40px; height: 40px; margin: 0 auto 0.75rem; border-radius: 10px;
        border: 1px solid #e5e7eb; background: #f8fafc; color: #2563eb;
        display: grid; place-items: center;
    }
    .login__title {
        margin: 0 0 0.35rem; font-family: var(--font-display);
        font-size: 1.25rem; font-weight: 600; color: #111827;
    }
    .login__subtitle { margin: 0; font-size: 0.875rem; color: #6b7280; }
    .login__form { display: flex; flex-direction: column; gap: 1rem; }
    .login__field { display: flex; flex-direction: column; gap: 0.375rem; }
    .login__label { font-size: 0.8125rem; font-weight: 600; color: #111827; }
    .login__control {
        display: flex; align-items: center; min-height: 48px; padding: 0 0.75rem;
        border: 1px solid #e5e7eb; border-radius: 10px; background: #fff;
    }
    .login__control:focus-within { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .login__control--invalid { border-color: #dc2626; }
    .login__input {
        width: 100%; min-height: 46px; border: 0; outline: none; background: transparent;
        font-family: inherit; font-size: 0.9375rem; color: #111827;
    }
    .login__error { margin: 0; font-size: 0.8125rem; color: #dc2626; }
    .login__meta { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }
    .login__remember { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; cursor: pointer; }
    .login__forgot { font-size: 0.875rem; font-weight: 500; color: #2563eb; text-decoration: none; }
    .login__submit {
        min-height: 48px; border: 1px solid #2563eb; border-radius: 10px; background: #2563eb;
        color: #fff; font-family: inherit; font-size: 0.9375rem; font-weight: 600; cursor: pointer;
    }
    .login__submit:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .login__footer { margin: 1.5rem 0 0; text-align: center; font-size: 0.875rem; color: #6b7280; }
    .login__footer a { color: #2563eb; text-decoration: none; font-weight: 500; }
</style>
@endpush
