@extends('layouts.app')
@section('title', 'Edit user')
@section('content')
<div class="page-header"><div><h1>Edit {{ $user->displayName() }}</h1></div><a href="{{ route('users.index') }}" class="btn btn--secondary">Back</a></div>
<div class="card"><div class="card__body"><form method="post" action="{{ route('users.update', $user) }}">@csrf @method('PUT')
<div class="form-grid">
<div class="form-group"><label class="form-label" for="employee_id">Employee ID</label><input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ old('employee_id', $user->employee_id) }}"></div>
<div class="form-group"><label class="form-label" for="name">Username <span class="req">*</span></label><input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
<div class="form-group"><label class="form-label" for="full_name">Full name</label><input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name', $user->full_name) }}"></div>
<div class="form-group"><label class="form-label" for="email">Email <span class="req">*</span></label><input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
<div class="form-group"><label class="form-label" for="department">Department</label><input type="text" name="department" id="department" class="form-control" value="{{ old('department', $user->department) }}"></div>
<div class="form-group"><label class="form-label" for="position">Position</label><input type="text" name="position" id="position" class="form-control" value="{{ old('position', $user->position) }}"></div>
<div class="form-group"><label class="form-label" for="role">Role</label><select name="role" id="role" class="form-select">@foreach(['admin','staff','viewer','employee'] as $r)<option value="{{ $r }}" @selected(old('role', $user->role)===$r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
<div class="form-group"><label class="form-label" for="status">Status</label><select name="status" id="status" class="form-select"><option value="active" @selected(old('status', $user->status)==='active')>Active</option><option value="inactive" @selected(old('status', $user->status)==='inactive')>Inactive</option></select></div>
</div>
<button type="submit" class="btn btn--primary mt-2">Save changes</button>
</form></div></div>
@endsection
