@extends('layouts.app')

@section('title', 'Borrow item')

@section('content')
<div class="page-header">
    <div>
        <h1>Borrow item</h1>
        <p class="page-header__meta">{{ $item->item_code }} — {{ $item->name }}</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card__body">
        <form method="post" action="{{ route('borrow.store', $item) }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="borrower_name">Borrower name <span class="req">*</span></label>
                    <input type="text" name="borrower_name" id="borrower_name" class="form-control" value="{{ old('borrower_name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="borrower_id_number">ID number</label>
                    <input type="text" name="borrower_id_number" id="borrower_id_number" class="form-control" value="{{ old('borrower_id_number') }}">
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
                    <label class="form-label" for="date_borrowed">Date borrowed</label>
                    <input type="date" name="date_borrowed" id="date_borrowed" class="form-control" value="{{ old('date_borrowed', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="expected_return_date">Expected return</label>
                    <input type="date" name="expected_return_date" id="expected_return_date" class="form-control" value="{{ old('expected_return_date') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="condition_before">Condition before</label>
                    <input type="text" name="condition_before" id="condition_before" class="form-control" value="{{ old('condition_before', $item->condition) }}">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="purpose">Purpose</label>
                    <input type="text" name="purpose" id="purpose" class="form-control" value="{{ old('purpose') }}">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Record borrow</button>
        </form>
    </div>
</div>
@endsection
