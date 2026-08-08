@extends('layouts.employee')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Support\EmployeeAttendancePresenter;
@endphp
<div class="page-header">
    <div>
        <h1>Welcome, {{ $user->displayName() }}</h1>
        <p class="page-header__meta">{{ $user->employee_id }} · {{ $user->department }} · {{ $user->position ?? '—' }}</p>
    </div>
</div>

<div class="grid-2 mb-2">
    <div class="card">
        <div class="card__body" style="display:flex;gap:1rem;align-items:center;">
            @if($user->profile_picture)
                <img src="{{ asset('storage/'.$user->profile_picture) }}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;">
            @else
                <div class="topbar__avatar" style="width:72px;height:72px;font-size:1.5rem;">{{ strtoupper(substr($user->displayName(), 0, 1)) }}</div>
            @endif
            <div>
                <div style="font-weight:700;font-size:1.15rem;">{{ $user->displayName() }}</div>
                <div class="text-muted">{{ $user->employee_id }}</div>
                <div>{{ $user->department }} · {{ $user->position ?? '—' }}</div>
                <div class="text-muted" style="margin-top:.35rem;">Schedule: {{ $schedule?->scheduleLabel() ?? $today['schedule'] }}</div>
            </div>
        </div>
    </div>

    <div class="card" id="today-attendance-card">
        <div class="card__header"><h2 class="card__title">Today's Attendance</h2></div>
        <div class="card__body">
            <dl class="dl-grid" style="margin:0;">
                <div class="dl-item"><dt>Schedule</dt><dd data-today="schedule">{{ $today['schedule'] }}</dd></div>
                <div class="dl-item"><dt>Time In</dt><dd data-today="time_in">{{ $today['time_in'] }}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd data-today="time_out">{{ $today['time_out'] }}</dd></div>
                <div class="dl-item"><dt>Total Hours</dt><dd data-today="hours">{{ $today['hours'] }}</dd></div>
                <div class="dl-item"><dt>Status</dt><dd><span class="badge badge--available" data-today="status">{{ $today['status'] }}</span></dd></div>
            </dl>
            <p class="text-muted" style="margin-top:.75rem;font-size:.85rem;">Live · <span id="server-clock">—</span></p>
        </div>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header"><h2 class="card__title">This Month</h2></div>
    <div class="card__body">
        <div class="stat-grid" id="month-summary">
            <div class="stat-card stat-card--ok"><div class="stat-card__label">Present</div><div class="stat-card__value" data-summary="present">{{ $summary['present'] }} days</div></div>
            <div class="stat-card stat-card--warn"><div class="stat-card__label">Late</div><div class="stat-card__value" data-summary="late_days">{{ $summary['late_days'] }} days</div></div>
            <div class="stat-card stat-card--danger"><div class="stat-card__label">Absent</div><div class="stat-card__value" data-summary="absent">{{ $summary['absent'] }} {{ $summary['absent'] === 1 ? 'day' : 'days' }}</div></div>
            <div class="stat-card"><div class="stat-card__label">Undertime</div><div class="stat-card__value" data-summary="undertime">{{ EmployeeAttendancePresenter::minutesToLabel($summary['undertime_minutes']) }}</div></div>
            <div class="stat-card"><div class="stat-card__label">Overtime</div><div class="stat-card__value" data-summary="overtime">{{ EmployeeAttendancePresenter::minutesToLabel($summary['overtime_minutes']) }}</div></div>
            <div class="stat-card stat-card--accent"><div class="stat-card__label">Total Hours</div><div class="stat-card__value" data-summary="total_hours">{{ EmployeeAttendancePresenter::minutesToLabel($summary['total_minutes']) }}</div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const url = @json(route('employee.live'));
    function apply(data) {
        const t = data.today || {};
        document.querySelectorAll('[data-today]').forEach((el) => {
            const key = el.getAttribute('data-today');
            if (t[key] !== undefined) el.textContent = t[key];
        });
        const s = data.summary || {};
        if (s.present !== undefined) document.querySelector('[data-summary="present"]').textContent = s.present + ' days';
        if (s.late_days !== undefined) document.querySelector('[data-summary="late_days"]').textContent = s.late_days + ' days';
        if (s.absent !== undefined) document.querySelector('[data-summary="absent"]').textContent = s.absent + (s.absent === 1 ? ' day' : ' days');
        if (s.undertime !== undefined) document.querySelector('[data-summary="undertime"]').textContent = s.undertime;
        if (s.overtime !== undefined) document.querySelector('[data-summary="overtime"]').textContent = s.overtime;
        if (s.total_hours !== undefined) document.querySelector('[data-summary="total_hours"]').textContent = s.total_hours;
        if (data.server_time) document.getElementById('server-clock').textContent = data.server_time;
    }
    async function poll() {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            if (json.success) apply(json);
        } catch (e) {}
    }
    poll();
    setInterval(poll, 10000);
})();
</script>
@endpush
