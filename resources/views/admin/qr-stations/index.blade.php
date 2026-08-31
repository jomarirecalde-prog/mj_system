@extends('layouts.app')

@section('title', 'QR Scanner Stations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qr-stations.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/qr-stations.js') }}" defer></script>
@endpush

@section('content')
<div class="qs-module">
    <div aria-live="polite" aria-atomic="true" class="qs-live-region" id="qs-live-region"></div>

    <header class="qs-page-header">
        <div class="qs-page-header__left">
            <span class="qs-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </span>
            <div>
                <h1 class="qs-page-header__title">QR Scanner Stations</h1>
                <p class="qs-page-header__desc">Manage dedicated attendance scanning stations and authorized devices.</p>
            </div>
        </div>
        <div class="qs-page-header__actions">
            <button type="button" class="btn btn--primary" id="open-create-station">
                + Create Station
            </button>
        </div>
    </header>

    @if(session('generated_password'))
        <div class="qs-password-alert" role="status">
            <strong>New station password:</strong>
            <code id="generated-password-value">{{ session('generated_password') }}</code>
            <button type="button" class="btn btn--ghost btn--sm js-qs-copy-password" data-target="generated-password-value">Copy</button>
        </div>
    @endif

    @isset($stats)
        <div class="qs-summary" aria-label="Station summary">
            <div class="qs-summary__card">
                <div class="qs-summary__label">Total Stations</div>
                <div class="qs-summary__value">{{ $stats->total ?? 0 }}</div>
            </div>
            <div class="qs-summary__card qs-summary__card--ok">
                <div class="qs-summary__label">Active Stations</div>
                <div class="qs-summary__value">{{ $stats->active ?? 0 }}</div>
            </div>
            <div class="qs-summary__card qs-summary__card--muted">
                <div class="qs-summary__label">Inactive Stations</div>
                <div class="qs-summary__value">{{ $stats->inactive ?? 0 }}</div>
            </div>
            <div class="qs-summary__card qs-summary__card--accent">
                <div class="qs-summary__label">Authorized Devices</div>
                <div class="qs-summary__value">{{ $stats->authorized ?? 0 }}</div>
            </div>
            <div class="qs-summary__card">
                <div class="qs-summary__label">Available Stations</div>
                <div class="qs-summary__value">{{ ($stats->total ?? 0) - ($stats->authorized ?? 0) }}</div>
            </div>
        </div>
    @endisset

    <div class="card qs-filters">
        <div class="card__body">
            <form method="get" action="{{ route('admin.qr-stations.index') }}" id="qs-filters-form">
                <div class="qs-filters__bar">
                    <div class="qs-search">
                        <svg class="qs-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="qs-search__input" placeholder="Search station, ID, location…" value="{{ request('search') }}" aria-label="Search stations">
                    </div>
                    <div class="qs-filters__field">
                        <label class="form-label" for="filter-status">Status</label>
                        <select name="status" id="filter-status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="qs-filters__actions">
                        <button type="submit" class="btn btn--secondary">Filter</button>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.qr-stations.index') }}" class="btn btn--ghost">Clear</a>
                        @endif
                    </div>
                    <button type="button" class="btn btn--secondary qs-filters__mobile-toggle" id="qs-filters-mobile-toggle" aria-expanded="false" aria-controls="qs-filters-drawer">Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="qs-filters__drawer-backdrop" id="qs-filters-drawer-backdrop" aria-hidden="true"></div>
    <div class="qs-filters__drawer" id="qs-filters-drawer" role="dialog" aria-label="Filter stations">
        <h2 class="qs-filters__drawer-title">Filters</h2>
        <div class="form-group">
            <label class="form-label" for="mobile-search">Search</label>
            <input type="search" id="mobile-search" class="form-control" data-qs-sync="search" value="{{ request('search') }}" placeholder="Search station, ID, location…">
        </div>
        <div class="form-group">
            <label class="form-label" for="mobile-status">Status</label>
            <select id="mobile-status" class="form-select" data-qs-sync="status">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button type="button" class="btn btn--primary btn--block" id="qs-filters-mobile-apply">Apply Filters</button>
    </div>

    <div class="card">
        @if($stations->isEmpty())
            <div class="qs-empty">
                <p class="qs-empty__title">No QR stations yet</p>
                <p>Create a station to authorize dedicated attendance scanning devices.</p>
                <button type="button" class="btn btn--primary mt-2" onclick="document.getElementById('open-create-station')?.click()">+ Create Station</button>
            </div>
        @else
            <div class="qs-table-wrap qs-table-desktop">
                <table class="qs-table">
                    <thead>
                        <tr>
                            <th scope="col">Station</th>
                            <th scope="col">Station ID</th>
                            <th scope="col">Location</th>
                            <th scope="col">Device</th>
                            <th scope="col">Device Status</th>
                            <th scope="col">Last Activity</th>
                            <th scope="col">Station Status</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stations as $station)
                            @php $device = $station->authorizedDevice; @endphp
                            <tr>
                                <td>
                                    <span class="qs-cell-primary">{{ $station->station_name }}</span>
                                </td>
                                <td><code class="qs-code">{{ $station->station_code }}</code></td>
                                <td>{{ $station->location }}</td>
                                <td>{{ $device?->displayName() ?? '—' }}</td>
                                <td>@include('partials.qr-station-device-status', ['station' => $station])</td>
                                <td>{{ $station->last_activity_at ? ph_datetime($station->last_activity_at) : '—' }}</td>
                                <td>
                                    <span class="qs-station-status qs-station-status--{{ $station->isActive() ? 'active' : 'inactive' }}">
                                        <span class="qs-station-status__dot" aria-hidden="true"></span>
                                        {{ $station->isActive() ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </td>
                                <td>{{ ph_datetime($station->created_at, 'M j, Y') }}</td>
                                <td>
                                    <div class="qs-table__actions">
                                        <a href="{{ route('admin.qr-stations.show', $station) }}" class="btn btn--ghost btn--sm">Details</a>
                                        <button type="button" class="btn btn--ghost btn--sm js-qs-edit-station" data-station-id="{{ $station->id }}"
                                            data-station="{{ json_encode($station->only(['id', 'station_name', 'station_code', 'location', 'description', 'building', 'department', 'floor_area', 'timezone', 'status'])) }}">Edit</button>
                                        <div class="qs-menu">
                                            <button type="button" class="btn btn--ghost btn--sm qs-menu__toggle" aria-haspopup="true" aria-expanded="false" aria-label="More actions for {{ $station->station_name }}">More</button>
                                            <div class="qs-menu__panel" role="menu">
                                                @if($station->isActive())
                                                    <button type="button" class="qs-menu__item" role="menuitem" data-qs-trigger-modal="confirm-deactivate-{{ $station->id }}">Deactivate</button>
                                                @else
                                                    <button type="button" class="qs-menu__item" role="menuitem" data-qs-trigger-modal="confirm-activate-{{ $station->id }}">Activate</button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="qs-mobile-cards">
                @foreach($stations as $station)
                    @php $device = $station->authorizedDevice; @endphp
                    <article class="qs-card-row">
                        <div class="qs-card-row__head">
                            <div>
                                <div class="qs-cell-primary">{{ $station->station_name }}</div>
                                <code class="qs-code">{{ $station->station_code }}</code>
                            </div>
                            <span class="qs-station-status qs-station-status--{{ $station->isActive() ? 'active' : 'inactive' }}">
                                <span class="qs-station-status__dot" aria-hidden="true"></span>
                                {{ $station->isActive() ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </div>
                        <div class="qs-card-row__grid">
                            <div>
                                <span class="qs-card-row__label">Location</span>
                                {{ $station->location }}
                            </div>
                            <div>
                                <span class="qs-card-row__label">Device</span>
                                {{ $device?->displayName() ?? '—' }}
                            </div>
                            <div>
                                <span class="qs-card-row__label">Device Status</span>
                                @include('partials.qr-station-device-status', ['station' => $station])
                            </div>
                            <div>
                                <span class="qs-card-row__label">Last Activity</span>
                                {{ $station->last_activity_at ? ph_datetime($station->last_activity_at) : '—' }}
                            </div>
                        </div>
                        <div class="qs-card-row__actions">
                            <a href="{{ route('admin.qr-stations.show', $station) }}" class="btn btn--ghost btn--sm">Details</a>
                            <button type="button" class="btn btn--ghost btn--sm js-qs-edit-station" data-station-id="{{ $station->id }}"
                                data-station="{{ json_encode($station->only(['id', 'station_name', 'station_code', 'location', 'description', 'building', 'department', 'floor_area', 'timezone', 'status'])) }}">Edit</button>
                            @if($station->isActive())
                                <button type="button" class="btn btn--ghost btn--sm" data-qs-trigger-modal="confirm-deactivate-{{ $station->id }}">Deactivate</button>
                            @else
                                <button type="button" class="btn btn--ghost btn--sm" data-qs-trigger-modal="confirm-activate-{{ $station->id }}">Activate</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if($stations->hasPages())
            <div class="card__body pt-0">@include('partials.pagination', ['paginator' => $stations])</div>
        @endif
    </div>
</div>

{{-- Per-station activate/deactivate modals --}}
@foreach($stations as $station)
    <dialog class="qs-modal" id="confirm-deactivate-{{ $station->id }}">
        <form method="post" action="{{ route('admin.qr-stations.deactivate', $station) }}" class="qs-modal__form" data-qs-submit>
            @csrf @method('PATCH')
            <div class="qs-modal__header">
                <h2 class="qs-modal__title">Deactivate Station?</h2>
                <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
            </div>
            <div class="qs-modal__body">
                <p class="qs-modal__note">Devices will no longer be able to use <strong>{{ $station->station_name }}</strong> while it is inactive.</p>
            </div>
            <div class="qs-modal__footer">
                <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
                <button type="submit" class="btn btn--primary" data-qs-loading-text="Processing…">Deactivate</button>
            </div>
        </form>
    </dialog>
    <dialog class="qs-modal" id="confirm-activate-{{ $station->id }}">
        <form method="post" action="{{ route('admin.qr-stations.activate', $station) }}" class="qs-modal__form" data-qs-submit>
            @csrf @method('PATCH')
            <div class="qs-modal__header">
                <h2 class="qs-modal__title">Activate Station?</h2>
                <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
            </div>
            <div class="qs-modal__body">
                <p class="qs-modal__note">Re-enable <strong>{{ $station->station_name }}</strong> for attendance scanning.</p>
            </div>
            <div class="qs-modal__footer">
                <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
                <button type="submit" class="btn btn--primary" data-qs-loading-text="Processing…">Activate</button>
            </div>
        </form>
    </dialog>
@endforeach

{{-- Create modal --}}
<dialog class="qs-modal" id="create-station-modal">
    <form method="post" action="{{ route('admin.qr-stations.store') }}" class="qs-modal__form" data-qs-submit>
        @csrf
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Create QR Station</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            @include('admin.qr-stations._form', ['station' => null, 'showExtendedFields' => true, 'departments' => $departments])
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" data-qs-loading-text="Creating…">Create Station</button>
        </div>
    </form>
</dialog>

{{-- Edit modal --}}
<dialog class="qs-modal" id="edit-station-modal">
    <form method="post" id="edit-station-form" class="qs-modal__form" data-qs-submit>
        @csrf
        @method('PUT')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Edit Station</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body" id="edit-station-body"></div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" data-qs-loading-text="Saving…">Save Changes</button>
        </div>
    </form>
</dialog>
@endsection

@php
    $editFormTemplate = view('admin.qr-stations._form', [
        'station' => null,
        'showExtendedFields' => true,
        'departments' => $departments,
    ])->render();
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-qs-trigger-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById(btn.dataset.qsTriggerModal)?.showModal();
        });
    });

    window.QrStations.initIndex({
        formTemplate: @json($editFormTemplate),
        updateBaseUrl: @json(url('admin/qr-stations')),
        openCreateOnError: @json($errors->any() && !request()->has('edit')),
        editId: @json(request('edit')),
    });
});
</script>
@endpush
