@php
    $resultValue = $result ?? '';
    $badgeMap = [
        'success' => ['class' => 'scan-log-badge--success', 'icon' => '✓', 'label' => 'Success'],
        'late' => ['class' => 'scan-log-badge--late', 'icon' => '⚠', 'label' => 'Late'],
        'already_in' => ['class' => 'scan-log-badge--already', 'icon' => '↻', 'label' => 'Already In'],
        'already_out' => ['class' => 'scan-log-badge--already', 'icon' => '↻', 'label' => 'Already Out'],
        'invalid' => ['class' => 'scan-log-badge--invalid', 'icon' => '✕', 'label' => 'Invalid'],
        'inactive' => ['class' => 'scan-log-badge--inactive', 'icon' => '⚠', 'label' => 'Inactive'],
        'cooldown' => ['class' => 'scan-log-badge--cooldown', 'icon' => '⏱', 'label' => 'Cooldown'],
    ];
    $badge = $badgeMap[$resultValue] ?? ['class' => 'scan-log-badge--default', 'icon' => '', 'label' => ucfirst(str_replace('_', ' ', $resultValue))];
@endphp
<span class="scan-log-badge {{ $badge['class'] }}">
    @if($badge['icon'])<span aria-hidden="true">{{ $badge['icon'] }}</span>@endif
    {{ $badge['label'] }}
</span>
