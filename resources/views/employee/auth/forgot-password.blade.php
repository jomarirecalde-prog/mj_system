@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="guest-card__title">Reset password</h1>
    <p class="guest-card__sub">Enter your Employee ID or registered email</p>

    <form method="post" action="{{ route('employee.password.email') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1.25rem;">
            <label class="form-label" for="identifier">Employee ID or Email</label>
            <input type="text" name="identifier" id="identifier" class="form-control" value="{{ old('identifier') }}" required autofocus>
            @error('identifier')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn--primary btn--block">Send reset link</button>
    </form>
    <p class="text-muted" style="margin-top:1rem;text-align:center;font-size:.9rem;">
        <a href="{{ route('employee.login') }}">Back to sign in</a>
    </p>
@endsection
