@extends('layouts.app')

@section('title', 'DTR Correction History')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">DTR Correction History</h1>
                <p class="aa-page-header__desc">Complete audit history of manual attendance adjustments.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.correction-requests.index') }}" class="btn btn--secondary">
                Employee Requests
                @if(($pendingCount ?? 0) > 0)
                    <span class="badge badge--warn" style="margin-left:.35rem;">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('attendance.corrections.create') }}" class="btn btn--primary">New Correction</a>
        </div>
    </header>

    @if(($pendingCount ?? 0) > 0)
        <div class="aa-summary">
            <div class="aa-summary__card aa-summary__card--pending">
                <div class="aa-summary__label">Pending Requests</div>
                <div class="aa-summary__value">{{ $pendingCount }}</div>
            </div>
        </div>
    @endif

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-corrections" method="get" action="{{ route('attendance.corrections.index') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Search employee or employee ID…" aria-label="Search corrections">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-corrections-clear">Clear Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($adjustments->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h2 class="aa-empty__title">No corrections recorded</h2>
                    <p class="aa-empty__text">
                        @if(request('search'))
                            No DTR corrections match your current search.
                        @else
                            No DTR corrections have been recorded yet.
                        @endif
                    </p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Field</th>
                            <th scope="col">Original</th>
                            <th scope="col">Corrected</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Corrected By</th>
                            <th scope="col">Date / Time</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($adjustments as $adj)
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $adj->employee?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $adj->employee?->employee_id }}</div>
                                </td>
                                <td>{{ $adj->field_name }}</td>
                                <td>
                                    <div class="aa-comparison">
                                        <span class="aa-comparison__label">Original</span>
                                        <span>{{ $adj->original_value ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="aa-comparison">
                                        <span class="aa-comparison__arrow" aria-hidden="true">↓</span>
                                        <span class="aa-comparison__label">Corrected</span>
                                        <span class="aa-comparison__corrected">{{ $adj->corrected_value ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>{{ $adj->reason }}</td>
                                <td>{{ $adj->corrector?->displayName() }}</td>
                                <td>
                                    <span class="aa-badge aa-badge--audit" style="margin-bottom:.35rem;display:inline-flex;">Audit Record</span>
                                    <div class="aa-cell-secondary">{{ ph_datetime($adj->corrected_at) }}</div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($adjustments as $adj)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $adj->employee?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $adj->employee?->employee_id }}</div>
                                </div>
                                <span class="aa-badge aa-badge--audit">Audit Record</span>
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Field</span> {{ $adj->field_name }}</div>
                                <div>
                                    <span class="aa-card-row__label">Change</span>
                                    {{ $adj->original_value ?? '—' }} → <strong>{{ $adj->corrected_value ?? '—' }}</strong>
                                </div>
                                <div><span class="aa-card-row__label">Reason</span> {{ $adj->reason }}</div>
                                <div><span class="aa-card-row__label">Corrected by</span> {{ $adj->corrector?->displayName() }}</div>
                                <div><span class="aa-card-row__label">Date / time</span> {{ ph_datetime($adj->corrected_at) }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $adjustments->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection
