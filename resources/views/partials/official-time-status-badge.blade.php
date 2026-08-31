@php
    $statusKey = strtolower((string) ($status ?? ''));
    $config = match ($statusKey) {
        'pending' => ['class' => 'ot-badge--pending', 'icon' => '●', 'label' => 'Pending Approval'],
        'approved' => ['class' => 'ot-badge--approved', 'icon' => '✓', 'label' => 'Approved'],
        'rejected' => ['class' => 'ot-badge--rejected', 'icon' => '✕', 'label' => 'Rejected'],
        'cancelled' => ['class' => 'ot-badge--cancelled', 'icon' => '○', 'label' => 'Cancelled'],
        default => ['class' => 'ot-badge--default', 'icon' => '', 'label' => $label ?? ucfirst($statusKey ?: '—')],
    };
    $displayLabel = $label ?? $config['label'];
@endphp
<span class="ot-badge {{ $config['class'] }}">
    @if($config['icon'] !== '')
        <span class="ot-badge__icon" aria-hidden="true">{{ $config['icon'] }}</span>
    @endif
    <span class="ot-badge__text">{{ $displayLabel }}</span>
</span>
