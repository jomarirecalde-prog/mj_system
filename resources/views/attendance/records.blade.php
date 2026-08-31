@extends('layouts.app')

@section('title', 'DTR Records')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">DTR Records</h1>
                <p class="aa-page-header__desc">Complete daily time records.</p>
            </div>
        </div>
        @if(auth()->user()->isAdmin())
            <div class="aa-page-header__actions">
                <a href="{{ route('attendance.corrections.create') }}" class="btn btn--secondary">DTR Correction</a>
            </div>
        @endif
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-dtr" method="get" action="{{ route('attendance.records') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" id="dtr-search" class="aa-search__input" value="{{ request('search') }}" placeholder="Name or employee ID…" autocomplete="off" aria-label="Search DTR records">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="button" class="btn btn--secondary" id="aa-filters-dtr-toggle" aria-expanded="false" aria-controls="aa-filters-dtr-advanced aa-filters-dtr-mobile">Filters</button>
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-dtr-clear">Clear</button>
                    </div>
                </div>
                <div class="aa-filters__advanced aa-filters__advanced-desktop" id="aa-filters-dtr-advanced">
                    <div class="aa-filters__advanced-inner">
                        <div class="aa-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="dtr-employee">Employee</label>
                                <select name="employee_id" class="form-select" id="dtr-employee">
                                    <option value="">All</option>
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="dtr-dept">Department</label>
                                <select name="department" class="form-select" id="dtr-dept">
                                    <option value="">All</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="dtr-from">Date From</label>
                                <input type="date" name="date_from" id="dtr-from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="dtr-to">Date To</label>
                                <input type="date" name="date_to" id="dtr-to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="dtr-status">Status</label>
                                <select name="status" class="form-select" id="dtr-status">
                                    <option value="">All</option>
                                    @foreach(['present','late','absent','on_leave','official_business','half_day','undertime','incomplete','rest_day'] as $s)
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

    <div class="aa-filters__drawer-backdrop" id="aa-filters-dtr-backdrop" aria-hidden="true"></div>
    <div class="aa-filters__mobile" id="aa-filters-dtr-mobile" role="dialog" aria-label="Filter DTR records" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="aa-filters__grid">
            <div class="form-group">
                <label class="form-label" for="dtr-employee-mobile">Employee</label>
                <select id="dtr-employee-mobile" class="form-select" data-aa-sync="employee_id">
                    <option value="">All</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('employee_id') == $e->id)>{{ $e->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="dtr-dept-mobile">Department</label>
                <select id="dtr-dept-mobile" class="form-select" data-aa-sync="department">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="dtr-from-mobile">Date From</label>
                <input type="date" id="dtr-from-mobile" class="form-control" value="{{ request('date_from') }}" data-aa-sync="date_from">
            </div>
            <div class="form-group">
                <label class="form-label" for="dtr-to-mobile">Date To</label>
                <input type="date" id="dtr-to-mobile" class="form-control" value="{{ request('date_to') }}" data-aa-sync="date_to">
            </div>
            <div class="form-group">
                <label class="form-label" for="dtr-status-mobile">Status</label>
                <select id="dtr-status-mobile" class="form-select" data-aa-sync="status">
                    <option value="">All</option>
                    @foreach(['present','late','absent','on_leave','official_business','half_day','undertime','incomplete','rest_day'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="button" class="btn btn--primary btn--block" id="aa-filters-dtr-mobile-apply" style="margin-top:1rem;">Apply Filters</button>
    </div>

    <div class="card">
        <div class="card__body">
            <div class="aa-table-wrap aa-table-desktop" id="dtr-table-wrap">
                <table class="aa-table" id="dtr-table">
                    <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Employee ID</th>
                        <th scope="col">Employee Name</th>
                        <th scope="col">Department</th>
                        <th scope="col">Schedule</th>
                        <th scope="col">Time In</th>
                        <th scope="col">Time Out</th>
                        <th scope="col">Total Hours</th>
                        <th scope="col">Late</th>
                        <th scope="col">Undertime</th>
                        <th scope="col">Overtime</th>
                        <th scope="col">Status</th>
                        <th scope="col">Remarks</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody id="dtr-tbody">
                    @forelse($records as $r)
                        <tr>
                            <td>{{ $r->attendance_date?->format('M d, Y') }}</td>
                            <td>{{ $r->user?->employee_id }}</td>
                            <td><span class="aa-cell-primary">{{ $r->user?->displayName() }}</span></td>
                            <td>{{ $r->user?->department ?? '—' }}</td>
                            <td>{{ $r->scheduleLabel() }}</td>
                            <td>{{ $r->time_in ? ph_datetime($r->time_in, 'h:i:s A') : '—' }}</td>
                            <td>{{ $r->time_out ? ph_datetime($r->time_out, 'h:i:s A') : '—' }}</td>
                            <td>{{ $r->totalHoursLabel() }}</td>
                            <td>{{ $r->minutesLabel($r->late_minutes) }}</td>
                            <td>{{ $r->minutesLabel($r->undertime_minutes) }}</td>
                            <td>{{ $r->minutesLabel($r->overtime_minutes) }}</td>
                            <td>@include('partials.attendance-record-status-badge', ['status' => $r->status])</td>
                            <td>{{ $r->remarks ?: '—' }}</td>
                            <td><a class="btn btn--ghost btn--sm" href="{{ route('attendance.records.show', $r) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-muted">No DTR records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aa-mobile-cards" id="dtr-cards">
                @forelse($records as $r)
                    <article class="aa-card-row aa-expand-card" data-aa-expand>
                        <button type="button" class="aa-expand-card__toggle" aria-expanded="false">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $r->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $r->attendance_date?->format('M d, Y') }} · {{ $r->user?->employee_id }}</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                    @include('partials.attendance-record-status-badge', ['status' => $r->status])
                                    <span class="aa-expand-card__chevron" aria-hidden="true">▼</span>
                                </div>
                            </div>
                        </button>
                        <div class="aa-card-row__grid">
                            <div><span class="aa-card-row__label">Schedule</span> {{ $r->scheduleLabel() }}</div>
                            <div><span class="aa-card-row__label">Time In / Out</span> {{ $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }} → {{ $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</div>
                            <div><span class="aa-card-row__label">Hours</span> {{ $r->totalHoursLabel() }}</div>
                        </div>
                        <div class="aa-expand-card__details">
                            <div><span class="aa-card-row__label">Late</span> {{ $r->minutesLabel($r->late_minutes) }}</div>
                            <div><span class="aa-card-row__label">Undertime</span> {{ $r->minutesLabel($r->undertime_minutes) }}</div>
                            <div><span class="aa-card-row__label">Overtime</span> {{ $r->minutesLabel($r->overtime_minutes) }}</div>
                            <div><span class="aa-card-row__label">Remarks</span> {{ $r->remarks ?: '—' }}</div>
                            <a class="btn btn--primary btn--sm" href="{{ route('attendance.records.show', $r) }}">View Record</a>
                        </div>
                    </article>
                @empty
                    <div class="aa-empty"><p class="aa-empty__text">No DTR records found.</p></div>
                @endforelse
            </div>

            <div id="dtr-pagination">
                @include('partials.pagination', ['paginator' => $records->withQueryString()])
            </div>
        </div>
    </div>
</div>
@endsection
