@php
    $status = strtolower($status ?? '');
    $labels = [
        'pending' => 'Pending',
        'ordered' => 'Ordered',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];
    $classes = [
        'pending' => 'pur-status--pending',
        'ordered' => 'pur-status--ordered',
        'received' => 'pur-status--received',
        'cancelled' => 'pur-status--cancelled',
    ];
    $label = $labels[$status] ?? ucfirst($status);
    $class = $classes[$status] ?? 'pur-status--default';
@endphp
<span class="pur-status {{ $class }}" role="status">
    @if($status === 'pending')
        <svg class="pur-status__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
    @elseif($status === 'ordered')
        <svg class="pur-status__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6"/></svg>
    @elseif($status === 'received')
        <svg class="pur-status__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    @elseif($status === 'cancelled')
        <svg class="pur-status__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    @endif
    <span>{{ $label }}</span>
</span>
