@extends('layouts.print')

@section('title', 'Receipt '.$sale->sale_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
<style>
@media print {
    .no-print { display: none !important; }
    @page { margin: 2mm; size: 80mm auto; }
}
@media print and (max-width: 58mm) {
    @page { size: 58mm auto; }
}
</style>
@endpush

@section('content')
<div class="pos-receipt">
    <div class="pos-receipt__actions no-print">
        <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">Print</button>
        <a href="{{ route('pos.show', $sale) }}" class="btn btn--secondary btn--sm">Back to Sale</a>
    </div>

    <p class="pos-receipt__org">{{ setting('organization_name', 'QR Inventory') }}</p>
    <p class="pos-receipt__type">Official Receipt</p>

    <hr class="pos-receipt__divider">

    <p class="pos-receipt__meta"><strong>Sale #:</strong> {{ $sale->sale_number }}</p>
    <p class="pos-receipt__meta"><strong>Date:</strong> {{ $sale->sale_date?->format('M d, Y') }}</p>
    <p class="pos-receipt__meta"><strong>Time:</strong> {{ ph_datetime($sale->created_at) }}</p>
    <p class="pos-receipt__meta"><strong>Customer:</strong> {{ $sale->customer_name ?: 'Walk-in' }}</p>
    <p class="pos-receipt__meta"><strong>Payment:</strong> {{ \App\Models\Sale::paymentLabel($sale->payment_method) }}</p>
    <p class="pos-receipt__meta"><strong>Cashier:</strong> {{ $sale->cashier?->displayName() ?? '—' }}</p>

    @if($sale->isVoided())
        <p class="pos-receipt__void">*** VOIDED ***</p>
    @endif

    <hr class="pos-receipt__divider">

    <table class="pos-receipt__table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="col-qty">Qty</th>
                <th class="col-price">Price</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $line)
            <tr>
                <td>
                    <span class="pos-receipt__item-name">{{ $line->item?->name ?? 'Item #'.$line->inventory_item_id }}</span>
                </td>
                <td class="col-qty">{{ $line->quantity }}</td>
                <td class="col-price">{{ money($line->unit_price) }}</td>
                <td class="col-total">{{ money($line->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <hr class="pos-receipt__divider">

    <div class="pos-receipt__totals">
        <div class="pos-receipt__totals-row">
            <span>Subtotal</span>
            <span>{{ money($sale->subtotal) }}</span>
        </div>
        @if((float) $sale->discount > 0)
            <div class="pos-receipt__totals-row">
                <span>Discount</span>
                <span>-{{ money($sale->discount) }}</span>
            </div>
        @endif
        @if((float) $sale->tax > 0)
            <div class="pos-receipt__totals-row">
                <span>Tax</span>
                <span>{{ money($sale->tax) }}</span>
            </div>
        @endif
        <div class="pos-receipt__totals-row pos-receipt__totals-row--grand">
            <span>TOTAL</span>
            <span>{{ money($sale->total_amount) }}</span>
        </div>
        @if($sale->amount_tendered !== null)
            <div class="pos-receipt__totals-row">
                <span>Tendered</span>
                <span>{{ money($sale->amount_tendered) }}</span>
            </div>
            <div class="pos-receipt__totals-row">
                <span>Change</span>
                <span>{{ money($sale->change_due) }}</span>
            </div>
        @endif
    </div>

    @if($sale->remarks)
        <hr class="pos-receipt__divider">
        <p class="pos-receipt__meta"><strong>Remarks:</strong> {{ $sale->remarks }}</p>
    @endif

    <p class="pos-receipt__footer">Thank you for your purchase.</p>
</div>
@endsection
