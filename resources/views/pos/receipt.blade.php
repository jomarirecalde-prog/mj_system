@extends('layouts.print')
@section('title', 'Receipt '.$sale->sale_number)
@section('content')
<div class="receipt">
    <div class="receipt__header">
        <h1>{{ setting('organization_name', 'QR Inventory') }}</h1>
        <p>Official receipt · {{ $sale->sale_number }}</p>
        <p>{{ $sale->sale_date?->format('M d, Y') }} · {{ ph_datetime($sale->created_at) }}</p>
    </div>

    <p><strong>Customer:</strong> {{ $sale->customer_name ?: 'Walk-in' }}</p>
    <p><strong>Payment:</strong> {{ \App\Models\Sale::paymentLabel($sale->payment_method) }}</p>
    <p><strong>Cashier:</strong> {{ $sale->cashier?->displayName() ?? '—' }}</p>
    @if($sale->isVoided())
        <p><strong>Status:</strong> VOIDED</p>
    @endif

    <table class="data-table receipt__table">
        <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $line)
            <tr>
                <td>{{ $line->item?->name ?? 'Item #'.$line->inventory_item_id }}</td>
                <td>{{ $line->quantity }}</td>
                <td>{{ money($line->unit_price) }}</td>
                <td>{{ money($line->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="receipt__totals">
        <div><span>Subtotal</span><span>{{ money($sale->subtotal) }}</span></div>
        <div><span>Discount</span><span>{{ money($sale->discount) }}</span></div>
        <div><span>Tax</span><span>{{ money($sale->tax) }}</span></div>
        <div class="receipt__grand"><span>Total</span><span>{{ money($sale->total_amount) }}</span></div>
        @if($sale->amount_tendered !== null)
            <div><span>Tendered</span><span>{{ money($sale->amount_tendered) }}</span></div>
            <div><span>Change</span><span>{{ money($sale->change_due) }}</span></div>
        @endif
    </div>

    @if($sale->remarks)
        <p class="mt-2">{{ $sale->remarks }}</p>
    @endif

    <p class="receipt__footer text-muted">Thank you for your purchase.</p>
</div>
@endsection
