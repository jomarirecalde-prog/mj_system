@extends('layouts.app')
@section('title', 'Stock adjustment')
@section('content')
<div class="page-header">
    <div>
        <h1>Stock adjustment</h1>
        <p class="page-header__meta">{{ $item->labeledName() }} (current: {{ $item->quantity }} {{ $item->unit }})</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>
<div class="card" style="max-width:640px;">
    <div class="card__body">
        <form method="post" action="{{ route('stock.adjust', $item) }}">
            @csrf
            <div class="form-grid form-grid--1">
                <div class="form-group">
                    <label class="form-label" for="new_quantity">New counted quantity <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0" name="new_quantity" id="new_quantity" class="form-control" value="{{ old('new_quantity', $item->quantity) }}" required>
                    <span class="form-hint">Sets absolute stock via an adjustment transaction (not a silent overwrite).</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="transaction_date">Date</label>
                    <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ old('transaction_date', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="reference_number">Reference number</label>
                    <input type="text" name="reference_number" id="reference_number" class="form-control" value="{{ old('reference_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="remarks">Reason / remarks <span class="req">*</span></label>
                    <textarea name="remarks" id="remarks" class="form-textarea" required>{{ old('remarks') }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Record adjustment</button>
        </form>
    </div>
</div>
@endsection
