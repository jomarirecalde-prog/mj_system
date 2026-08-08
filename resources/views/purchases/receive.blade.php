@extends('layouts.app')
@section('title', 'Receive purchase')
@section('content')
<div class="page-header">
    <div>
        <h1>Receive purchase</h1>
        <p class="page-header__meta">{{ $purchase->purchase_number }} — receiving will add quantities to inventory</p>
    </div>
    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:820px;">
    <div class="card__body">
        <form method="post" action="{{ route('purchases.receive', $purchase) }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="received_date">Received date</label>
                    <input type="date" name="received_date" id="received_date" class="form-control" value="{{ old('received_date', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="invoice_number">Invoice number</label>
                    <input type="text" name="invoice_number" id="invoice_number" class="form-control" value="{{ old('invoice_number', $purchase->invoice_number) }}">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="table-wrap mt-2">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Current stock</th>
                        <th>Ordered</th>
                        <th>Qty to receive</th>
                        <th>Unit cost</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchase->items as $line)
                        <tr>
                            <td>{{ $line->item?->item_code }} — {{ $line->item?->name }}</td>
                            <td>{{ $line->item?->quantity }} {{ $line->item?->unit }}</td>
                            <td>{{ $line->quantity_ordered }}</td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                       name="quantities[{{ $line->id }}]"
                                       value="{{ old('quantities.'.$line->id, $line->quantity_ordered) }}" required>
                            </td>
                            <td>{{ money($line->unit_cost) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <p class="form-hint mt-1">After confirmation: New Stock = Current Stock + Received Quantity for each item.</p>
            <button type="submit" class="btn btn--primary mt-2" data-confirm="Receive this purchase and update inventory quantities?">Confirm receive</button>
        </form>
    </div>
</div>
@endsection
