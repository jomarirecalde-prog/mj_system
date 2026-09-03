@extends('layouts.app')

@section('title', 'Edit '.$item->part_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
@endpush

@section('content')
<div class="inv-module">
    <header class="inv-page-header">
        <div>
            <a href="{{ route('inventory.show', $item) }}" class="inv-back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Item
            </a>
            <h1 class="inv-page-header__title">Edit Inventory Item</h1>
            <p class="inv-page-header__desc">{{ $item->name }} · {{ $item->part_number }}</p>
            <p class="inv-page-header__count" style="margin-top:0.5rem;font-size:0.82rem;">Last saved changes are reflected immediately after saving.</p>
        </div>
        <div class="inv-page-header__actions">
            <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">View Item</a>
        </div>
    </header>

    <form method="post" action="{{ route('inventory.update', $item) }}" id="inventory-edit-form">
        @csrf
        @method('PUT')
        @include('inventory._form', ['item' => $item])

        <div class="inv-form-actions">
            <div class="inv-form-actions__primary">
                <button type="submit" class="btn btn--primary">
                    <span class="inv-btn-spinner" aria-hidden="true"></span>
                    <span class="inv-submit-text">Save Changes</span>
                </button>
                <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
<script>
InventoryModule.initForm({
    formId: 'inventory-edit-form',
    isEdit: true,
    loadingText: 'Saving Changes…',
});
</script>
@endpush
