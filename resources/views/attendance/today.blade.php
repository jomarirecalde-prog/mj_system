@extends('layouts.app')

@section('title', "Today's Attendance")

@section('content')
<div class="page-header">
    <div>
        <h1>Today's Attendance</h1>
        <p class="page-header__meta">Daily monitoring · {{ $date }}</p>
    </div>
    <a href="{{ route('attendance.currently-in') }}" class="btn btn--secondary">Currently Time In</a>
</div>

<div class="stat-grid mb-2">
    <div class="stat-card"><div class="stat-card__label">Present</div><div class="stat-card__value">{{ $counts['present'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Late</div><div class="stat-card__value">{{ $counts['late'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Absent</div><div class="stat-card__value">{{ $counts['absent'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">On Leave</div><div class="stat-card__value">{{ $counts['on_leave'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Currently In</div><div class="stat-card__value">{{ $counts['currently_in'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Time Out</div><div class="stat-card__value">{{ $counts['timed_out'] }}</div></div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ $date }}"></div>
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or employee ID"></div>
            <div class="form-group"><label class="form-label">Department</label>
                <select name="department" class="form-select"><option value="">All</option>
                    @foreach($departments as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-select"><option value="">All</option>
                    @foreach(['present','late','absent','on_leave','undertime','incomplete','rest_day'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Employee</th><th>Department</th><th>Schedule</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Late</th><th>Status</th><th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $r)
                <tr>
                    <td>{{ $r->user?->displayName() }}<br><span class="text-muted" style="font-size:.8rem;">{{ $r->user?->employee_id }}</span></td>
                    <td>{{ $r->user?->department ?? '—' }}</td>
                    <td>{{ $r->scheduleLabel() }}</td>
                    <td>{{ $r->time_in ? ph_datetime($r->time_in, 'h:i:s A') : '—' }}</td>
                    <td>{{ $r->time_out ? ph_datetime($r->time_out, 'h:i:s A') : '—' }}</td>
                    <td>{{ $r->totalHoursLabel() }}</td>
                    <td>{{ $r->minutesLabel($r->late_minutes) }}</td>
                    <td><span class="badge">{{ $r->statusLabel() }}</span></td>
                    <td><a href="{{ route('attendance.records.show', $r) }}" class="btn btn--ghost btn--sm">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No attendance records for this date.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $records->withQueryString()])
    </div>
</div>
@endsection
