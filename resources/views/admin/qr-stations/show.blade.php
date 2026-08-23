@extends('layouts.app')

@section('title', $station->station_name)

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $station->station_name }}</h1>
        <p class="page-header__meta"><code>{{ $station->station_code }}</code> · {{ $station->location }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('admin.qr-stations.index') }}" class="btn btn--ghost">← All Stations</a>
        <button type="button" class="btn btn--secondary js-edit-inline">Edit Station</button>
    </div>
</div>

@if(session('generated_password'))
    <div class="alert alert--warning mb-2">
        <strong>New station password:</strong>
        <code>{{ session('generated_password') }}</code>
    </div>
@endif

<div class="stat-grid mb-2">
    <div class="stat-card">
        <div class="stat-card__label">Station Status</div>
        <div class="stat-card__value">{{ $station->isActive() ? 'Active' : 'Inactive' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Device Status</div>
        <div class="stat-card__value">{{ $station->deviceStatusLabel() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Last Activity</div>
        <div class="stat-card__value" style="font-size:1rem">{{ $station->last_activity_at ? ph_datetime($station->last_activity_at) : '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Created</div>
        <div class="stat-card__value" style="font-size:1rem">{{ ph_datetime($station->created_at) }}</div>
    </div>
</div>

<div class="grid-2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Station Details</h2></div>
        <div class="card__body">
            <dl class="dl-list">
                <div class="dl-item"><dt>Station Name</dt><dd>{{ $station->station_name }}</dd></div>
                <div class="dl-item"><dt>Station ID</dt><dd><code>{{ $station->station_code }}</code></dd></div>
                <div class="dl-item"><dt>Location</dt><dd>{{ $station->location }}</dd></div>
                @if($station->building)<div class="dl-item"><dt>Building</dt><dd>{{ $station->building }}</dd></div>@endif
                @if($station->department)<div class="dl-item"><dt>Department</dt><dd>{{ $station->department }}</dd></div>@endif
                @if($station->floor_area)<div class="dl-item"><dt>Floor / Area</dt><dd>{{ $station->floor_area }}</dd></div>@endif
                <div class="dl-item"><dt>Timezone</dt><dd>{{ $station->timezone }}</dd></div>
                @if($station->description)<div class="dl-item"><dt>Notes</dt><dd>{{ $station->description }}</dd></div>@endif
                @if($station->creator)<div class="dl-item"><dt>Created By</dt><dd>{{ $station->creator->displayName() }}</dd></div>@endif
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Authorized Device</h2></div>
        <div class="card__body">
            @if($station->authorizedDevice && $station->authorizedDevice->isAuthorized())
                <dl class="dl-list">
                    <div class="dl-item"><dt>Device</dt><dd>{{ $station->authorizedDevice->displayName() }}</dd></div>
                    <div class="dl-item"><dt>Identifier</dt><dd><code style="font-size:.75rem">{{ $station->authorizedDevice->device_identifier }}</code></dd></div>
                    <div class="dl-item"><dt>IP Address</dt><dd>{{ $station->authorizedDevice->ip_address ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Authorized</dt><dd>{{ $station->authorized_at ? ph_datetime($station->authorized_at) : '—' }}</dd></div>
                    <div class="dl-item"><dt>Last Activity</dt><dd>{{ $station->authorizedDevice->last_activity_at ? ph_datetime($station->authorizedDevice->last_activity_at) : '—' }}</dd></div>
                </dl>
            @else
                <p class="text-muted mb-0">No device is currently authorized. The next successful station login will bind a device.</p>
            @endif

            <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:0.5rem">
                @if($station->isActive())
                    <form method="post" action="{{ route('admin.qr-stations.deactivate', $station) }}">@csrf @method('PATCH')<button type="submit" class="btn btn--ghost btn--sm">Deactivate Station</button></form>
                @else
                    <form method="post" action="{{ route('admin.qr-stations.activate', $station) }}">@csrf @method('PATCH')<button type="submit" class="btn btn--secondary btn--sm">Activate Station</button></form>
                @endif

                @if($station->hasAuthorizedDevice())
                    <button type="button" class="btn btn--ghost btn--sm" data-confirm-modal="reset-device">Reset Device</button>
                    <button type="button" class="btn btn--ghost btn--sm" data-confirm-modal="revoke-device">Revoke Device Access</button>
                @endif

                <form method="post" action="{{ route('admin.qr-stations.regenerate-password', $station) }}">@csrf @method('PATCH')<button type="submit" class="btn btn--ghost btn--sm">Change Password</button></form>

                <button type="button" class="btn btn--ghost btn--sm text-danger" data-confirm-modal="delete-station">Delete Station</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header"><h2 class="card__title">Activity Log</h2></div>
    <div class="table-wrap">
        <table class="table table--compact">
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Performed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($station->activityLogs as $log)
                    <tr>
                        <td>{{ ph_datetime($log->created_at) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->performer?->displayName() ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">No activity recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<dialog class="modal confirm-modal" id="confirm-reset-device">
    <form method="post" action="{{ route('admin.qr-stations.reset-device', $station) }}">
        @csrf @method('PATCH')
        <div class="modal__header"><h2 class="modal__title">Reset Device Authorization</h2></div>
        <div class="modal__body">
            <p>The currently registered device will lose access immediately:</p>
            <p><strong>{{ $station->authorizedDevice?->displayName() ?? 'Unknown device' }}</strong></p>
            <p class="text-muted">The station will become available for a new device on the next login.</p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary">Confirm Reset</button>
        </div>
    </form>
</dialog>

<dialog class="modal confirm-modal" id="confirm-revoke-device">
    <form method="post" action="{{ route('admin.qr-stations.revoke-device', $station) }}">
        @csrf @method('PATCH')
        <div class="modal__header"><h2 class="modal__title">Revoke Device Access</h2></div>
        <div class="modal__body">
            <p>This will revoke the authorized device and remove its access token. The station must log in again on a new device.</p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary">Revoke Access</button>
        </div>
    </form>
</dialog>

<dialog class="modal confirm-modal" id="confirm-delete-station">
    <form method="post" action="{{ route('admin.qr-stations.destroy', $station) }}">
        @csrf @method('DELETE')
        <div class="modal__header"><h2 class="modal__title">Delete Station</h2></div>
        <div class="modal__body">
            <p>Permanently delete <strong>{{ $station->station_name }}</strong>? This cannot be undone.</p>
        </div>
        <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" style="background:#dc2626;border-color:#dc2626">Delete Station</button>
        </div>
    </form>
</dialog>
@endsection

@push('styles')
<style>
.grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.modal { border: 0; border-radius: 14px; padding: 0; max-width: 480px; width: calc(100% - 2rem); }
.modal::backdrop { background: rgba(15,23,42,.45); }
.modal__header, .modal__footer { padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; }
.modal__body { padding: 0 1.25rem 1rem; }
.modal__title { margin: 0; font-size: 1.125rem; }
.modal__footer { justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-confirm-modal]').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('confirm-' + btn.dataset.confirmModal)?.showModal());
});
document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('dialog')?.close());
});
document.querySelector('.js-edit-inline')?.addEventListener('click', () => {
    window.location.href = @json(route('admin.qr-stations.index')) + '?edit={{ $station->id }}';
});
</script>
@endpush
