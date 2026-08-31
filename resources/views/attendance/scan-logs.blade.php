@extends('layouts.app')

@section('title', 'QR Scan Logs')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/scanning.css') }}">
@endpush

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('date') || request()->filled('result');
@endphp

<div class="aa-module scan-module">
    <header class="scan-logs-header scan-header">
        <div class="scan-header__left">
            <span class="scan-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </span>
            <div>
                <h1 class="scan-header__title">QR Scan Logs</h1>
                <p class="scan-header__desc">Monitor every QR scan attempt and its result.</p>
                @if($logs->total() > 0)
                    <p class="scan-header__desc" style="margin-top:0.35rem;font-size:0.82rem;">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} records
                    </p>
                @endif
            </div>
        </div>
    </header>

    {{-- Filters --}}
    <div class="card scan-logs-filters">
        <div class="card__body">
            <form method="get" action="{{ route('attendance.scan-logs') }}" id="scan-logs-filters">
                <div class="scan-logs-filters__top">
                    <div class="scan-logs-search">
                        <svg class="scan-logs-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="scan-logs-search__input" value="{{ request('search') }}" placeholder="Search employee, QR code, scanner…" aria-label="Search scan logs">
                    </div>
                    <button type="button" class="btn btn--secondary scan-logs-filters__mobile-toggle" id="scan-logs-filter-toggle">Filters</button>
                    <button type="submit" class="btn btn--primary scan-logs-filters__desktop-submit">Apply Filters</button>
                    @if($hasFilters)
                        <a href="{{ route('attendance.scan-logs') }}" class="btn btn--ghost">Clear Filters</a>
                    @endif
                </div>

                <div class="scan-logs-filters__advanced scan-logs-filters__desktop is-open">
                    <div class="scan-logs-filters__advanced-inner">
                        <div class="scan-logs-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="scan-log-date">Date</label>
                                <input type="date" name="date" id="scan-log-date" class="form-control" value="{{ request('date') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="scan-log-result">Result</label>
                                <select name="result" id="scan-log-result" class="form-select">
                                    <option value="">All Results</option>
                                    @foreach(['success','late','already_in','already_out','invalid','inactive','cooldown'] as $r)
                                        <option value="{{ $r }}" @selected(request('result') === $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div class="scan-logs-filters__backdrop" id="scan-logs-filter-backdrop" aria-hidden="true"></div>
    <div class="scan-logs-filters__mobile-drawer" id="scan-logs-filter-drawer" role="dialog" aria-label="Filter scan logs" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="scan-logs-filters__grid">
            <div class="form-group">
                <label class="form-label" for="scan-log-date-mobile">Date</label>
                <input type="date" id="scan-log-date-mobile" class="form-control" value="{{ request('date') }}" data-sync="date">
            </div>
            <div class="form-group">
                <label class="form-label" for="scan-log-result-mobile">Result</label>
                <select id="scan-log-result-mobile" class="form-select" data-sync="result">
                    <option value="">All Results</option>
                    @foreach(['success','late','already_in','already_out','invalid','inactive','cooldown'] as $r)
                        <option value="{{ $r }}" @selected(request('result') === $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="btn-group mt-2">
            <button type="button" class="btn btn--primary btn--block" id="scan-logs-filter-apply">Apply Filters</button>
            @if($hasFilters)
                <a href="{{ route('attendance.scan-logs') }}" class="btn btn--secondary btn--block">Clear Filters</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($logs->isEmpty())
                <div class="scan-logs-empty">
                    <svg class="scan-logs-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <h2 class="scan-logs-empty__title">No scan activity found</h2>
                    <p class="scan-logs-empty__text">
                        @if($hasFilters)
                            No QR scan records match your current filters.
                        @else
                            Scan activity will appear here once employees start scanning.
                        @endif
                    </p>
                    @if($hasFilters)
                        <a href="{{ route('attendance.scan-logs') }}" class="btn btn--primary">Clear Filters</a>
                    @endif
                </div>
            @else
                {{-- Desktop table --}}
                <div class="table-wrap scan-logs-table-wrap">
                    <table class="data-table scan-logs-table">
                        <thead>
                            <tr>
                                <th scope="col">Time</th>
                                <th scope="col">Employee</th>
                                <th scope="col">QR Code</th>
                                <th scope="col">Action</th>
                                <th scope="col">Scanner</th>
                                <th scope="col">Device</th>
                                <th scope="col">Result</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <div class="scan-log-time">
                                        <div class="scan-log-time__date">{{ optional($log->scan_date)->format('M d, Y') ?? '—' }}</div>
                                        <div class="scan-log-time__clock">{{ $log->scan_time ? \Carbon\Carbon::parse($log->scan_time)->format('h:i:s A') : '—' }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="scan-log-employee">
                                        <div class="scan-log-employee__name">{{ $log->employee?->displayName() ?? '—' }}</div>
                                        @if($log->employee?->employee_id)
                                            <div class="scan-log-employee__id">{{ $log->employee->employee_id }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td><code style="font-size:0.82rem;">{{ $log->qr_code }}</code></td>
                                <td>{{ $log->action ? str_replace('_', ' ', ucfirst($log->action)) : '—' }}</td>
                                <td>{{ $log->scanner?->displayName() ?? '—' }}</td>
                                <td>{{ $log->device ?? '—' }}</td>
                                <td>@include('partials.scan-result-badge', ['result' => $log->result])</td>
                                <td>{{ Str::limit($log->remarks, 50) ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="scan-logs-cards" aria-label="Scan log entries">
                    @foreach($logs as $log)
                        <article class="scan-log-card">
                            <div class="scan-log-card__head">
                                <div>
                                    <div class="scan-log-employee__name">{{ $log->employee?->displayName() ?? '—' }}</div>
                                    @if($log->employee?->employee_id)
                                        <div class="scan-log-employee__id">{{ $log->employee->employee_id }}</div>
                                    @endif
                                </div>
                                @include('partials.scan-result-badge', ['result' => $log->result])
                            </div>
                            <dl class="scan-log-card__meta">
                                <div>
                                    <dt>Time</dt>
                                    <dd>{{ optional($log->scan_date)->format('M d, Y') }} · {{ $log->scan_time ? \Carbon\Carbon::parse($log->scan_time)->format('h:i:s A') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Action</dt>
                                    <dd>{{ $log->action ? str_replace('_', ' ', ucfirst($log->action)) : '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Scanner</dt>
                                    <dd>{{ $log->scanner?->displayName() ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Device</dt>
                                    <dd>{{ $log->device ?? '—' }}</dd>
                                </div>
                                <div style="grid-column:1/-1;">
                                    <dt>QR Code</dt>
                                    <dd><code style="font-size:0.82rem;">{{ $log->qr_code }}</code></dd>
                                </div>
                                @if($log->remarks)
                                    <div style="grid-column:1/-1;">
                                        <dt>Remarks</dt>
                                        <dd>{{ $log->remarks }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $logs->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('scan-logs-filter-toggle');
    const drawer = document.getElementById('scan-logs-filter-drawer');
    const backdrop = document.getElementById('scan-logs-filter-backdrop');
    const form = document.getElementById('scan-logs-filters');
    const applyBtn = document.getElementById('scan-logs-filter-apply');

    function openDrawer() {
        drawer?.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer?.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', openDrawer);
    backdrop?.addEventListener('click', closeDrawer);

    document.querySelectorAll('[data-sync]').forEach(function (el) {
        el.addEventListener('change', function () {
            const name = el.dataset.sync;
            const target = form?.querySelector('[name="' + name + '"]');
            if (!target) return;
            target.value = el.value;
        });
    });

    applyBtn?.addEventListener('click', function () {
        document.querySelectorAll('[data-sync]').forEach(function (el) {
            const name = el.dataset.sync;
            const target = form?.querySelector('[name="' + name + '"]');
            if (target) target.value = el.value;
        });
        form?.submit();
    });
})();
</script>
@endpush
