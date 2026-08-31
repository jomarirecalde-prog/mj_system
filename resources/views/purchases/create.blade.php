@extends('layouts.app')

@section('title', 'New Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}">
@endpush

@section('content')
<div class="pur-module inv-module">
    <header class="inv-page-header">
        <div>
            <a href="{{ route('purchases.index') }}" class="inv-back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Purchases
            </a>
            <h1 class="inv-page-header__title">New Purchase</h1>
            <p class="inv-page-header__desc">Create a purchase order before receiving inventory.</p>
        </div>
    </header>

    {{-- Workflow indicator --}}
    <nav class="pur-workflow" aria-label="Purchase workflow">
        <div class="pur-workflow__step is-active">
            <span class="pur-workflow__dot">1</span>
            <span class="pur-workflow__label">Create Purchase</span>
        </div>
        <div class="pur-workflow__step">
            <span class="pur-workflow__dot">2</span>
            <span class="pur-workflow__label">Order</span>
        </div>
        <div class="pur-workflow__step">
            <span class="pur-workflow__dot">3</span>
            <span class="pur-workflow__label">Receive</span>
        </div>
        <div class="pur-workflow__step">
            <span class="pur-workflow__dot">4</span>
            <span class="pur-workflow__label">Inventory Updated</span>
        </div>
    </nav>

    <form method="post" action="{{ route('purchases.store') }}" id="purchase-form">
        @csrf
        @include('purchases._form')

        <div class="inv-form-actions">
            <div class="inv-form-actions__primary">
                <button type="submit" class="btn btn--primary">
                    <span class="inv-btn-spinner" aria-hidden="true"></span>
                    <span class="inv-submit-text">Create Purchase</span>
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn--secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/purchases.js') }}"></script>
<script>
PurchasesModule.initPurchaseForm({
    formId: 'purchase-form',
    loadingText: 'Creating Purchase…',
    itemOptions: @json($itemOptions),
});
</script>
@endpush
