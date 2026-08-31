@extends('layouts.app')

@section('title', $station->station_name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qr-stations.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/qr-stations.js') }}" defer></script>
@endpush

@section('content')
@php
    $device = $station->authorizedDevice;
    $deviceAuthorized = $device && $device->isAuthorized();
    $deviceStatus = $station->deviceStatusLabel();
    $justReset = session('success') && str_contains(session('success'), 'Device authorization reset');
@endphp

<div class="qs-module">
    <div aria-live="polite" aria-atomic="true" class="qs-live-region" id="qs-live-region">
        @if(session('success')){{ session('success') }}@endif
    </div>

    <header class="qs-page-header">
        <div class="qs-page-header__left">
            <span class="qs-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </span>
            <div>
                <p class="qs-page-header__desc" style="margin:0 0 0.15rem;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">QR Scanner Station</p>
                <h1 class="qs-page-header__title">{{ $station->station_name }}</h1>
                <div class="qs-page-header__meta">
                    <code>{{ $station->station_code }}</code>
                    <span aria-hidden="true">·</span>
                    <span>{{ $station->location }}</span>
                    <span class="qs-station-status qs-station-status--{{ $station->isActive() ? 'active' : 'inactive' }}">
                        <span class="qs-station-status__dot" aria-hidden="true"></span>
                        {{ $station->isActive() ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="qs-page-header__actions">
            <a href="{{ route('admin.qr-stations.index') }}" class="btn btn--ghost">Back to Stations</a>
            <button type="button" class="btn btn--secondary js-qs-edit-inline">Edit Station</button>
        </div>
    </header>

    @if($justReset)
        <div class="qs-success-banner" role="status">
            <span class="qs-success-banner__icon" aria-hidden="true">✓</span>
            <div>
                <p class="qs-success-banner__title">Device authorization reset</p>
                <p class="qs-success-banner__text">The station is now available for a new device.</p>
            </div>
        </div>
    @endif

    @if(session('generated_password'))
        <div class="qs-password-alert" role="status">
            <strong>New station password:</strong>
            <code id="generated-password-value">{{ session('generated_password') }}</code>
            <button type="button" class="btn btn--ghost btn--sm js-qs-copy-password" data-target="generated-password-value">Copy</button>
            <span class="form-hint" style="flex:1 1 100%;margin:0;">Copy this password now — it will not be shown again. Existing authorized devices remain connected until reset or revoked.</span>
        </div>
    @endif

    <div class="qs-status-grid">
        <div class="qs-status-card">
            <div class="qs-status-card__label">Station Status</div>
            <div class="qs-status-card__value">
                <span class="qs-station-status qs-station-status--{{ $station->isActive() ? 'active' : 'inactive' }}">
                    <span class="qs-station-status__dot" aria-hidden="true"></span>
                    {{ $station->isActive() ? 'ACTIVE' : 'INACTIVE' }}
                </span>
            </div>
        </div>
        <div class="qs-status-card">
            <div class="qs-status-card__label">Device Status</div>
            <div class="qs-status-card__value">
                @include('partials.qr-station-device-status', ['station' => $station, 'showDescription' => false])
            </div>
        </div>
        <div class="qs-status-card">
            <div class="qs-status-card__label">Last Activity</div>
            <div class="qs-status-card__value">{{ $station->last_activity_at ? ph_datetime($station->last_activity_at) : '—' }}</div>
        </div>
        <div class="qs-status-card">
            <div class="qs-status-card__label">Created</div>
            <div class="qs-status-card__value">{{ ph_datetime($station->created_at) }}</div>
        </div>
    </div>

    <div class="qs-detail-grid">
        <div class="card">
            <div class="card__header"><h2 class="card__title">Station Details</h2></div>
            <div class="card__body">
                <dl class="qs-dl">
                    <div class="qs-dl__item"><dt>Station Name</dt><dd>{{ $station->station_name }}</dd></div>
                    <div class="qs-dl__item"><dt>Station ID</dt><dd><code class="qs-code">{{ $station->station_code }}</code></dd></div>
                    <div class="qs-dl__item"><dt>Location</dt><dd>{{ $station->location }}</dd></div>
                    @if($station->building)<div class="qs-dl__item"><dt>Building</dt><dd>{{ $station->building }}</dd></div>@endif
                    @if($station->department)<div class="qs-dl__item"><dt>Department</dt><dd>{{ $station->department }}</dd></div>@endif
                    @if($station->floor_area)<div class="qs-dl__item"><dt>Floor / Area</dt><dd>{{ $station->floor_area }}</dd></div>@endif
                    <div class="qs-dl__item"><dt>Timezone</dt><dd>{{ $station->timezone }}</dd></div>
                    @if($station->description)<div class="qs-dl__item"><dt>Notes</dt><dd>{{ $station->description }}</dd></div>@endif
                    @if($station->creator)<div class="qs-dl__item"><dt>Created By</dt><dd>{{ $station->creator->displayName() }}</dd></div>@endif
                </dl>

                <div class="qs-security">
                    <h3 class="qs-security__title">Security</h3>
                    <ul class="qs-security__list">
                        <li><span aria-hidden="true">✓</span> One device per station — Enabled</li>
                        <li><span aria-hidden="true">✓</span> Device authorization — Required</li>
                        <li>
                            <span class="qs-station-status__dot" aria-hidden="true" style="background:{{ $station->isActive() ? 'var(--ok)' : 'var(--muted)' }}"></span>
                            Station status — {{ $station->isActive() ? 'Active' : 'Inactive' }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="qs-device-panel {{ $deviceAuthorized ? 'qs-device-panel--authorized' : 'qs-device-panel--available' }}">
            <div class="qs-device-panel__header">
                <h2 class="qs-device-panel__title">Authorized Device</h2>
                <p class="qs-device-panel__desc">Only one device can be authorized to this station at a time.</p>
            </div>
            <div class="qs-device-panel__body">
                @if($deviceAuthorized)
                    <div class="qs-device-identity">
                        <div class="qs-device-identity__banner" role="status">
                            <span aria-hidden="true">✓</span> DEVICE AUTHORIZED
                        </div>
                        <dl class="qs-dl">
                            <div class="qs-dl__item"><dt>Device</dt><dd>{{ $device->displayName() }}</dd></div>
                            <div class="qs-dl__item"><dt>Device ID</dt><dd><code class="qs-code">{{ $device->device_identifier }}</code></dd></div>
                            <div class="qs-dl__item"><dt>IP Address</dt><dd>{{ $device->ip_address ?? '—' }}</dd></div>
                            <div class="qs-dl__item"><dt>Authorized</dt><dd>{{ $station->authorized_at ? ph_datetime($station->authorized_at) : '—' }}</dd></div>
                            <div class="qs-dl__item"><dt>Last Active</dt><dd>{{ $device->last_activity_at ? ph_datetime($device->last_activity_at) : '—' }}</dd></div>
                        </dl>
                    </div>

                    <div class="qs-device-actions">
                        <p class="qs-device-actions__hint"><strong>Reset Device</strong> — Release this station so another device can authorize itself. The current device will lose access immediately.</p>
                        <button type="button" class="btn btn--primary btn--sm" data-qs-confirm="reset-device">Reset Device</button>
                        <p class="qs-device-actions__hint"><strong>Revoke Access</strong> — Disconnect the current device and record a security revocation. Same result as reset, with an additional audit entry.</p>
                        <button type="button" class="btn btn--ghost btn--sm" data-qs-confirm="revoke-device">Revoke Access</button>
                    </div>
                @else
                    <div class="qs-device-empty">
                        <div class="qs-device-empty__icon" aria-hidden="true">○</div>
                        <p class="qs-device-empty__title">NO DEVICE AUTHORIZED</p>
                        <p class="qs-device-empty__text">
                            @if($justReset || !$station->hasAuthorizedDevice())
                                This station is ready for a new device.
                            @elseif($deviceStatus === 'Revoked')
                                Device access has been revoked. A new device can authorize on next login.
                            @else
                                No device is currently authorized.
                            @endif
                        </p>
                        <div class="qs-device-empty__next">
                            <strong>Next step:</strong> Open the QR Attendance Scanner on the new device and log in using:<br>
                            <strong>Station ID:</strong> <code class="qs-code">{{ $station->station_code }}</code><br>
                            <strong>Station Password:</strong> (provided by administrator)
                        </div>
                    </div>
                @endif

                <div class="qs-device-actions">
                    @if($station->isActive())
                        <button type="button" class="btn btn--ghost btn--sm" data-qs-confirm="deactivate-station">Deactivate Station</button>
                    @else
                        <button type="button" class="btn btn--secondary btn--sm" data-qs-confirm="activate-station">Activate Station</button>
                    @endif
                    <button type="button" class="btn btn--ghost btn--sm" data-qs-confirm="change-password">Change Password</button>
                    <button type="button" class="btn btn--ghost btn--sm text-danger" data-qs-confirm="delete-station">Delete Station</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Activity Log</h2></div>
        <div class="qs-table-wrap">
            <table class="qs-table">
                <thead>
                    <tr>
                        <th scope="col">Date &amp; Time</th>
                        <th scope="col">Action</th>
                        <th scope="col">Description</th>
                        <th scope="col">Performed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($station->activityLogs as $log)
                        <tr>
                            <td>{{ ph_datetime($log->created_at) }}</td>
                            <td>@include('partials.qr-station-activity-badge', ['action' => $log->action])</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->performer?->displayName() ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Reset Device modal --}}
<dialog class="qs-modal" id="confirm-reset-device">
    <form method="post" action="{{ route('admin.qr-stations.reset-device', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('PATCH')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Reset Station Device?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <div class="qs-modal__warn" role="alert">
                <span class="qs-modal__warn-icon" aria-hidden="true">!</span>
                <span>This will disconnect the currently authorized device from this station.</span>
            </div>
            <dl class="qs-modal__meta">
                <div><dt>Station</dt><dd>{{ $station->station_name }}</dd></div>
                <div><dt>Current Device</dt><dd>{{ $device?->displayName() ?? 'Unknown device' }}</dd></div>
            </dl>
            <p class="qs-modal__note">After reset, this station will be available for authorization on another device.</p>
            <p class="qs-modal__note qs-modal__note--security">The current device will no longer be authorized to use this station.</p>
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" style="background:var(--danger);border-color:var(--danger)" data-qs-loading-text="Resetting…" data-qs-modal-focus>Reset Device</button>
        </div>
    </form>
</dialog>

{{-- Revoke Device modal --}}
<dialog class="qs-modal" id="confirm-revoke-device">
    <form method="post" action="{{ route('admin.qr-stations.revoke-device', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('PATCH')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Revoke Device Access?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <div class="qs-modal__warn" role="alert">
                <span class="qs-modal__warn-icon" aria-hidden="true">!</span>
                <span>This will immediately disconnect <strong>{{ $device?->displayName() ?? 'the authorized device' }}</strong> and record a security revocation.</span>
            </div>
            <p class="qs-modal__note">The device will lose scanner access on its next request. A new device can authorize after this action.</p>
            <p class="qs-modal__note">This performs the same disconnection as Reset Device, with an additional revocation audit entry.</p>
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" style="background:var(--danger);border-color:var(--danger)" data-qs-loading-text="Revoking…">Revoke Access</button>
        </div>
    </form>
</dialog>

{{-- Change Password modal --}}
<dialog class="qs-modal" id="confirm-change-password">
    <form method="post" action="{{ route('admin.qr-stations.regenerate-password', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('PATCH')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Change Station Password?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <p class="qs-modal__note">A new password will be generated for <strong>{{ $station->station_name }}</strong>.</p>
            <p class="qs-modal__note">Changing the station password affects <strong>future</strong> station logins. Currently authorized devices remain connected until reset or revoked.</p>
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" data-qs-loading-text="Generating…">Generate New Password</button>
        </div>
    </form>
</dialog>

{{-- Activate / Deactivate modals --}}
<dialog class="qs-modal" id="confirm-deactivate-station">
    <form method="post" action="{{ route('admin.qr-stations.deactivate', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('PATCH')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Deactivate Station?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <p class="qs-modal__note">Devices will no longer be able to use this station while it is inactive.</p>
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" data-qs-loading-text="Processing…">Deactivate</button>
        </div>
    </form>
</dialog>

<dialog class="qs-modal" id="confirm-activate-station">
    <form method="post" action="{{ route('admin.qr-stations.activate', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('PATCH')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Activate Station?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <p class="qs-modal__note">Re-enable this station for attendance scanning.</p>
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" data-qs-loading-text="Processing…">Activate</button>
        </div>
    </form>
</dialog>

{{-- Delete Station modal --}}
<dialog class="qs-modal" id="confirm-delete-station">
    <form method="post" action="{{ route('admin.qr-stations.destroy', $station) }}" class="qs-modal__form" data-qs-submit>
        @csrf @method('DELETE')
        <div class="qs-modal__header">
            <h2 class="qs-modal__title">Delete Station?</h2>
            <button type="button" class="qs-modal__close" data-qs-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="qs-modal__body">
            <div class="qs-modal__warn" role="alert">
                <span class="qs-modal__warn-icon" aria-hidden="true">!</span>
                <span>This permanently removes the station and cannot be undone.</span>
            </div>
            <dl class="qs-modal__meta">
                <div><dt>Station</dt><dd>{{ $station->station_name }}</dd></div>
                <div><dt>Station ID</dt><dd><code class="qs-code">{{ $station->station_code }}</code></dd></div>
            </dl>
            @if($deviceAuthorized)
                <p class="qs-modal__note qs-modal__note--security">This station currently has an authorized device. Deleting will permanently remove all device records.</p>
            @endif
        </div>
        <div class="qs-modal__footer">
            <button type="button" class="btn btn--ghost" data-qs-close-modal>Cancel</button>
            <button type="submit" class="btn btn--primary" style="background:var(--danger);border-color:var(--danger)" data-qs-loading-text="Deleting…">Delete Station</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.js-qs-edit-inline')?.addEventListener('click', function () {
        window.location.href = @json(route('admin.qr-stations.index')) + '?edit={{ $station->id }}';
    });

    window.QrStations.initShow();
});
</script>
@endpush
