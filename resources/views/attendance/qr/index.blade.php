@extends('layouts.app')

@section('title', 'Employee QR Codes')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Employee QR Codes</h1>
                <p class="aa-page-header__desc">Manage secure attendance identifiers for employees.</p>
            </div>
        </div>
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-qr" method="get" action="{{ route('attendance.qr.index') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Search employee, employee ID…" aria-label="Search employees">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="submit" class="btn btn--primary">Search</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-qr-clear">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($employees->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <h2 class="aa-empty__title">No employees found</h2>
                    <p class="aa-empty__text">No employees match your search criteria.</p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Employee ID</th>
                            <th scope="col">Department</th>
                            <th scope="col">Position</th>
                            <th scope="col">QR Status</th>
                            <th scope="col">QR Code</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($employees as $e)
                            <tr>
                                <td><span class="aa-cell-primary">{{ $e->displayName() }}</span></td>
                                <td>{{ $e->employee_id }}</td>
                                <td>{{ $e->department ?? '—' }}</td>
                                <td>{{ $e->position ?? '—' }}</td>
                                <td>
                                    @if($e->activeQrCode)
                                        @include('partials.attendance-status-badge', ['status' => 'active'])
                                    @else
                                        @include('partials.attendance-status-badge', ['status' => 'none', 'label' => 'No QR Code'])
                                    @endif
                                </td>
                                <td><code style="font-size:.85rem;">{{ $e->activeQrCode?->code ?? '—' }}</code></td>
                                <td>
                                    <a href="{{ route('attendance.qr.show', $e) }}" class="btn btn--secondary btn--sm">Manage QR</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($employees as $e)
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $e->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $e->employee_id }}</div>
                                </div>
                                @if($e->activeQrCode)
                                    @include('partials.attendance-status-badge', ['status' => 'active'])
                                @else
                                    @include('partials.attendance-status-badge', ['status' => 'none', 'label' => 'No QR Code'])
                                @endif
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Department</span> {{ $e->department ?? '—' }}</div>
                                <div><span class="aa-card-row__label">Position</span> {{ $e->position ?? '—' }}</div>
                                @if($e->activeQrCode)
                                    <div><span class="aa-card-row__label">QR Code</span> <code>{{ $e->activeQrCode->code }}</code></div>
                                @endif
                            </div>
                            <div class="aa-card-row__actions">
                                <a href="{{ route('attendance.qr.show', $e) }}" class="btn btn--primary btn--block">Manage QR</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $employees->withQueryString()])
            @endif
        </div>
    </div>
</div>
@endsection
