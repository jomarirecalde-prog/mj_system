@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <h1 class="guest-card__title">{{ setting('organization_name', 'QR Inventory System') }}</h1>
    <p class="guest-card__sub">Sign in to manage inventory and scan QR codes</p>

    <form method="post" action="{{ route('login') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-check" style="margin-bottom:1.25rem;">
            <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn--primary btn--block">Sign in</button>
    </form>
    <p class="text-muted" style="margin-top:1.25rem;text-align:center;font-size:.9rem;">
        Employee? <a href="{{ route('employee.login') }}">Sign in to the Employee Portal</a>
    </p>
@endsection
