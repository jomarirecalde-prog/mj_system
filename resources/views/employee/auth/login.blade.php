@extends('layouts.guest')

@section('title', 'Employee Sign in')

@section('content')
    <h1 class="guest-card__title">Employee Portal</h1>
    <p class="guest-card__sub">Sign in with your Employee ID or email</p>

    <form method="post" action="{{ route('employee.login') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" for="identifier">Employee ID or Email</label>
            <input type="text" name="identifier" id="identifier" class="form-control" value="{{ old('identifier') }}" required autofocus autocomplete="username">
            @error('identifier')<p class="form-error">{{ $message }}</p>@enderror
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
    <p class="text-muted" style="margin-top:1rem;text-align:center;font-size:.9rem;">
        <a href="{{ route('employee.password.request') }}">Forgot password?</a>
    </p>
    <p class="text-muted" style="margin-top:.5rem;text-align:center;font-size:.9rem;">
        Staff / Admin? <a href="{{ route('login') }}">System login</a>
    </p>
@endsection
