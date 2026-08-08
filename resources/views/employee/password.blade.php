@extends('layouts.employee')

@section('title', 'Change Password')

@section('content')
<div class="page-header">
    <div>
        <h1>Change Password</h1>
        <p class="page-header__meta">Keep your employee account secure</p>
    </div>
</div>

<div class="card" style="max-width:480px;">
    <div class="card__body">
        <form method="post" action="{{ route('employee.password.change') }}">
            @csrf
            @method('PUT')
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="current_password">Current password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="password">New password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="8">
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn--primary">Update password</button>
        </form>
    </div>
</div>
@endsection
