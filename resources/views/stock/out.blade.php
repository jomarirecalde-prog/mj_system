@extends('layouts.app')

@section('title', ($mode ?? 'issue') === 'consumption' ? 'Consume item' : 'Issue item')

@section('content')
@php $isConsume = ($mode ?? 'issue') === 'consumption'; @endphp
<div class="page-header">
    <div>
        <h1>{{ $isConsume ? 'Consume item' : 'Issue item' }}</h1>
        <p class="page-header__meta">
            {{ $item->labeledName() }}
            · Available: <strong>{{ $item->quantity }} {{ $item->unit }}</strong>
            @if($item->isLowStock())
                · <span class="badge badge--warn">⚠️ Low Stock</span>
            @endif
        </p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card__body">
        <form method="post" action="{{ route('stock.out', $item) }}">
            @csrf
            <input type="hidden" name="transaction_type" value="{{ $isConsume ? 'consumption' : 'issue' }}">
            <div class="form-grid form-grid--1">
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity <span class="req">*</span></label>
                    <input type="number" step="0.01" min="0.01" max="{{ $item->quantity }}" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') }}" required>
                    <span class="form-hint">Cannot exceed available stock ({{ $item->quantity }}).</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="recipient">Recipient <span class="req">*</span></label>
                    <input type="text" name="recipient" id="recipient" class="form-control" value="{{ old('recipient') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department <span class="req">*</span></label>
                    <select name="department_id" id="department_id" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="purpose">Purpose <span class="req">*</span></label>
                    <input type="text" name="purpose" id="purpose" class="form-control" value="{{ old('purpose') }}" required>
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
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <p class="form-hint mt-1">After confirmation: New Quantity = Current Quantity − Issued Quantity.</p>
            <button type="submit" class="btn btn--primary mt-2">{{ $isConsume ? 'Confirm consumption' : 'Confirm issue' }}</button>
        </form>
    </div>
</div>
@endsection
