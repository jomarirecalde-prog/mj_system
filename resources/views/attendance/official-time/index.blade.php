@extends('layouts.app')

@section('title', 'Official Time Requests')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/official-time.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/official-time-admin.js') }}" defer></script>
@endpush

@section('content')
<div class="aa-module ot-admin-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">🕐</span>
            <div>
                <h1 class="aa-page-header__title">Official Time Requests</h1>
                <p class="aa-page-header__desc">Review and approve employee official time requests.</p>
            </div>
        </div>
    </header>

    <div class="aa-summary aa-summary--primary mb-2">
        <div class="aa-summary__card aa-summary__card--pending">
            <div class="aa-summary__label">Pending</div>
            <div class="aa-summary__value">{{ $stats['pending'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--approved">
            <div class="aa-summary__label">Approved</div>
            <div class="aa-summary__value">{{ $stats['approved'] }}</div>
        </div>
        <div class="aa-summary__card aa-summary__card--rejected">
            <div class="aa-summary__label">Rejected</div>
            <div class="aa-summary__value">{{ $stats['rejected'] }}</div>
        </div>
    </div>

    <div class="card aa-filters mb-2">
        <div class="card__body">
            <form method="get" action="{{ route('attendance.official-time.index') }}" id="ot-admin-filters">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Employee name or ID" aria-label="Search">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <a href="{{ route('attendance.official-time.index') }}" class="btn btn--ghost">Clear</a>
                    </div>
                </div>
                <div class="aa-filters__grid" style="margin-top:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="ot-status">Status</label>
                        <select name="status" id="ot-status" class="form-select">
                            <option value="">All</option>
                            @foreach(['pending','approved','rejected','cancelled'] as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ot-date-from">From</label>
                        <input type="date" name="date_from" id="ot-date-from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ot-date-to">To</label>
                        <input type="date" name="date_to" id="ot-date-to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    @if($departments->isNotEmpty())
                        <div class="form-group">
                            <label class="form-label" for="ot-dept">Department</label>
                            <select name="department" id="ot-dept" class="form-select">
                                <option value="">All</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($requests->isEmpty())
                <div class="aa-empty">
                    <h2 class="aa-empty__title">No official time requests</h2>
                    <p class="aa-empty__text">
                        @if(request()->hasAny(['search','status','date_from','date_to','department']))
                            No requests match your current filters.
                        @else
                            No pending official time requests. New employee requests will appear here.
                        @endif
                    </p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Effective Period</th>
                            <th>Current Time</th>
                            <th>Requested Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($requests as $req)
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $req->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $req->user?->employee_id }}</div>
                                </td>
                                <td>{{ $req->user?->department ?? '—' }}</td>
                                <td>{{ $req->effectivePeriodLabel() }}</td>
                                <td>{{ $req->timeRangeLabel('current') }}</td>
                                <td><strong>{{ $req->timeRangeLabel('requested') }}</strong></td>
                                <td>{{ Str::limit($req->reason, 50) }}</td>
                                <td>@include('partials.official-time-status-badge', ['status' => $req->status])</td>
                                <td>{{ $req->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('attendance.official-time.show', $req) }}" class="btn btn--primary btn--sm">Review</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($requests as $req)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $req->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $req->user?->employee_id }}</div>
                                </div>
                                @include('partials.official-time-status-badge', ['status' => $req->status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Effective</span> {{ $req->effectivePeriodLabel() }}</div>
                                <div><span class="aa-card-row__label">Current</span> {{ $req->timeRangeLabel('current') }}</div>
                                <div><span class="aa-card-row__label">Requested</span> <strong>{{ $req->timeRangeLabel('requested') }}</strong></div>
                                <div><span class="aa-card-row__label">Reason</span> {{ Str::limit($req->reason, 60) }}</div>
                            </div>
                            <div class="aa-card-row__actions">
                                <a href="{{ route('attendance.official-time.show', $req) }}" class="btn btn--primary btn--block">Review</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $requests])
            @endif
        </div>
    </div>
</div>
@endsection
