@extends('layouts.app')

@section('title', "Today's Attendance")

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Today's Attendance</h1>
                <p class="aa-page-header__desc">Monitor employee attendance for the selected date.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.currently-in') }}" class="btn btn--secondary">Currently Time In</a>
        </div>
    </header>

    <div class="aa-summary aa-summary--primary">
        <div class="aa-summary__card aa-summary__card--approved">
            <div class="aa-summary__label">Present</div>
            <div class="aa-summary__value">{{ $counts['present'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--late">
            <div class="aa-summary__label">Late</div>
            <div class="aa-summary__value">⚠ {{ $counts['late'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--absent">
            <div class="aa-summary__label">Absent</div>
            <div class="aa-summary__value">✕ {{ $counts['absent'] }}</div>
        </div>
        <div class="aa-summary__card">
            <div class="aa-summary__label">On Leave</div>
            <div class="aa-summary__value">{{ $counts['on_leave'] }}</div>
        </div>
        <div class="aa-summary__card">
            <div class="aa-summary__label">Currently In</div>
            <div class="aa-summary__value">{{ $counts['currently_in'] }}</div>
        </div>
        <div class="aa-summary__card">
            <div class="aa-summary__label">Time Out</div>
            <div class="aa-summary__value">{{ $counts['timed_out'] }}</div>
        </div>
    </div>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-today" method="get" action="{{ route('attendance.today') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Search employee or employee ID…" aria-label="Search employees">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="button" class="btn btn--secondary" id="aa-filters-today-toggle" aria-expanded="false" aria-controls="aa-filters-today-advanced aa-filters-today-mobile">Filters</button>
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-today-clear">Clear Filters</button>
                    </div>
                </div>
                <div class="aa-filters__advanced aa-filters__advanced-desktop" id="aa-filters-today-advanced">
                    <div class="aa-filters__advanced-inner">
                        <div class="aa-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="today-date">Date</label>
                                <input type="date" name="date" id="today-date" class="form-control" value="{{ $date }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="today-dept">Department</label>
                                <select name="department" id="today-dept" class="form-select">
                                    <option value="">All</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="today-status">Status</label>
                                <select name="status" id="today-status" class="form-select">
                                    <option value="">All</option>
                                    @foreach(['present','late','absent','on_leave','undertime','incomplete','rest_day'] as $s)
                                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="aa-filters__drawer-backdrop" id="aa-filters-today-backdrop" aria-hidden="true"></div>
    <div class="aa-filters__mobile" id="aa-filters-today-mobile" role="dialog" aria-label="Filter today's attendance" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="aa-filters__grid">
            <div class="form-group">
                <label class="form-label" for="today-date-mobile">Date</label>
                <input type="date" id="today-date-mobile" class="form-control" value="{{ $date }}" data-aa-sync="date">
            </div>
            <div class="form-group">
                <label class="form-label" for="today-dept-mobile">Department</label>
                <select id="today-dept-mobile" class="form-select" data-aa-sync="department">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="today-status-mobile">Status</label>
                <select id="today-status-mobile" class="form-select" data-aa-sync="status">
                    <option value="">All</option>
                    @foreach(['present','late','absent','on_leave','undertime','incomplete','rest_day'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="button" class="btn btn--primary btn--block" id="aa-filters-today-mobile-apply" style="margin-top:1rem;">Apply Filters</button>
    </div>

    <div class="card">
        <div class="card__body">
            @if($records->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <h2 class="aa-empty__title">No attendance records</h2>
                    <p class="aa-empty__text">No attendance data is available for the selected period.</p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Department</th>
                            <th scope="col">Schedule</th>
                            <th scope="col">Time In</th>
                            <th scope="col">Time Out</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Late</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($records as $r)
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $r->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $r->user?->employee_id }}</div>
                                </td>
                                <td>{{ $r->user?->department ?? '—' }}</td>
                                <td>{{ $r->scheduleLabel() }}</td>
                                <td>{{ $r->time_in ? ph_datetime($r->time_in, 'h:i:s A') : '—' }}</td>
                                <td>{{ $r->time_out ? ph_datetime($r->time_out, 'h:i:s A') : '—' }}</td>
                                <td>{{ $r->totalHoursLabel() }}</td>
                                <td>{{ $r->minutesLabel($r->late_minutes) }}</td>
                                <td>@include('partials.attendance-record-status-badge', ['status' => $r->status])</td>
                                <td><a href="{{ route('attendance.records.show', $r) }}" class="btn btn--ghost btn--sm">View</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($records as $r)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $r->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $r->user?->employee_id }}</div>
                                </div>
                                @include('partials.attendance-record-status-badge', ['status' => $r->status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Department</span> {{ $r->user?->department ?? '—' }}</div>
                                <div><span class="aa-card-row__label">Schedule</span> {{ $r->scheduleLabel() }}</div>
                                <div><span class="aa-card-row__label">Time In / Out</span> {{ $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }} → {{ $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</div>
                                <div><span class="aa-card-row__label">Hours</span> {{ $r->totalHoursLabel() }} · Late: {{ $r->minutesLabel($r->late_minutes) }}</div>
                            </div>
                            <div class="aa-card-row__actions">
                                <a href="{{ route('attendance.records.show', $r) }}" class="btn btn--primary btn--block">View</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $records->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection
