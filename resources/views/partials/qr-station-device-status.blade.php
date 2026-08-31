@php
    $label = $label ?? ($station->deviceStatusLabel() ?? 'Unassigned');
    $key = strtolower($label);
    $icons = [
        'authorized' => '✓',
        'unassigned' => '○',
        'revoked' => '✕',
    ];
    $descriptions = [
        'authorized' => 'One device is currently authorized.',
        'unassigned' => 'No device is currently authorized.',
        'revoked' => 'Device access has been revoked.',
    ];
@endphp
<span class="qs-device-status qs-device-status--{{ $key }}" role="status">
    <span class="qs-device-status__icon" aria-hidden="true">{{ $icons[$key] ?? '○' }}</span>
    <span class="qs-device-status__text">
        <span class="qs-device-status__label">{{ strtoupper($label) }}</span>
        @if($showDescription ?? false)
            <span class="qs-device-status__desc">{{ $descriptions[$key] ?? '' }}</span>
        @endif
    </span>
</span>
