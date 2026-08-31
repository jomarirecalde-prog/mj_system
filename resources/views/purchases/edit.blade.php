@extends('layouts.app')

@section('title', 'Edit Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}">
@endpush

@section('content')
<div class="pur-module inv-module">
    <header class="inv-page-header">
        <div>
            <a href="{{ route('purchases.show', $purchase) }}" class="inv-back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Purchase
            </a>
            <h1 class="inv-page-header__title">Edit Purchase</h1>
            <div class="pur-edit-badge">
                <span class="text-muted">{{ $purchase->purchase_number }}</span>
                @include('partials.purchase-status-badge', ['status' => $purchase->status])
            </div>
        </div>
    </header>

    <div class="pur-banner pur-banner--warn" role="note">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>
            <p class="pur-banner__title">Inventory not affected by edits</p>
            <p class="pur-banner__text">Changes to this purchase do not update inventory until the purchase is received.</p>
        </div>
    </div>

    <form method="post" action="{{ route('purchases.update', $purchase) }}" id="purchase-form">
        @csrf
        @method('PUT')
        @include('purchases._form')

        <div class="inv-form-actions">
            <div class="inv-form-actions__primary">
                <button type="submit" class="btn btn--primary">
                    <span class="inv-btn-spinner" aria-hidden="true"></span>
                    <span class="inv-submit-text">Save Changes</span>
                </button>
                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--secondary">Cancel</a>
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
    loadingText: 'Saving Changes…',
    itemOptions: @json($itemOptions),
});
</script>
@endpush
