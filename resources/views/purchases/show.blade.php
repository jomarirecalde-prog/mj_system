@extends('layouts.app')
@section('title', $purchase->purchase_number)
@section('content')
<div class="page-header">
    <div>
        <h1>{{ $purchase->purchase_number }}</h1>
        <p class="page-header__meta">
            {{ $purchase->purchase_date?->format('M d, Y') }}
            · <span class="badge badge--{{ $purchase->status === 'received' ? 'available' : ($purchase->status === 'cancelled' ? 'archived' : 'warn') }}">{{ ucfirst($purchase->status) }}</span>
        </p>
    </div>
    <div class="btn-group">
        <a href="{{ route('purchases.index') }}" class="btn btn--secondary">Back</a>
        @if(auth()->user()->canModifyInventory())
            @if($purchase->canReceive())
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn--secondary">Edit</a>
                @if($purchase->status === 'pending')
                    <form method="post" action="{{ route('purchases.ordered', $purchase) }}" style="display:inline;">@csrf<button class="btn btn--secondary" type="submit">Mark ordered</button></form>
                @endif
                <a href="{{ route('purchases.receive.form', $purchase) }}" class="btn btn--primary">Receive &amp; stock in</a>
                <form method="post" action="{{ route('purchases.cancel', $purchase) }}" data-confirm="Cancel this purchase? No stock will change." style="display:inline;">@csrf<button class="btn btn--danger" type="submit">Cancel</button></form>
            @endif
        @endif
    </div>
</div>

<div class="grid grid--2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Purchase details</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Supplier</dt><dd>{{ $purchase->supplier?->name ?? '—' }}</dd></div>
                <div class="dl-item"><dt>PO number</dt><dd>{{ $purchase->purchase_order_number ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Invoice number</dt><dd>{{ $purchase->invoice_number ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Total cost</dt><dd>{{ money($purchase->total_cost) }}</dd></div>
                <div class="dl-item"><dt>Received by</dt><dd>{{ $purchase->receiver?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Received at</dt><dd>{{ $purchase->received_at ? ph_datetime($purchase->received_at) : '—' }}</dd></div>
                <div class="dl-item"><dt>Created by</dt><dd>{{ $purchase->creator?->displayName() ?? '—' }}</dd></div>
            </dl>
            @if($purchase->remarks)
                <p class="mt-2 text-muted">{{ $purchase->remarks }}</p>
            @endif
            @if(!$purchase->isReceived())
                <p class="form-hint mt-2">Inventory quantity is <strong>not</strong> increased until this purchase is received.</p>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Line items</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th>Ordered</th>
                    <th>Received</th>
                    <th>Unit cost</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($purchase->items as $line)
                    <tr>
                        <td>
                            @if($line->item)
                                <a href="{{ route('inventory.show', $line->item) }}">{{ $line->item->item_code }}</a>
                                <div class="text-muted">{{ $line->item->name }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $line->quantity_ordered }}</td>
                        <td>{{ $line->quantity_received }}</td>
                        <td>{{ money($line->unit_cost) }}</td>
                        <td>{{ money($line->total_cost) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="form-hint mt-1">Total cost = Quantity received × Unit cost (after receiving).</p>
        </div>
    </div>
</div>
@endsection
