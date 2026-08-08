@extends('layouts.app')
@section('title', $sale->sale_number)
@section('content')
<div class="page-header">
    <div>
        <h1>{{ $sale->sale_number }}</h1>
        <p class="page-header__meta">
            {{ $sale->sale_date?->format('M d, Y') }}
            · <span class="badge badge--{{ $sale->status === 'completed' ? 'available' : 'archived' }}">{{ ucfirst($sale->status) }}</span>
        </p>
    </div>
    <div class="btn-group">
        <a href="{{ route('pos.index') }}" class="btn btn--secondary">History</a>
        <a href="{{ route('pos.terminal') }}" class="btn btn--secondary">New sale</a>
        <a href="{{ route('pos.receipt', $sale) }}" class="btn btn--secondary" target="_blank">Print receipt</a>
        @if($sale->canVoid())
            <form method="post" action="{{ route('pos.void', $sale) }}" data-confirm="Void this sale and restore stock?" style="display:inline;">
                @csrf
                <input type="hidden" name="void_reason" value="Voided from sale detail">
                <button class="btn btn--danger" type="submit">Void sale</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid--2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Sale details</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Customer</dt><dd>{{ $sale->customer_name ?: 'Walk-in' }}</dd></div>
                <div class="dl-item"><dt>Payment</dt><dd>{{ \App\Models\Sale::paymentLabel($sale->payment_method) }}</dd></div>
                <div class="dl-item"><dt>Cashier</dt><dd>{{ $sale->cashier?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Subtotal</dt><dd>{{ money($sale->subtotal) }}</dd></div>
                <div class="dl-item"><dt>Discount</dt><dd>{{ money($sale->discount) }}</dd></div>
                <div class="dl-item"><dt>Tax</dt><dd>{{ money($sale->tax) }}</dd></div>
                <div class="dl-item"><dt>Total</dt><dd><strong>{{ money($sale->total_amount) }}</strong></dd></div>
                @if($sale->amount_tendered !== null)
                    <div class="dl-item"><dt>Tendered</dt><dd>{{ money($sale->amount_tendered) }}</dd></div>
                    <div class="dl-item"><dt>Change</dt><dd>{{ money($sale->change_due) }}</dd></div>
                @endif
                @if($sale->isVoided())
                    <div class="dl-item"><dt>Voided by</dt><dd>{{ $sale->voider?->displayName() ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Voided at</dt><dd>{{ $sale->voided_at ? ph_datetime($sale->voided_at) : '—' }}</dd></div>
                    <div class="dl-item"><dt>Void reason</dt><dd>{{ $sale->void_reason ?? '—' }}</dd></div>
                @endif
            </dl>
            @if($sale->remarks)
                <p class="mt-2 text-muted">{{ $sale->remarks }}</p>
            @endif
            @if($sale->isCompleted())
                <p class="form-hint mt-2">Inventory quantity was deducted through Sale ledger transactions.</p>
            @else
                <p class="form-hint mt-2">Stock was restored through Sale Return ledger transactions.</p>
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
                    <th>Qty</th>
                    <th>Unit price</th>
                    <th>Unit cost</th>
                    <th>Line total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sale->items as $line)
                    <tr>
                        <td>
                            @if($line->item)
                                <a href="{{ route('inventory.show', $line->item) }}">{{ $line->item->item_code }}</a>
                                <div class="text-muted">{{ $line->item->name }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $line->quantity }}</td>
                        <td>{{ money($line->unit_price) }}</td>
                        <td>{{ money($line->unit_cost) }}</td>
                        <td>{{ money($line->line_total) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
