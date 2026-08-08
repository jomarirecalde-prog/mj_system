@extends('layouts.employee')

@section('title', 'My DTR')

@section('content')
@php
    use App\Support\EmployeeAttendancePresenter;
@endphp
<div class="page-header">
    <div>
        <h1>My Monthly DTR</h1>
        <p class="page-header__meta">{{ $user->displayName() }} · {{ $user->employee_id }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('employee.dtr.export', ['month' => $month, 'format' => 'print']) }}" class="btn btn--secondary" target="_blank">Print</a>
        <a href="{{ route('employee.dtr.export', ['month' => $month, 'format' => 'pdf']) }}" class="btn btn--secondary">PDF</a>
        <a href="{{ route('employee.dtr.export', ['month' => $month, 'format' => 'excel']) }}" class="btn btn--secondary">Excel</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
            </div>
        </form>
        <dl class="dl-grid" style="margin-top:1rem;">
            <div class="dl-item"><dt>Employee Name</dt><dd>{{ $user->displayName() }}</dd></div>
            <div class="dl-item"><dt>Employee ID</dt><dd>{{ $user->employee_id }}</dd></div>
            <div class="dl-item"><dt>Department</dt><dd>{{ $user->department }}</dd></div>
            <div class="dl-item"><dt>Position</dt><dd>{{ $user->position ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Month</dt><dd>{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</dd></div>
            <div class="dl-item"><dt>Assigned Schedule</dt><dd>{{ $schedule?->scheduleLabel() ?? 'Default' }}</dd></div>
        </dl>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Date</th><th>Day</th><th>Time In</th><th>Time Out</th>
                <th>Late</th><th>Undertime</th><th>Overtime</th><th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach($records as $row)
                @php $r = $row->record; @endphp
                <tr>
                    <td>{{ $row->date->format('M d, Y') }}</td>
                    <td>{{ $row->date->format('D') }}</td>
                    <td>{{ $r?->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }}</td>
                    <td>{{ $r?->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</td>
                    <td>{{ $r ? $r->minutesLabel($r->late_minutes) : '—' }}</td>
                    <td>{{ $r ? $r->minutesLabel($r->undertime_minutes) : '—' }}</td>
                    <td>{{ $r ? $r->minutesLabel($r->overtime_minutes) : '—' }}</td>
                    <td>{{ $r ? EmployeeAttendancePresenter::displayStatus($r, $row->date) : ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-card__label">Total Days Present</div><div class="stat-card__value">{{ $totals['present'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Days Absent</div><div class="stat-card__value">{{ $totals['absent'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Late</div><div class="stat-card__value">{{ EmployeeAttendancePresenter::minutesToLabel($totals['late']) }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Undertime</div><div class="stat-card__value">{{ EmployeeAttendancePresenter::minutesToLabel($totals['undertime']) }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Overtime</div><div class="stat-card__value">{{ EmployeeAttendancePresenter::minutesToLabel($totals['overtime']) }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Hours Worked</div><div class="stat-card__value">{{ EmployeeAttendancePresenter::minutesToLabel($totals['hours']) }}</div></div>
</div>
@endsection
