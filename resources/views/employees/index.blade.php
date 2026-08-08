@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="page-header">
    <div>
        <h1>Employees</h1>
        <p class="page-header__meta">Manage employee records for DTR and QR attendance</p>
    </div>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.create') }}" class="btn btn--primary">Add employee</a>
    @endif
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, employee ID, department">
            </div>
            <div class="form-group">
                <label class="form-label">Department</label>
                <select name="department" class="form-select">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($employees->isEmpty())
            <div class="empty-state">
                <p class="empty-state__title">No employees found</p>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('employees.create') }}" class="btn btn--primary mt-1">Add your first employee</a>
                @endif
            </div>
        @else
            <table class="data-table">
                <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Schedule</th>
                    <th>QR Code</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($employees as $employee)
                    <tr>
                        <td>{{ $employee->employee_id }}</td>
                        <td>{{ $employee->displayName() }}</td>
                        <td>{{ $employee->department ?? '—' }}</td>
                        <td>{{ $employee->position ?? '—' }}</td>
                        <td>{{ $employee->activeSchedule?->scheduleLabel() ?? '—' }}</td>
                        <td>{{ $employee->activeQrCode?->code ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $employee->status === 'active' ? 'badge--available' : 'badge--archived' }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn--ghost btn--sm">View</a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn--ghost btn--sm">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @include('partials.pagination', ['paginator' => $employees->withQueryString()])
        @endif
    </div>
</div>
@endsection
