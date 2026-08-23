@extends('layouts.app')

@section('title', 'QR Scanner Stations')

@section('content')
<div class="page-header">
    <div>
        <h1>QR Scanner Stations</h1>
        <p class="page-header__meta">Manage dedicated attendance scanning stations · one device per station</p>
    </div>
    <div class="page-header__actions">
        <button type="button" class="btn btn--primary" id="open-create-station">Create Station</button>
    </div>
</div>

@if(session('generated_password'))
    <div class="alert alert--warning mb-2">
        <strong>New station password:</strong>
        <code id="generated-password-value">{{ session('generated_password') }}</code>
        <button type="button" class="btn btn--ghost btn--sm ml-1" onclick="navigator.clipboard.writeText(document.getElementById('generated-password-value').textContent)">Copy</button>
    </div>
@endif

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filter-row">
            <div class="form-group mb-0" style="flex:1;min-width:180px">
                <input type="search" name="search" class="form-control" placeholder="Search name, ID, location…" value="{{ request('search') }}">
            </div>
            <div class="form-group mb-0" style="min-width:140px">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.qr-stations.index') }}" class="btn btn--ghost">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Station Name</th>
                    <th>Station ID</th>
                    <th>Location</th>
                    <th>Assigned Device</th>
                    <th>Device Status</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stations as $station)
                    @php
                        $device = $station->authorizedDevice;
                        $deviceStatus = $station->deviceStatusLabel();
                    @endphp
                    <tr>
                        <td><strong>{{ $station->station_name }}</strong></td>
                        <td><code>{{ $station->station_code }}</code></td>
                        <td>{{ $station->location }}</td>
                        <td>{{ $device?->displayName() ?? '—' }}</td>
                        <td>
                            @if($deviceStatus === 'Authorized')
                                <span class="badge badge--success">Authorized</span>
                            @elseif($deviceStatus === 'Revoked')
                                <span class="badge badge--danger">Revoked</span>
                            @else
                                <span class="badge badge--muted">Unassigned</span>
                            @endif
                        </td>
                        <td>{{ $station->last_activity_at ? ph_datetime($station->last_activity_at) : '—' }}</td>
                        <td>
                            @if($station->isActive())
                                <span class="badge badge--success">Active</span>
                            @else
                                <span class="badge badge--muted">Inactive</span>
                            @endif
                        </td>
                        <td>{{ ph_datetime($station->created_at, 'M j, Y') }}</td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.qr-stations.show', $station) }}" class="btn btn--ghost btn--sm">Details</a>
                                <button type="button" class="btn btn--ghost btn--sm js-edit-station"
                                    data-station="{{ json_encode($station->only(['id', 'station_name', 'station_code', 'location', 'description', 'building', 'department', 'floor_area', 'timezone', 'status'])) }}">Edit</button>
                                @if($station->isActive())
                                    <form method="post" action="{{ route('admin.qr-stations.deactivate', $station) }}" class="inline-form">@csrf @method('PATCH')<button type="submit" class="btn btn--ghost btn--sm">Deactivate</button></form>
                                @else
                                    <form method="post" action="{{ route('admin.qr-stations.activate', $station) }}" class="inline-form">@csrf @method('PATCH')<button type="submit" class="btn btn--ghost btn--sm">Activate</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">No QR stations yet. Create one to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stations->hasPages())
        <div class="card__body pt-0">@include('partials.pagination', ['paginator' => $stations])</div>
    @endif
</div>

{{-- Create modal --}}
<dialog class="modal" id="create-station-modal">
    <form method="post" action="{{ route('admin.qr-stations.store') }}" class="modal__form">
        @csrf
        <div class="modal__header">
            <h2 class="modal__title">Create QR Station</h2>
            <button type="button" class="modal__close" data-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            @include('admin.qr-stations._form', ['station' => null])
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary">Create Station</button>
        </div>
    </form>
</dialog>

{{-- Edit modal --}}
<dialog class="modal" id="edit-station-modal">
    <form method="post" id="edit-station-form" class="modal__form">
        @csrf
        @method('PUT')
        <div class="modal__header">
            <h2 class="modal__title">Edit Station</h2>
            <button type="button" class="modal__close" data-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="modal__body" id="edit-station-body"></div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.filter-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
.table-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: flex-end; }
.inline-form { display: inline; }
.modal { border: 0; border-radius: 14px; padding: 0; max-width: 560px; width: calc(100% - 2rem); box-shadow: 0 24px 48px rgba(15,23,42,.18); }
.modal::backdrop { background: rgba(15,23,42,.45); }
.modal__form { display: flex; flex-direction: column; max-height: 90vh; }
.modal__header, .modal__footer { padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
.modal__header { border-bottom: 1px solid var(--border); }
.modal__footer { border-top: 1px solid var(--border); justify-content: flex-end; }
.modal__body { padding: 1.25rem; overflow: auto; }
.modal__title { margin: 0; font-size: 1.125rem; }
.modal__close { border: 0; background: transparent; font-size: 1.5rem; line-height: 1; cursor: pointer; color: var(--muted); }
.pw-field { display: flex; gap: 0.5rem; align-items: stretch; }
.pw-field .form-control { flex: 1; }
.confirm-modal { max-width: 480px; }
</style>
@endpush

@php
    $editFormTemplate = view('admin.qr-stations._form', [
        'station' => null,
        'showExtendedFields' => true,
        'departments' => $departments,
    ])->render();
@endphp

@push('scripts')
<script>
(function () {
    const createModal = document.getElementById('create-station-modal');
    const editModal = document.getElementById('edit-station-modal');
    const editForm = document.getElementById('edit-station-form');
    const editBody = document.getElementById('edit-station-body');
    const formTemplate = @json($editFormTemplate);

    document.getElementById('open-create-station')?.addEventListener('click', () => createModal.showModal());

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('dialog')?.close());
    });

    document.querySelectorAll('.js-edit-station').forEach(btn => {
        btn.addEventListener('click', () => {
            const station = JSON.parse(btn.dataset.station);
            editForm.action = @json(url('admin/qr-stations')) + '/' + station.id;
            editBody.innerHTML = formTemplate;
            Object.entries(station).forEach(([key, val]) => {
                const field = editBody.querySelector('[name="' + key + '"]');
                if (field) field.value = val ?? '';
            });
            const pw = editBody.querySelector('[name="password"]');
            if (pw) { pw.value = ''; pw.placeholder = 'Leave blank to keep current password'; pw.removeAttribute('required'); }
            editModal.showModal();
        });
    });

    document.addEventListener('click', async e => {
        const gen = e.target.closest('.js-generate-password');
        if (!gen) return;
        e.preventDefault();
        const target = document.getElementById(gen.dataset.target);
        const res = await fetch(@json(route('admin.qr-stations.generate-password')), { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
        const data = await res.json();
        if (data.password && target) {
            target.value = data.password;
            target.type = 'text';
        }
    });

    document.addEventListener('click', e => {
        const toggle = e.target.closest('.js-toggle-password');
        if (!toggle) return;
        e.preventDefault();
        const input = document.getElementById(toggle.dataset.target);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Hide' : 'Show';
    });
})();
</script>
@endpush
