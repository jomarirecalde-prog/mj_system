@extends('layouts.app')

@section('title', 'Transfer item')

@section('content')
<div class="page-header">
    <div>
        <h1>Transfer item</h1>
        <p class="page-header__meta">{{ $item->item_code }} — current location: {{ $item->location?->name ?? 'Unassigned' }}</p>
    </div>
    <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card__body">
        <form method="post" action="{{ route('transfer.store', $item) }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="to_location_id">To location</label>
                    <select name="to_location_id" id="to_location_id" class="form-select">
                        <option value="">— No change —</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected(old('to_location_id') == $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="to_department_id">To department</label>
                    <select name="to_department_id" id="to_department_id" class="form-select">
                        <option value="">— No change —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('to_department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="to_custodian_id">Custodian</label>
                    <select name="to_custodian_id" id="to_custodian_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(old('to_custodian_id') == $u->id)>{{ $u->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="transfer_date">Transfer date</label>
                    <input type="date" name="transfer_date" id="transfer_date" class="form-control" value="{{ old('transfer_date', now('Asia/Manila')->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="approved_by">Approved by</label>
                    <select name="approved_by" id="approved_by" class="form-select">
                        <option value="">— Optional —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(old('approved_by') == $u->id)>{{ $u->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="reason">Reason</label>
                    <input type="text" name="reason" id="reason" class="form-control" value="{{ old('reason') }}">
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks') }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn--primary mt-2">Complete transfer</button>
        </form>
    </div>
</div>
@endsection
