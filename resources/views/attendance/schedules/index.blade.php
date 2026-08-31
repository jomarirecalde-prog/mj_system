@extends('layouts.app')

@section('title', 'Employee Schedules')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '—';
    $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Employee Schedules</h1>
                <p class="aa-page-header__desc">Manage regular schedules and employee shift assignments.</p>
            </div>
        </div>
        @if(auth()->user()->isAdmin())
            <div class="aa-page-header__actions">
                <a href="{{ route('attendance.schedules.create') }}" class="btn btn--primary">Add Schedule</a>
                <a href="{{ route('attendance.shifts.index') }}" class="btn btn--secondary">Manage Shifts</a>
            </div>
        @endif
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-schedules" method="get" action="{{ route('attendance.schedules.index') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Search employee or employee ID…" aria-label="Search schedules">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-schedules-clear">Clear Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($schedules->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <h2 class="aa-empty__title">No schedules configured</h2>
                    <p class="aa-empty__text">
                        @if(request('search'))
                            No employee schedules match your search.
                        @else
                            No employee schedules have been configured yet.
                        @endif
                    </p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Schedule Type</th>
                            <th scope="col">Shift</th>
                            <th scope="col">Working Hours</th>
                            <th scope="col">Break</th>
                            <th scope="col">Rest Days</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($schedules as $s)
                            @php
                                $rest = collect($s->rest_days ?? [])->map(fn ($d) => $dayNames[$d] ?? $d)->implode(', ');
                            @endphp
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $s->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $s->user?->employee_id }}</div>
                                </td>
                                <td>{{ ucfirst($s->schedule_type) }}</td>
                                <td>{{ $s->shift?->name ?? '—' }}</td>
                                <td><strong>{{ $fmtTime($s->time_in) }} → {{ $fmtTime($s->time_out) }}</strong></td>
                                <td>{{ $s->break_start ? $fmtTime($s->break_start).' → '.$fmtTime($s->break_end) : '—' }}</td>
                                <td>{{ $rest ?: '—' }}</td>
                                <td>
                                    @include('partials.attendance-status-badge', [
                                        'status' => $s->is_active ? 'active' : 'inactive',
                                    ])
                                </td>
                                <td>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('attendance.schedules.edit', $s) }}" class="btn btn--ghost btn--sm">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($schedules as $s)
                        @php
                            $rest = collect($s->rest_days ?? [])->map(fn ($d) => $dayNames[$d] ?? $d)->implode(', ');
                        @endphp
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $s->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $s->user?->employee_id }}</div>
                                </div>
                                @include('partials.attendance-status-badge', [
                                    'status' => $s->is_active ? 'active' : 'inactive',
                                ])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Type</span> {{ ucfirst($s->schedule_type) }}</div>
                                <div><span class="aa-card-row__label">Shift</span> {{ $s->shift?->name ?? '—' }}</div>
                                <div><span class="aa-card-row__label">Hours</span> <strong>{{ $fmtTime($s->time_in) }} → {{ $fmtTime($s->time_out) }}</strong></div>
                                <div><span class="aa-card-row__label">Break</span> {{ $s->break_start ? $fmtTime($s->break_start).' → '.$fmtTime($s->break_end) : '—' }}</div>
                                <div><span class="aa-card-row__label">Rest days</span> {{ $rest ?: '—' }}</div>
                            </div>
                            @if(auth()->user()->isAdmin())
                                <div class="aa-card-row__actions">
                                    <a href="{{ route('attendance.schedules.edit', $s) }}" class="btn btn--primary btn--block">Edit Schedule</a>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $schedules->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection
