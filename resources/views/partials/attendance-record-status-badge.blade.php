@php
    $slug = strtolower((string) ($status ?? ''));
    if ($slug === '' && !empty($label)) {
        $slug = strtolower(str_replace(' ', '_', (string) $label));
    }
    $display = $label ?? null;
    $config = match ($slug) {
        'present' => ['class' => 'aa-dtr-badge--present', 'icon' => '✓', 'label' => 'Present'],
        'late' => ['class' => 'aa-dtr-badge--late', 'icon' => '⚠', 'label' => 'Late'],
        'absent' => ['class' => 'aa-dtr-badge--absent', 'icon' => '✕', 'label' => 'Absent'],
        'on_leave' => ['class' => 'aa-dtr-badge--leave', 'icon' => '▣', 'label' => 'On Leave'],
        'official_business' => ['class' => 'aa-dtr-badge--leave', 'icon' => '▣', 'label' => 'Official Business'],
        'half_day' => ['class' => 'aa-dtr-badge--warn', 'icon' => '⚠', 'label' => 'Half Day'],
        'undertime' => ['class' => 'aa-dtr-badge--warn', 'icon' => '⚠', 'label' => 'Undertime'],
        'incomplete' => ['class' => 'aa-dtr-badge--incomplete', 'icon' => '⚠', 'label' => 'Incomplete'],
        'rest_day' => ['class' => 'aa-dtr-badge--rest', 'icon' => '○', 'label' => 'Rest Day'],
        'currently_in', 'time_in' => ['class' => 'aa-dtr-badge--in', 'icon' => '●', 'label' => 'Time In'],
        'timed_out', 'time_out' => ['class' => 'aa-dtr-badge--out', 'icon' => '✓', 'label' => 'Time Out'],
        default => ['class' => 'aa-dtr-badge--default', 'icon' => '', 'label' => $display ?? ucfirst(str_replace('_', ' ', $slug ?: '—'))],
    };
    $text = $display ?? $config['label'];
@endphp
<span class="aa-dtr-badge {{ $config['class'] }}">
    @if($config['icon'] !== '')
        <span class="aa-dtr-badge__icon" aria-hidden="true">{{ $config['icon'] }}</span>
    @endif
    <span class="aa-dtr-badge__text">{{ $text }}</span>
</span>
