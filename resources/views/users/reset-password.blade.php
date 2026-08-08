@extends('layouts.app')
@section('title', 'Reset password')
@section('content')
<div class="page-header"><div><h1>Reset password</h1><p class="page-header__meta">{{ $user->displayName() }} ({{ $user->email }})</p></div><a href="{{ route('users.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card" style="max-width:480px;"><div class="card__body"><form method="post" action="{{ route('users.reset-password', $user) }}">@csrf
<div class="form-group"><label class="form-label" for="password">New password</label><input type="password" name="password" id="password" class="form-control" required></div>
<div class="form-group"><label class="form-label" for="password_confirmation">Confirm password</label><input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required></div>
<button type="submit" class="btn btn--primary mt-2">Update password</button>
</form></div></div>
@endsection
