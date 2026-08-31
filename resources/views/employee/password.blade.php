@extends('layouts.employee')

@section('title', 'Change Password')

@section('content')
<div class="page-header">
    <div>
        <h1>Change Password</h1>
        <p class="page-header__meta">Use a strong password with at least 8 characters to keep your account secure.</p>
    </div>
</div>

<div class="card emp-form-card">
    <div class="card__header">
        <h2 class="card__title">Update your password</h2>
    </div>
    <div class="card__body">
        <form method="post" action="{{ route('employee.password.change') }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label" for="current_password">Current password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required autocomplete="current-password">
                @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="password">New password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="8" autocomplete="new-password">
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn--primary">Update password</button>
        </form>
    </div>
</div>
@endsection
