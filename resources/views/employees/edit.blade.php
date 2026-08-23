@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div class="page-header">
    <div>
        <h1>Edit employee</h1>
        <p class="page-header__meta">{{ $employee->employee_id }} · {{ $employee->displayName() }}</p>
    </div>
    <a href="{{ route('employees.show', $employee) }}" class="btn btn--secondary">Back</a>
</div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('employees.update', $employee) }}" class="form-grid">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="employee_id">Employee ID <span class="req">*</span></label>
                <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ old('employee_id', $employee->employee_id) }}" required>
                @error('employee_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            @include('employees.partials._name-fields', ['employee' => $employee])

            <div class="form-group">
                <label class="form-label" for="department">Department <span class="req">*</span></label>
                <input list="department-list" type="text" name="department" id="department" class="form-control" value="{{ old('department', $employee->department) }}" required>
                <datalist id="department-list">
                    @foreach($departments as $d)
                        <option value="{{ $d }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label" for="position">Position</label>
                <input type="text" name="position" id="position" class="form-control" value="{{ old('position', $employee->position) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="date_hired">Date hired</label>
                <input type="date" name="date_hired" id="date_hired" class="form-control" value="{{ old('date_hired', optional($employee->date_hired)->format('Y-m-d')) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Employment status</label>
                <select name="status" id="status" class="form-select">
                    <option value="active" @selected(old('status', $employee->status)==='active')>Active</option>
                    <option value="inactive" @selected(old('status', $employee->status)==='inactive')>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="shift_id">Work schedule / shift</label>
                <select name="shift_id" id="shift_id" class="form-select">
                    <option value="">Keep current schedule</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected(old('shift_id', optional($employee->activeSchedule)->shift_id)==$shift->id)>
                            {{ $shift->name }} ({{ substr($shift->time_in,0,5) }} – {{ substr($shift->time_out,0,5) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email <span class="req">*</span></label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $employee->email) }}" required>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="role">System role</label>
                <select name="role" id="role" class="form-select">
                    @foreach(['employee'=>'Employee (Portal)','viewer'=>'Viewer','staff'=>'Staff','admin'=>'Administrator'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $employee->role)===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">New password (optional)</label>
                <input type="password" name="password" id="password" class="form-control" minlength="8">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="8">
            </div>

            <div style="grid-column:1/-1;">
                <button type="submit" class="btn btn--primary">Save changes</button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn--ghost">Cancel</a>
            </div>
        </form>

        @if($employee->id !== auth()->id())
            <form method="post" action="{{ route('employees.destroy', $employee) }}" class="mt-2" onsubmit="return confirm('Deactivate this employee? Attendance history will be kept.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--ghost">Deactivate employee</button>
            </form>
        @endif
    </div>
</div>
@endsection
