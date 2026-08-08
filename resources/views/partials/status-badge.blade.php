@php
    $statusClass = match($status ?? '') {
        'Available' => 'badge--available',
        'Borrowed' => 'badge--borrowed',
        'Under Maintenance' => 'badge--maintenance',
        'Archived' => 'badge--archived',
        'Out of Stock' => 'badge--out',
        default => 'badge--default',
    };
@endphp
<span class="badge {{ $statusClass }}">{{ $status ?? '—' }}</span>
