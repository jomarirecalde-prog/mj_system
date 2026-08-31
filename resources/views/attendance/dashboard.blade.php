@extends('layouts.app')

@section('title', 'Attendance Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Today's Attendance</h1>
                <p class="aa-page-header__desc">
                    {{ \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('l, F j, Y') }}
                </p>
                <p class="aa-page-header__meta">
                    <span>Philippine Time</span>
                    <span id="server-clock" aria-live="polite">—</span>
                </p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.scanner') }}" class="btn btn--primary">Open QR Scanner</a>
            <a href="{{ route('attendance.today') }}" class="btn btn--secondary">Today's List</a>
        </div>
    </header>

    <div class="aa-summary aa-summary--primary" id="attendance-stats">
        <div class="aa-summary__card">
            <div class="aa-summary__label">Total Employees</div>
            <div class="aa-summary__value" data-stat="total">{{ $counts['total'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--approved">
            <div class="aa-summary__label">Present</div>
            <div class="aa-summary__value" data-stat="present">{{ $counts['present'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--late">
            <div class="aa-summary__label">Late</div>
            <div class="aa-summary__value" data-stat="late">⚠ {{ $counts['late'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--absent">
            <div class="aa-summary__label">Absent</div>
            <div class="aa-summary__value" data-stat="absent">✕ {{ $counts['absent'] }}</div>
        </div>
    </div>

    <div class="aa-summary aa-summary--secondary mb-2" id="attendance-stats-secondary">
        <div class="aa-summary__card">
            <div class="aa-summary__label">On Leave</div>
            <div class="aa-summary__value" data-stat="on_leave">{{ $counts['on_leave'] }}</div>
        </div>
        <div class="aa-summary__card">
            <div class="aa-summary__label">Currently Time In</div>
            <div class="aa-summary__value" data-stat="currently_in">{{ $counts['currently_in'] }}</div>
        </div>
        <div class="aa-summary__card">
            <div class="aa-summary__label">Already Time Out</div>
            <div class="aa-summary__value" data-stat="timed_out">{{ $counts['timed_out'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--incomplete">
            <div class="aa-summary__label">Incomplete DTR</div>
            <div class="aa-summary__value" data-stat="incomplete">⚠ {{ $counts['incomplete'] }}</div>
        </div>
    </div>

    @if(auth()->user()->isAdmin() && ($pendingOfficialTime ?? 0) > 0)
        <div class="card mb-2">
            <div class="card__body" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div>
                    <strong>Pending Official Time Requests</strong>
                    <p class="text-muted" style="margin:0.25rem 0 0;">{{ $pendingOfficialTime }} pending {{ Str::plural('request', $pendingOfficialTime) }}</p>
                </div>
                <a href="{{ route('attendance.official-time.index', ['status' => 'pending']) }}" class="btn btn--primary">Review Requests</a>
            </div>
        </div>
    @endif

    <div class="grid-2">
        <div class="card aa-panel-live">
            <div class="card__header">
                <h2 class="card__title">Currently Time In</h2>
                <span class="aa-live-badge"><span class="aa-live-badge__dot" aria-hidden="true"></span> Live</span>
            </div>
            <div class="card__body">
                <p class="aa-last-updated" id="dash-last-updated" aria-live="polite"></p>
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table" id="currently-in-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Department</th>
                            <th scope="col">Time In</th>
                            <th scope="col">Duration</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($currentlyIn as $row)
                            @php $mins = $row->time_in ? $row->time_in->diffInMinutes(now('Asia/Manila')) : 0; @endphp
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $row->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $row->user?->employee_id }}</div>
                                </td>
                                <td>{{ $row->user?->department ?? '—' }}</td>
                                <td>{{ ph_datetime($row->time_in, 'h:i A') }}</td>
                                <td><span class="aa-duration">{{ intdiv($mins, 60) }}h {{ sprintf('%02d', $mins % 60) }}m</span></td>
                                <td>@include('partials.attendance-record-status-badge', ['status' => $row->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No employees currently timed in.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="aa-mobile-cards" id="dash-ci-cards">
                    @forelse($currentlyIn as $row)
                        @php $mins = $row->time_in ? $row->time_in->diffInMinutes(now('Asia/Manila')) : 0; @endphp
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $row->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $row->user?->employee_id }}</div>
                                </div>
                                @include('partials.attendance-record-status-badge', ['status' => $row->status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Department</span> {{ $row->user?->department ?? '—' }}</div>
                                <div><span class="aa-card-row__label">Time In</span> {{ ph_datetime($row->time_in, 'h:i A') }}</div>
                                <div><span class="aa-card-row__label">Duration</span> <span class="aa-duration">{{ intdiv($mins, 60) }}h {{ sprintf('%02d', $mins % 60) }}m</span></div>
                            </div>
                        </article>
                    @empty
                        <div class="aa-empty" style="padding:1rem;"><p class="aa-empty__text">No employees currently timed in.</p></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><h2 class="card__title">Recent Punches</h2></div>
            <div class="card__body">
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Time In</th>
                            <th scope="col">Time Out</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recent as $row)
                            <tr>
                                <td><span class="aa-cell-primary">{{ $row->user?->displayName() }}</span></td>
                                <td>{{ $row->time_in ? ph_datetime($row->time_in, 'h:i A') : '—' }}</td>
                                <td>{{ $row->time_out ? ph_datetime($row->time_out, 'h:i A') : '—' }}</td>
                                <td>@include('partials.attendance-record-status-badge', ['status' => $row->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No punches yet today.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="aa-mobile-cards">
                    @forelse($recent as $row)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div class="aa-cell-primary">{{ $row->user?->displayName() }}</div>
                                @include('partials.attendance-record-status-badge', ['status' => $row->status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Time In</span> {{ $row->time_in ? ph_datetime($row->time_in, 'h:i A') : '—' }}</div>
                                <div><span class="aa-card-row__label">Time Out</span> {{ $row->time_out ? ph_datetime($row->time_out, 'h:i A') : '—' }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="aa-empty" style="padding:1rem;"><p class="aa-empty__text">No punches yet today.</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.App) window.App = {};
    if (!App.escapeHtml && window.AttendanceUI) App.escapeHtml = AttendanceUI.escapeHtml;

    AttendanceUI.initLiveAttendance({
        url: @json(route('attendance.live')),
        clockId: 'server-clock',
        tableBodyId: 'currently-in-table',
        cardsId: 'dash-ci-cards',
        updatedId: 'dash-last-updated',
        statsRootId: 'attendance-stats',
        emptyMessage: 'No employees currently timed in.',
    });
});
</script>
@endpush
