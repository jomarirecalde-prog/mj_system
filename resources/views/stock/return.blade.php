@extends('layouts.app')
@section('title', 'Return stock')
@section('content')
<div class="page-header">
    <div>
        <h1>Return stock</h1>
        <p class="page-header__meta">{{ $item->item_code }} — {{ $item->name }} (current: {{ $item->quantity }} {{ $item->unit }})</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>
<div class="card" style="max-width:640px;">
    <div class="card__body">
        <form method="post" action="{{ route('stock.return', $item) }}">
            @csrf
            <div class="form-grid form-grid--1">
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity returned <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recipient">Returned by</label>
                    <input type="text" name="recipient" id="recipient" class="form-control" value="{{ old('recipient') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">— Optional —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="transaction_date">Date</label>
                    <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ old('transaction_date', now('Asia/Manila')->format('Y-m-d')) }}">
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
            <button type="submit" class="btn btn--primary mt-2">Record return</button>
        </form>
    </div>
</div>
@endsection
