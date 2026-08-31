@extends('layouts.app')

@section('title', 'Currently Time In')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Currently Time In</h1>
                <p class="aa-page-header__desc">Employees currently on duty.</p>
                <p class="aa-page-header__meta">
                    <span>{{ \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('l, F j, Y') }}</span>
                    <span id="ci-clock" aria-live="polite">—</span>
                    <span class="aa-live-badge"><span class="aa-live-badge__dot" aria-hidden="true"></span> Live</span>
                </p>
                <p class="aa-last-updated" id="ci-last-updated" aria-live="polite"></p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.dashboard') }}" class="btn btn--secondary">Dashboard</a>
        </div>
    </header>

    <div class="aa-summary" style="margin-bottom:var(--aa-space-xl);">
        <div class="aa-summary__card">
            <div class="aa-summary__label">Currently on duty</div>
            <div class="aa-summary__value"><span id="ci-count">{{ $records->count() }}</span> employees</div>
        </div>
    </div>

    <div class="card aa-panel-live">
        <div class="card__body">
            <div class="aa-table-wrap aa-table-desktop" id="ci-table-wrap">
                <table class="aa-table" id="ci-table">
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
                    @forelse($records as $row)
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
                        <tr><td colspan="5" class="text-muted">Nobody is currently timed in.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aa-mobile-cards" id="ci-cards">
                @forelse($records as $row)
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
                    <div class="aa-empty"><p class="aa-empty__text">Nobody is currently timed in.</p></div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    AttendanceUI.initLiveAttendance({
        url: @json(route('attendance.live')),
        clockId: 'ci-clock',
        tableBodyId: 'ci-table',
        cardsId: 'ci-cards',
        countId: 'ci-count',
        updatedId: 'ci-last-updated',
        emptyMessage: 'Nobody is currently timed in.',
    });
});
</script>
@endpush
