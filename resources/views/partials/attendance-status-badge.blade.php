@php
    $statusKey = strtolower((string) ($status ?? ''));
    $config = match ($statusKey) {
        'pending' => ['class' => 'aa-badge--pending', 'icon' => '●', 'label' => 'Pending'],
        'approved' => ['class' => 'aa-badge--approved', 'icon' => '✓', 'label' => 'Approved'],
        'rejected' => ['class' => 'aa-badge--rejected', 'icon' => '✕', 'label' => 'Rejected'],
        'active' => ['class' => 'aa-badge--active', 'icon' => '✓', 'label' => 'Active'],
        'inactive' => ['class' => 'aa-badge--inactive', 'icon' => '○', 'label' => 'Inactive'],
        'disabled' => ['class' => 'aa-badge--inactive', 'icon' => '○', 'label' => 'Disabled'],
        'none', 'no qr', 'no qr code' => ['class' => 'aa-badge--none', 'icon' => '○', 'label' => $label ?? 'No QR Code'],
        default => ['class' => 'aa-badge--default', 'icon' => '', 'label' => $label ?? ucfirst($statusKey ?: '—')],
    };
    $displayLabel = $label ?? $config['label'];
@endphp
<span class="aa-badge {{ $config['class'] }}" @if(!empty($ariaLabel)) aria-label="{{ $ariaLabel }}" @endif>
    @if($config['icon'] !== '')
        <span class="aa-badge__icon" aria-hidden="true">{{ $config['icon'] }}</span>
    @endif
    <span class="aa-badge__text">{{ $displayLabel }}</span>
</span>
