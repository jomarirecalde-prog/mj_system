<tbody id="inventory-table-body">
@if(!empty($items) && count($items))
    @foreach($items as $row)
        <tr>
            <td><a href="{{ route('inventory.show', $row['id'] ?? $row->id) }}">{{ $row['item_code'] ?? $row->item_code }}</a></td>
            <td>{{ $row['name'] ?? $row->name }}</td>
            <td>{{ $row['category']['name'] ?? $row->category?->name ?? '—' }}</td>
            <td>{{ $row['location']['name'] ?? $row->location?->name ?? '—' }}</td>
            <td>
                {{ $row['quantity'] ?? $row->quantity }}
                @php
                    $qtyItem = is_object($row) ? $row : null;
                @endphp
                @if($qtyItem && method_exists($qtyItem, 'isLowStock') && $qtyItem->isLowStock())
                    <span class="badge badge--warn" title="At or below reorder level">Low</span>
                @endif
            </td>
            <td>@include('partials.status-badge', ['status' => $row['status'] ?? $row->status])</td>
            <td>{{ $row['condition'] ?? $row->condition }}</td>
            <td>{{ money($row['total_value'] ?? $row->total_value) }}</td>
            <td>
                <div class="actions">
                    <a href="{{ route('inventory.show', $row['id'] ?? $row->id) }}" class="btn btn--ghost btn--sm">View</a>
                    @if(auth()->user()->canModifyInventory())
                        <a href="{{ route('inventory.edit', $row['id'] ?? $row->id) }}" class="btn btn--secondary btn--sm">Edit</a>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
@endif
</tbody>
