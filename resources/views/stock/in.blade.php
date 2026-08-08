@extends('layouts.app')

@section('title', 'Stock in')

@section('content')
<div class="page-header">
    <div>
        <h1>Stock in</h1>
        <p class="page-header__meta">{{ $item->item_code }} — {{ $item->name }} (current: {{ $item->quantity }} {{ $item->unit }})</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card__body">
        <form method="post" action="{{ route('stock.in', $item) }}">
            @csrf
            <div class="form-grid form-grid--1">
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="transaction_date">Transaction date</label>
                    <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ old('transaction_date', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reference_number">Reference number</label>
                    <input type="text" name="reference_number" id="reference_number" class="form-control" value="{{ old('reference_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select">
                        <option value="">— Optional —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id') == $sup->id)>{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="purpose">Purpose</label>
                    <input type="text" name="purpose" id="purpose" class="form-control" value="{{ old('purpose') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Record stock in</button>
        </form>
    </div>
</div>
@endsection
