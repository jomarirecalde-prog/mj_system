@extends('layouts.app')

@section('title', 'New Item')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
@endpush

@section('content')
<div class="inv-module">
    <header class="inv-page-header">
        <div>
            <a href="{{ route('inventory.index') }}" class="inv-back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Inventory
            </a>
            <h1 class="inv-page-header__title">Add New Inventory Item</h1>
            <p class="inv-page-header__desc">Enter the item information below to register it in the inventory system.</p>
        </div>
    </header>

    <form method="post" action="{{ route('inventory.store') }}" id="inventory-create-form">
        @csrf
        @include('inventory._form')

        <div class="inv-form-actions">
            <div class="inv-form-actions__primary">
                <button type="submit" class="btn btn--primary">
                    <span class="inv-btn-spinner" aria-hidden="true"></span>
                    <span class="inv-submit-text">Create Item</span>
                </button>
                <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
<script>
InventoryModule.initForm({
    formId: 'inventory-create-form',
    isEdit: false,
    loadingText: 'Creating Item…',
});
</script>
@endpush
