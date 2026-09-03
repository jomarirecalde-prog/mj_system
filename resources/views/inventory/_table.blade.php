<tbody id="inventory-table-body">
@if(!empty($items) && count($items))
    @foreach($items as $row)
        @php
            $rowId = $row['id'] ?? $row->id;
            $rowPart = $row['part_number'] ?? $row->part_number ?? null;
            $rowCode = $row['item_code'] ?? $row->item_code;
            $rowName = $row['name'] ?? $row->name;
            $rowQty = $row['quantity'] ?? $row->quantity;
            $rowUnit = $row['unit'] ?? ($row->unit ?? 'pcs');
            $rowStatus = $row['status'] ?? $row->status;
            $rowCondition = $row['condition'] ?? $row->condition;
            $rowValue = $row['total_value'] ?? $row->total_value;
            $rowBrand = $row['brand'] ?? $row->brand ?? null;
            $rowModel = $row['model'] ?? $row->model ?? null;
            $rowBrandModel = trim(implode(' ', array_filter([$rowBrand, $rowModel])));
            $rowCategory = $row['category']['name'] ?? $row->category?->name ?? '—';
            $rowLocation = $row['location']['name'] ?? $row->location?->name ?? '—';
            $qtyItem = is_object($row) ? $row : null;
            $isLow = $qtyItem && method_exists($qtyItem, 'isLowStock') && $qtyItem->isLowStock();
        @endphp
        <tr>
            <td class="inv-col-part"><span class="inv-part-number">{{ $rowPart ?: '—' }}</span></td>
            <td>
                <a href="{{ route('inventory.show', $rowId) }}" class="inv-item-link">
                    <span class="inv-item-link__name">{{ $rowName }}</span>
                    @if($rowBrandModel)
                        <span class="inv-item-link__meta">{{ $rowBrandModel }}</span>
                    @endif
                </a>
            </td>
            <td>{{ $rowCategory }}</td>
            <td>{{ $rowLocation }}</td>
            <td>
                <div class="inv-qty">
                    <span class="inv-qty__value">{{ $rowQty }} {{ $rowUnit }}</span>
                    @if($isLow)
                        <span class="inv-qty__warn" role="status">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            Low Stock
                        </span>
                    @endif
                </div>
            </td>
            <td>@include('partials.status-badge', ['status' => $rowStatus])</td>
            <td><span class="inv-condition">{{ $rowCondition }}</span></td>
            <td><span class="inv-value">{{ money($rowValue) }}</span></td>
            <td class="inv-col-actions">
                <div class="actions">
                    <a href="{{ route('inventory.show', $rowId) }}" class="btn btn--ghost btn--sm">View</a>
                    @if(auth()->user()->canModifyInventory())
                        <a href="{{ route('inventory.edit', $rowId) }}" class="btn btn--secondary btn--sm">Edit</a>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
@endif
</tbody>
