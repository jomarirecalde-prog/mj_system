@extends('layouts.employee')

@section('title', 'My Attendance')

@section('content')
@php use App\Support\EmployeeAttendancePresenter; @endphp
<div class="page-header">
    <div>
        <h1>My Attendance</h1>
        <p class="page-header__meta">Your personal attendance records</p>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group">
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="today" @selected($filter==='today')>Today</option>
                    <option value="this_week" @selected($filter==='this_week')>This week</option>
                    <option value="this_month" @selected($filter==='this_month')>This month</option>
                    <option value="custom" @selected($filter==='custom')>Custom date range</option>
                </select>
            </div>
            @if($filter === 'custom')
                <div class="form-group"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from ?? $start }}"></div>
                <div class="form-group"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to ?? $end }}"></div>
                <button class="btn btn--secondary" type="submit">Apply</button>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Schedule</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Total Hours</th>
                <th>Late</th>
                <th>Undertime</th>
                <th>Overtime</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $r)
                <tr>
                    <td>{{ $r->attendance_date?->format('M d, Y') }}</td>
                    <td>{{ $r->attendance_date?->format('D') }}</td>
                    <td>{{ $r->scheduleLabel() }}</td>
                    <td>{{ $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }}</td>
                    <td>{{ $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</td>
                    <td>{{ $r->totalHoursLabel() }}</td>
                    <td>{{ $r->minutesLabel($r->late_minutes) }}</td>
                    <td>{{ $r->minutesLabel($r->undertime_minutes) }}</td>
                    <td>{{ $r->minutesLabel($r->overtime_minutes) }}</td>
                    <td><span class="badge badge--default">{{ EmployeeAttendancePresenter::displayStatus($r) }}</span></td>
                    <td>{{ $r->remarks ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-muted">No attendance records for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $records])
    </div>
</div>
@endsection
