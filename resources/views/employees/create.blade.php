@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
<div class="page-header">
    <div>
        <h1>Add employee</h1>
        <p class="page-header__meta">Create an employee record for QR attendance and DTR</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn--secondary">Back</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('employees.store') }}" class="form-grid" id="employee-create-form">
            @csrf

            <div class="form-group">
                <label class="form-label" for="employee_id">Employee ID <span class="req">*</span></label>
                <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ old('employee_id', $suggestedId) }}" required>
                @error('employee_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="full_name">Full name <span class="req">*</span></label>
                <input type="text" name="full_name" id="full_name" class="form-control" value="{{ old('full_name') }}" required placeholder="Juan Dela Cruz">
                @error('full_name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="department">Department <span class="req">*</span></label>
                <input list="department-list" type="text" name="department" id="department" class="form-control" value="{{ old('department') }}" required>
                <datalist id="department-list">
                    @foreach($departments as $d)
                        <option value="{{ $d }}"></option>
                    @endforeach
                </datalist>
                @error('department')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="position">Position</label>
                <input type="text" name="position" id="position" class="form-control" value="{{ old('position') }}" placeholder="Staff / Officer">
                @error('position')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="date_hired">Date hired</label>
                <input type="date" name="date_hired" id="date_hired" class="form-control" value="{{ old('date_hired') }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Employment status</label>
                <select name="status" id="status" class="form-select">
                    <option value="active" @selected(old('status', 'active')==='active')>Active</option>
                    <option value="inactive" @selected(old('status')==='inactive')>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="shift_id">Work schedule / shift</label>
                <select name="shift_id" id="shift_id" class="form-select">
                    <option value="">Use default (8:00 AM – 5:00 PM)</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id')==$shift->id)>
                            {{ $shift->name }} ({{ substr($shift->time_in,0,5) }} – {{ substr($shift->time_out,0,5) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-hint">
                    <input type="checkbox" name="generate_qr" value="1" @checked(old('generate_qr', true))>
                    Generate attendance QR code now
                </label>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-hint">
                    <input type="checkbox" name="allow_login" id="allow_login" value="1" @checked(old('allow_login'))>
                    Allow this employee to sign in to the Employee Portal
                </label>
                <p class="form-hint">Leave unchecked for attendance-only employees (no portal login needed).</p>
            </div>

            <div id="login-fields" style="display:none;grid-column:1/-1;">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="role">System role</label>
                        <select name="role" id="role" class="form-select">
                            @foreach(['employee'=>'Employee (Portal)','viewer'=>'Viewer','staff'=>'Staff','admin'=>'Administrator'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', 'employee')===$value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" minlength="8">
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="8">
                    </div>
                </div>
            </div>

            <div style="grid-column:1/-1;">
                <button type="submit" class="btn btn--primary">Save employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('allow_login');
    const fields = document.getElementById('login-fields');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirm = document.getElementById('password_confirmation');

    function sync() {
        const on = toggle.checked;
        fields.style.display = on ? 'block' : 'none';
        email.required = on;
        password.required = on;
        confirm.required = on;
    }

    toggle.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
