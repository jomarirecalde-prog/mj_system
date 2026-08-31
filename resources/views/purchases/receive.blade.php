@extends('layouts.app')

@section('title', 'Receive Purchase')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}">
@endpush

@section('content')
@php
    $totalExpected = $purchase->items->sum('quantity_ordered');
    $totalAlreadyReceived = $purchase->items->sum('quantity_received');
@endphp

<div class="pur-module inv-module">
    <header class="inv-page-header">
        <div>
            <a href="{{ route('purchases.show', $purchase) }}" class="inv-back-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Purchase
            </a>
            <h1 class="inv-page-header__title">Receive Purchase</h1>
            <p class="inv-page-header__desc">Purchase: <strong>{{ $purchase->purchase_number }}</strong></p>
        </div>
    </header>

    <div class="pur-banner pur-banner--warn" role="alert">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>
            <p class="pur-banner__title">Inventory will be updated</p>
            <p class="pur-banner__text">Confirming receipt will update inventory quantities. New Stock = Current Stock + Received Quantity for each item.</p>
        </div>
    </div>

    {{-- Receiving summary --}}
    <div class="inv-summary-grid pur-receive-summary">
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Purchase Number</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $purchase->purchase_number }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Supplier</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $purchase->supplier?->name ?? '—' }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Purchase Date</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $purchase->purchase_date?->format('M d, Y') ?? '—' }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Status</div>
            <div class="inv-summary-card__value" style="font-size:1rem;margin-top:0.5rem;">
                @include('partials.purchase-status-badge', ['status' => $purchase->status])
            </div>
        </div>
    </div>

    <div class="inv-summary-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:1.25rem;">
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Items to Receive</div>
            <div class="inv-summary-card__value">{{ $purchase->items->count() }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Total Expected</div>
            <div class="inv-summary-card__value">{{ number_format($totalExpected, 2) }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Total to Receive</div>
            <div class="inv-summary-card__value" id="pur-receive-total-qty">{{ number_format($totalExpected, 2) }}</div>
            <div class="inv-summary-card__sub" style="color:var(--muted);font-weight:500;">Live preview from quantities entered</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Purchase Total</div>
            <div class="inv-summary-card__value">{{ money($purchase->total_cost) }}</div>
        </div>
    </div>

    <form method="post" action="{{ route('purchases.receive', $purchase) }}" id="pur-receive-form"
          data-confirm="You are about to receive this purchase. Inventory quantities will be increased based on the quantities entered."
          data-confirm-title="Confirm Receipt">
        @csrf

        {{-- Receiving information --}}
        <section class="inv-form-section" aria-labelledby="pur-receive-info">
            <header class="inv-form-section__header">
                <span class="inv-form-section__icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
                <div>
                    <h2 class="inv-form-section__title" id="pur-receive-info">Receiving Information</h2>
                    <p class="inv-form-section__desc">Date and document details for this receipt.</p>
                </div>
            </header>
            <div class="inv-form-section__body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="received_date">Received Date</label>
                        <input type="date" name="received_date" id="received_date"
                               class="form-control @error('received_date') is-invalid @enderror"
                               value="{{ old('received_date', now('Asia/Manila')->format('Y-m-d')) }}"
                               @error('received_date') aria-invalid="true" aria-describedby="received_date_error" @enderror>
                        @error('received_date')
                            <span class="form-error" id="received_date_error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="invoice_number">Invoice Number</label>
                        <input type="text" name="invoice_number" id="invoice_number"
                               class="form-control @error('invoice_number') is-invalid @enderror"
                               value="{{ old('invoice_number', $purchase->invoice_number) }}"
                               @error('invoice_number') aria-invalid="true" aria-describedby="invoice_number_error" @enderror>
                        @error('invoice_number')
                            <span class="form-error" id="invoice_number_error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group form-group--full">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-textarea @error('remarks') is-invalid @enderror" rows="2"
                                  @error('remarks') aria-invalid="true" aria-describedby="remarks_error" @enderror>{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <span class="form-error" id="remarks_error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Items to receive --}}
        <section class="inv-form-section mt-2" aria-labelledby="pur-receive-items">
            <header class="inv-form-section__header">
                <span class="inv-form-section__icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
                <div>
                    <h2 class="inv-form-section__title" id="pur-receive-items">Items to Receive</h2>
                    <p class="inv-form-section__desc">Review each item and enter the quantity to receive.</p>
                </div>
            </header>
            <div class="inv-form-section__body">
                <div class="pur-lines">
                    @foreach($purchase->items as $line)
                        @php
                            $ordered = (float) $line->quantity_ordered;
                            $alreadyReceived = (float) $line->quantity_received;
                            $remaining = max(0, $ordered - $alreadyReceived);
                            $currentStock = (float) ($line->item?->quantity ?? 0);
                            $unit = $line->item?->unit ?? 'pcs';
                            $defaultQty = old('quantities.'.$line->id, $line->quantity_ordered);
                        @endphp
                        <div class="pur-line pur-receive-line"
                             data-remaining="{{ $remaining }}"
                             data-current-stock="{{ $currentStock }}"
                             data-unit="{{ $unit }}">
                            <div class="pur-receive-item__name">{{ $line->item?->name ?? '—' }}</div>
                            <div class="pur-receive-item__code">{{ $line->item?->item_code ?? '—' }}</div>

                            <dl class="pur-receive-stats">
                                <div>
                                    <dt>Current Stock</dt>
                                    <dd>{{ $currentStock }} {{ $unit }}</dd>
                                </div>
                                <div>
                                    <dt>Ordered</dt>
                                    <dd>{{ $ordered }} {{ $unit }}</dd>
                                </div>
                                <div>
                                    <dt>Already Received</dt>
                                    <dd>{{ $alreadyReceived }} {{ $unit }}</dd>
                                </div>
                                <div>
                                    <dt>Remaining</dt>
                                    <dd>{{ $remaining }} {{ $unit }}</dd>
                                </div>
                            </dl>

                            <div class="form-grid" style="margin-top:0.75rem;">
                                <div class="form-group">
                                    <label class="form-label" for="qty_{{ $line->id }}">Qty to Receive <span class="req" aria-hidden="true">*</span></label>
                                    <input type="number" step="0.01" min="0.01"
                                           name="quantities[{{ $line->id }}]"
                                           id="qty_{{ $line->id }}"
                                           class="form-control pur-receive-qty @error('quantities.'.$line->id) is-invalid @enderror"
                                           value="{{ $defaultQty }}"
                                           required
                                           aria-describedby="qty_max_{{ $line->id }} qty_warn_{{ $line->id }}"
                                           @error('quantities.'.$line->id) aria-invalid="true" @enderror>
                                    <p class="pur-qty-max" id="qty_max_{{ $line->id }}">Maximum recommended: {{ $remaining }} {{ $unit }}</p>
                                    <p class="pur-qty-warn" id="qty_warn_{{ $line->id }}" role="alert"></p>
                                    @error('quantities.'.$line->id)
                                        <span class="form-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Unit Cost</label>
                                    <div class="form-control" style="background:rgba(238,242,246,0.65);cursor:default;" aria-readonly="true">{{ money($line->unit_cost) }}</div>
                                </div>
                            </div>

                            {{-- Stock impact preview --}}
                            <div class="pur-stock-preview" aria-label="Stock impact preview">
                                <div class="pur-stock-preview__part">
                                    <div class="pur-stock-preview__label">Current</div>
                                    <div class="pur-stock-preview__value">{{ $currentStock }} {{ $unit }}</div>
                                </div>
                                <span class="pur-stock-preview__op" aria-hidden="true">+</span>
                                <div class="pur-stock-preview__part">
                                    <div class="pur-stock-preview__label">Receiving</div>
                                    <div class="pur-stock-preview__value pur-receive-qty-display">{{ $defaultQty }} {{ $unit }}</div>
                                </div>
                                <span class="pur-stock-preview__op" aria-hidden="true">=</span>
                                <div class="pur-stock-preview__part pur-stock-preview__result">
                                    <div class="pur-stock-preview__label">New Stock</div>
                                    <div class="pur-stock-preview__value pur-stock-preview__new">{{ number_format($currentStock + (float) $defaultQty, 2) }} {{ $unit }}</div>
                                </div>
                            </div>
                            <p class="form-hint" style="margin-top:0.35rem;">Preview only — backend applies the actual stock update.</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="inv-form-actions">
            <div>
                <p class="form-hint" style="margin:0;">Inventory quantities will be updated after confirmation.</p>
            </div>
            <div class="inv-form-actions__primary">
                <button type="submit" class="btn btn--primary">
                    <span class="inv-btn-spinner" aria-hidden="true"></span>
                    <span class="inv-submit-text">✓ Confirm Receipt &amp; Update Inventory</span>
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
PurchasesModule.initReceiveForm();
</script>
@endpush
