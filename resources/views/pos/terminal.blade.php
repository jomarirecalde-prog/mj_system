@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
@endpush

@section('content')
<div class="pos-module">
    <header class="pos-header">
        <div class="pos-header__left">
            <h1 class="pos-header__title">Point of Sale</h1>
            <p class="pos-header__desc">Scan or search items to build a sale.</p>
        </div>
        <div class="pos-header__actions">
            <button type="button" class="btn btn--secondary" id="pos-scan-toggle" aria-expanded="false" aria-controls="pos-scanner-overlay">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 16h4.01M8 16H4.01M8 12H4.01M12 8h4.01"/></svg>
                Scan QR
            </button>
            <a href="{{ route('pos.index') }}" class="btn btn--secondary">Sale History</a>
        </div>
    </header>

    <div class="pos-status-bar">
        <div class="pos-status-indicator is-ready" id="pos-status-indicator" role="status" aria-live="polite">
            <span class="pos-status-indicator__dot" aria-hidden="true"></span>
            <span id="pos-status-text">POS Ready</span>
        </div>
        <div class="pos-shortcuts-hint" aria-hidden="true">
            <kbd>F2</kbd> Search · <kbd>F4</kbd> Cash · <kbd>Ctrl+K</kbd> Search · <kbd>Esc</kbd> Close scanner
        </div>
    </div>

    {{-- QR Scanner modal --}}
    <div class="pos-scanner-overlay" id="pos-scanner-overlay" hidden role="dialog" aria-modal="true" aria-label="QR scanner">
        <div class="pos-scanner-modal">
            <div class="pos-scanner-modal__header">
                <h2 class="pos-scanner-modal__title">Scan Product QR</h2>
                <button type="button" class="btn btn--ghost btn--sm" id="pos-scan-close" aria-label="Close scanner">Close</button>
            </div>
            <div class="pos-scanner-modal__body">
                <div id="pos-qr-reader" class="scan-viewport pos-scan-viewport" aria-label="POS camera scanner"></div>
                <p class="pos-scanner-modal__hint">Position the QR code inside the frame.</p>
                <div class="pos-scanner-status" id="pos-scanner-camera-status" role="status">
                    <span class="pos-scanner-status__dot" aria-hidden="true"></span>
                    <span class="pos-scanner-status__label">Camera idle</span>
                </div>
                <p class="form-hint" id="pos-scan-status"></p>

                <p class="pos-manual-scan-label">Can't scan?</p>
                <label class="form-label" for="pos-manual-qr">QR / Item Code</label>
                <div class="pos-scan-manual">
                    <input type="text" id="pos-manual-qr" class="form-control" placeholder="INV-2026-000001 or item code" autocomplete="off" aria-label="Manual QR or item code entry">
                    <button type="button" class="btn btn--primary" id="pos-manual-scan-btn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div class="pos-layout">
        {{-- Catalog --}}
        <div class="pos-catalog">
            <div class="pos-panel__header">
                <h2 class="pos-panel__title">Product Search</h2>
            </div>
            <div class="pos-panel__body">
                <label class="sr-only" for="pos-search">Search products</label>
                <div class="pos-search-wrap">
                    <svg class="pos-search-wrap__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" id="pos-search" class="pos-search-input" placeholder="Search product, scan QR, or enter item code…" autocomplete="off" autofocus>
                </div>
                <div id="pos-results" class="pos-results mt-2" aria-live="polite">
                    <p class="text-muted">Search or scan to add items to the cart.</p>
                </div>
            </div>
        </div>

        {{-- Cart / Checkout --}}
        <div class="pos-cart-panel" id="pos-cart-panel">
            <div class="pos-panel__header">
                <h2 class="pos-panel__title">Cart &amp; Checkout</h2>
            </div>
            <div class="pos-panel__body">
                <form method="post" action="{{ route('pos.checkout') }}" id="pos-checkout-form">
                    @csrf

                    <div class="form-grid form-grid--1">
                        <div class="form-group">
                            <label class="form-label" for="customer_name">Customer</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}" placeholder="Walk-in customer / name" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="payment_method_display">Payment</label>
                            <input type="text" id="payment_method_display" class="form-control" value="Cash" readonly aria-readonly="true">
                            <input type="hidden" name="payment_method" id="payment_method" value="cash">
                        </div>
                    </div>

                    {{-- Cart items --}}
                    <div class="pos-cart-empty" id="pos-cart-empty">
                        <svg class="pos-cart-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="pos-cart-empty__title">Cart is empty</p>
                        <p class="pos-cart-empty__text">Search or scan an item to begin a sale.</p>
                    </div>
                    <div class="pos-cart-items" id="pos-cart-items" aria-live="polite"></div>

                    {{-- Payment summary --}}
                    <div class="pos-payment-summary">
                        <div class="pos-payment-summary__title">Payment Summary</div>
                        <div class="pos-payment-summary__row">
                            <span>Subtotal</span>
                            <strong id="pos-subtotal">₱0.00</strong>
                        </div>
                        <div class="pos-payment-summary__row pos-payment-summary__row--compact">
                            <label class="form-label mb-0" for="discount">Discount</label>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="discount" id="discount" class="form-control" value="{{ old('discount', 0) }}" aria-label="Discount amount">
                        </div>
                        <div class="pos-payment-summary__row pos-payment-summary__row--compact">
                            <label class="form-label mb-0" for="tax">Tax</label>
                            <input type="number" step="0.01" min="0" inputmode="decimal" name="tax" id="tax" class="form-control" value="{{ old('tax', 0) }}" aria-label="Tax amount">
                        </div>
                        <div class="pos-payment-summary__row pos-payment-summary__row--grand">
                            <span>Total</span>
                            <strong id="pos-total">₱0.00</strong>
                        </div>
                    </div>

                    <div class="pos-payment mt-2" id="pos-payment">
                        <div class="pos-payment__title">Cash Received</div>
                        <div class="form-group mb-0">
                            <label class="sr-only" for="amount_tendered" id="amount_tendered_label">Cash received</label>
                            <div class="pos-money-input">
                                <span class="pos-money-input__prefix" aria-hidden="true">₱</span>
                                <input type="number" step="0.01" min="0" inputmode="decimal" name="amount_tendered" id="amount_tendered"
                                       class="form-control pos-money-input__field" value="{{ old('amount_tendered') }}" placeholder="0.00" required autocomplete="off"
                                       aria-describedby="amount_tendered_hint pos-payment-alert">
                            </div>
                            <div class="pos-quick-cash" aria-label="Quick cash amounts">
                                <button type="button" class="pos-quick-cash__btn" data-amount="exact">Exact</button>
                                <button type="button" class="pos-quick-cash__btn" data-amount="100">₱100</button>
                                <button type="button" class="pos-quick-cash__btn" data-amount="500">₱500</button>
                                <button type="button" class="pos-quick-cash__btn" data-amount="1000">₱1,000</button>
                                <button type="button" class="pos-quick-cash__btn" data-amount="2000">₱2,000</button>
                            </div>
                            <span class="form-hint" id="amount_tendered_hint">Enter the cash given by the customer.</span>
                        </div>

                        <div class="pos-totals__row pos-change-row" id="pos-change-row">
                            <span id="pos-change-label">Change</span>
                            <strong id="pos-change" aria-live="polite">₱0.00</strong>
                        </div>
                        <p class="pos-payment__alert" id="pos-payment-alert" hidden role="alert" aria-live="assertive"></p>
                    </div>

                    <div class="form-group mt-2">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-textarea" rows="2">{{ old('remarks') }}</textarea>
                    </div>

                    <div id="pos-hidden-items"></div>

                    <button type="submit" class="btn btn--primary btn--block pos-checkout-btn mt-2" id="pos-checkout-btn" disabled>
                        <span class="pos-btn-spinner" aria-hidden="true"></span>
                        <span class="pos-checkout-text">Complete Sale</span>
                    </button>
                    <p class="pos-checkout-hint" id="pos-checkout-hint">Add items to continue.</p>
                </form>
            </div>
        </div>
    </div>

    {{-- Mobile sticky bar --}}
    <div class="pos-mobile-bar" id="pos-mobile-bar" aria-hidden="true">
        <div>
            <div class="pos-mobile-bar__label">Total</div>
            <div class="pos-mobile-bar__total" id="pos-mobile-total">₱0.00</div>
        </div>
        <button type="button" class="btn btn--primary btn--sm" id="pos-mobile-review">Review Cart</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="{{ asset('js/pos.js') }}"></script>
<script>
PosModule.initTerminal({
    searchUrl: @json(route('pos.search')),
    scanUrl: @json(route('pos.scan')),
});
</script>
@endpush
