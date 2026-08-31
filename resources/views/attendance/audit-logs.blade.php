@extends('layouts.app')

@section('title', 'Attendance Audit Logs')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Attendance Audit Logs</h1>
                <p class="aa-page-header__desc">Read-only record of attendance changes and actions.</p>
                <span class="aa-readonly-banner" style="margin-top:.5rem;">🔒 Read Only</span>
            </div>
        </div>
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-audit" method="get" action="{{ route('attendance.audit-logs') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Search employee or action…" aria-label="Search audit logs">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="button" class="btn btn--secondary" id="aa-filters-audit-toggle" aria-expanded="false" aria-controls="aa-filters-audit-advanced aa-filters-audit-mobile">Filters</button>
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-audit-clear">Clear</button>
                    </div>
                </div>
                <div class="aa-filters__advanced aa-filters__advanced-desktop" id="aa-filters-audit-advanced">
                    <div class="aa-filters__advanced-inner">
                        <div class="form-group">
                            <label class="form-label" for="audit-action">Action</label>
                            <input type="text" name="action" id="audit-action" class="form-control" value="{{ request('action') }}" placeholder="QR Time In">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="aa-filters__drawer-backdrop" id="aa-filters-audit-backdrop" aria-hidden="true"></div>
    <div class="aa-filters__mobile" id="aa-filters-audit-mobile" role="dialog" aria-label="Filter audit logs" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="form-group">
            <label class="form-label" for="audit-action-mobile">Action</label>
            <input type="text" id="audit-action-mobile" class="form-control" value="{{ request('action') }}" data-aa-sync="action">
        </div>
        <button type="button" class="btn btn--primary btn--block" id="aa-filters-audit-mobile-apply" style="margin-top:1rem;">Apply Filters</button>
    </div>

    <div class="card">
        <div class="card__body">
            @if($logs->isEmpty())
                <div class="aa-empty">
                    <p class="aa-empty__text">No attendance audit logs match your filters.</p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Action</th>
                            <th scope="col">Original</th>
                            <th scope="col">New</th>
                            <th scope="col">User</th>
                            <th scope="col">Date/Time</th>
                            <th scope="col">IP</th>
                            <th scope="col">Device</th>
                            <th scope="col">Reason</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td><span class="aa-cell-primary">{{ $log->employee?->displayName() ?? '—' }}</span></td>
                                <td>{{ $log->action }}</td>
                                <td>
                                    <div class="aa-comparison">
                                        <span class="aa-comparison__label">Original</span>
                                        <span>{{ $log->original_value ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="aa-comparison">
                                        <span class="aa-comparison__arrow" aria-hidden="true">↓</span>
                                        <span class="aa-comparison__label">New</span>
                                        <span class="aa-comparison__corrected">{{ $log->new_value ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>{{ $log->performer?->displayName() ?? '—' }}</td>
                                <td>{{ ph_datetime($log->logged_at) }}</td>
                                <td><code style="font-size:.78rem;">{{ $log->ip_address ?? '—' }}</code></td>
                                <td><code style="font-size:.78rem;">{{ $log->device ?? '—' }}</code></td>
                                <td>{{ $log->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($logs as $log)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div class="aa-cell-primary">{{ $log->employee?->displayName() ?? '—' }}</div>
                                <span class="aa-badge aa-badge--audit">Audit</span>
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Action</span> {{ $log->action }}</div>
                                <div><span class="aa-card-row__label">Change</span> {{ $log->original_value ?? '—' }} → <strong>{{ $log->new_value ?? '—' }}</strong></div>
                                <div><span class="aa-card-row__label">User</span> {{ $log->performer?->displayName() ?? '—' }}</div>
                                <div><span class="aa-card-row__label">When</span> {{ ph_datetime($log->logged_at) }}</div>
                                <div><span class="aa-card-row__label">IP</span> <code>{{ $log->ip_address ?? '—' }}</code></div>
                                <div><span class="aa-card-row__label">Device</span> <code>{{ $log->device ?? '—' }}</code></div>
                                @if($log->reason)
                                    <div><span class="aa-card-row__label">Reason</span> {{ $log->reason }}</div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $logs->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection
