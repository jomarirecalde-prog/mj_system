@extends('layouts.app')

@section('title', 'Return item')

@section('content')
@php $item = $record->item; @endphp
<div class="page-header">
    <div>
        <h1>Return borrowed item</h1>
        <p class="page-header__meta">{{ $item?->labeledName() }} — borrowed by {{ $record->borrower_name }}</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card__body">
        <dl class="dl-grid mb-2">
            <div class="dl-item"><dt>Borrowed</dt><dd>{{ $record->date_borrowed?->format('M d, Y') }}</dd></div>
            <div class="dl-item"><dt>Expected return</dt><dd>{{ $record->expected_return_date?->format('M d, Y') ?? '—' }}</dd></div>
        </dl>
        <form method="post" action="{{ route('borrow.return.store', $record) }}">
            @csrf
            <div class="form-grid form-grid--1">
                <div class="form-group">
                    <label class="form-label" for="date_returned">Date returned</label>
                    <input type="date" name="date_returned" id="date_returned" class="form-control" value="{{ old('date_returned', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="condition_after">Condition after</label>
                    <select name="condition_after" id="condition_after" class="form-select">
                        @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                            <option value="{{ $cond }}" @selected(old('condition_after', $record->condition_before) === $cond)>{{ $cond }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="return_remarks">Return remarks</label>
                    <textarea name="return_remarks" id="return_remarks" class="form-textarea">{{ old('return_remarks') }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Confirm return</button>
        </form>
    </div>
</div>
@endsection
