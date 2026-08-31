@php
    $status = strtolower($status ?? '');
    $labels = ['completed' => 'Completed', 'voided' => 'Voided'];
    $classes = ['completed' => 'pos-status-badge--completed', 'voided' => 'pos-status-badge--voided'];
@endphp
<span class="pos-status-badge {{ $classes[$status] ?? 'pos-status-badge--default' }}" role="status">
    @if($status === 'completed')
        <svg class="pos-status-badge__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    @elseif($status === 'voided')
        <svg class="pos-status-badge__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    @endif
    <span>{{ $labels[$status] ?? ucfirst($status) }}</span>
</span>
