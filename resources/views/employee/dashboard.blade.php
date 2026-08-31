@extends('layouts.employee')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Support\EmployeeAttendancePresenter;
    $displayName = $user->displayName();
    $nameParts = preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($nameParts) > 1) {
        $initials = strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[array_key_last($nameParts)], 0, 1));
    } else {
        $initials = strtoupper(substr($nameParts[0] ?? 'E', 0, 2));
    }
    $profilePhotoUrl = $user->profile_picture ? asset('storage/'.$user->profile_picture) : null;
@endphp

<div class="page-header emp-dash__welcome">
    <div>
        <h1>Welcome back, {{ explode(' ', $displayName)[0] }}</h1>
        <p class="page-header__meta">{{ now()->format('l, F j, Y') }} · {{ $user->department ?? 'No department' }}</p>
    </div>
</div>

<div class="emp-quick-actions">
    <a href="{{ route('employee.attendance') }}" class="emp-quick-action">
        <span class="emp-quick-action__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <span class="emp-quick-action__label">Attendance</span>
    </a>
    <a href="{{ route('employee.dtr') }}" class="emp-quick-action">
        <span class="emp-quick-action__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </span>
        <span class="emp-quick-action__label">My DTR</span>
    </a>
    <a href="{{ route('employee.calendar') }}" class="emp-quick-action">
        <span class="emp-quick-action__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
        <span class="emp-quick-action__label">Calendar</span>
    </a>
    <a href="{{ route('employee.qr') }}" class="emp-quick-action">
        <span class="emp-quick-action__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        </span>
        <span class="emp-quick-action__label">My QR</span>
    </a>
    <a href="{{ route('employee.corrections.create') }}" class="emp-quick-action">
        <span class="emp-quick-action__icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </span>
        <span class="emp-quick-action__label">Request Correction</span>
    </a>
</div>

<div class="grid-2 mb-2">
    <div class="card emp-dash__profile-card">
        <div class="card__body">
            <div class="emp-profile-avatar emp-profile-avatar--sm">
                @if($profilePhotoUrl)
                    <img src="{{ $profilePhotoUrl }}" alt="{{ $displayName }}">
                @else
                    <div class="emp-profile-avatar__fallback">{{ $initials }}</div>
                @endif
            </div>
            <div class="emp-dash__profile-info">
                <div class="emp-dash__profile-name">{{ $displayName }}</div>
                <div class="emp-dash__profile-meta">{{ $user->employee_id }}</div>
                <div class="emp-dash__profile-dept">{{ $user->department }} · {{ $user->position ?? '—' }}</div>
                <span class="emp-dash__schedule">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $schedule?->scheduleLabel() ?? $today['schedule'] }}
                </span>
            </div>
        </div>
    </div>

    <div class="card emp-dash__today" id="today-attendance-card">
        <div class="card__header">
            <h2 class="card__title">Today's Attendance</h2>
            <span class="emp-dash__live"><span class="emp-dash__live-dot" aria-hidden="true"></span> Live</span>
        </div>
        <div class="card__body">
            <dl class="dl-grid" style="margin:0;">
                <div class="dl-item"><dt>Schedule</dt><dd data-today="schedule">{{ $today['schedule'] }}</dd></div>
                <div class="dl-item"><dt>Time In</dt><dd data-today="time_in">{{ $today['time_in'] }}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd data-today="time_out">{{ $today['time_out'] }}</dd></div>
                <div class="dl-item"><dt>Total Hours</dt><dd data-today="hours">{{ $today['hours'] }}</dd></div>
                <div class="dl-item"><dt>Status</dt><dd><span class="badge badge--available" data-today="status">{{ $today['status'] }}</span></dd></div>
            </dl>
            <p class="emp-dash__clock">Last updated · <span id="server-clock">—</span></p>
        </div>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header">
        <h2 class="card__title">Official Time Request</h2>
        <a href="{{ route('employee.official-time.index') }}" class="btn btn--ghost btn--sm">View all</a>
    </div>
    <div class="card__body">
        @if($officialTimePending > 0)
            <p style="margin:0;"><strong>Pending:</strong> {{ $officialTimePending }}</p>
        @elseif($latestOfficialTime)
            <p style="margin:0;"><strong>Latest Request:</strong>
                <span class="badge {{ $latestOfficialTime->status === 'approved' ? 'badge--available' : ($latestOfficialTime->status === 'rejected' ? 'badge--out' : 'badge--warn') }}">
                    {{ $latestOfficialTime->statusLabel() }}
                </span>
            </p>
        @else
            <p class="text-muted" style="margin:0;">No official time requests yet.</p>
        @endif
        <a href="{{ route('employee.official-time.index') }}" class="btn btn--secondary btn--sm" style="margin-top:0.75rem;">Request Official Time</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header">
        <h2 class="card__title">This Month</h2>
        <a href="{{ route('employee.dtr') }}" class="btn btn--ghost btn--sm">View full DTR</a>
    </div>
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
