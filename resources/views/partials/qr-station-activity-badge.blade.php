@php
    $actionLabels = [
        'device_authorized' => ['label' => 'Device Authorized', 'class' => 'qs-log-badge--success', 'icon' => '✓'],
        'device_reset' => ['label' => 'Device Reset', 'class' => 'qs-log-badge--warn', 'icon' => '↺'],
        'device_revoked' => ['label' => 'Device Revoked', 'class' => 'qs-log-badge--danger', 'icon' => '✕'],
        'device_login' => ['label' => 'Device Login', 'class' => 'qs-log-badge--muted', 'icon' => '→'],
        'device_logout' => ['label' => 'Device Logout', 'class' => 'qs-log-badge--muted', 'icon' => '←'],
        'password_changed' => ['label' => 'Password Changed', 'class' => 'qs-log-badge--warn', 'icon' => '🔑'],
        'station_activated' => ['label' => 'Station Activated', 'class' => 'qs-log-badge--success', 'icon' => '●'],
        'station_deactivated' => ['label' => 'Station Deactivated', 'class' => 'qs-log-badge--muted', 'icon' => '○'],
        'station_created' => ['label' => 'Station Created', 'class' => 'qs-log-badge--success', 'icon' => '+'],
        'station_updated' => ['label' => 'Station Updated', 'class' => 'qs-log-badge--muted', 'icon' => '✎'],
        'station_deleted' => ['label' => 'Station Deleted', 'class' => 'qs-log-badge--danger', 'icon' => '✕'],
    ];
    $meta = $actionLabels[$action] ?? [
        'label' => str_replace('_', ' ', ucfirst($action)),
        'class' => 'qs-log-badge--muted',
        'icon' => '•',
    ];
@endphp
<span class="qs-log-badge {{ $meta['class'] }}">
    <span class="qs-log-badge__icon" aria-hidden="true">{{ $meta['icon'] }}</span>
    {{ $meta['label'] }}
</span>
