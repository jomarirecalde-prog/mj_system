@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <h1 class="guest-card__title">Choose a new password</h1>
    <p class="guest-card__sub">Enter your email and a new password</p>

    <form method="post" action="{{ route('employee.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $email) }}" required>
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label" for="password">New password</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="8">
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group" style="margin-bottom:1.25rem;">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn--primary btn--block">Update password</button>
    </form>
@endsection
