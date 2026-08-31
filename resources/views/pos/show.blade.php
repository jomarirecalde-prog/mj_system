@extends('layouts.app')

@section('title', $sale->sale_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
@endpush

@section('content')
@php
    $itemCount = $sale->items->count();
@endphp

<div class="pos-module inv-module">
    <header class="inv-show-hero">
        <a href="{{ route('pos.index') }}" class="inv-back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to History
        </a>
        <div class="inv-show-hero__top">
            <div>
                <h1 class="inv-show-hero__title">{{ $sale->sale_number }}</h1>
                <div style="margin-top:0.5rem;">
                    @include('partials.sale-status-badge', ['status' => $sale->status])
                </div>
                <div class="inv-show-hero__meta">
                    <span class="inv-show-hero__meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $sale->sale_date?->format('M d, Y') ?? '—' }}
                    </span>
                    <span class="inv-show-hero__meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $sale->customer_name ?: 'Walk-in' }}
                    </span>
                </div>
            </div>
            <div class="inv-page-header__actions">
                <a href="{{ route('pos.index') }}" class="btn btn--secondary">Back to History</a>
                <a href="{{ route('pos.terminal') }}" class="btn btn--secondary">New Sale</a>
                <a href="{{ route('pos.receipt', $sale) }}" class="btn btn--primary" target="_blank" rel="noopener">Print Receipt</a>
                @if($sale->canVoid())
                    <form method="post" action="{{ route('pos.void', $sale) }}" data-confirm="Void this sale and restore stock?" data-confirm-title="Void sale" style="display:inline;">
                        @csrf
                        <input type="hidden" name="void_reason" value="Voided from sale detail">
                        <button class="btn btn--danger" type="submit">Void Sale</button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    @if($sale->isVoided())
        <div class="pos-void-banner" role="alert">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <div>
                <p class="pos-void-banner__title">✕ Sale Voided</p>
                <dl class="inv-detail-grid" style="margin-top:0.5rem;font-size:0.875rem;">
                    <div class="inv-detail-item">
                        <dt>Voided By</dt>
                        <dd>{{ $sale->voider?->displayName() ?? '—' }}</dd>
                    </div>
                    <div class="inv-detail-item">
                        <dt>Voided At</dt>
                        <dd>{{ $sale->voided_at ? ph_datetime($sale->voided_at) : '—' }}</dd>
                    </div>
                    @if($sale->void_reason)
                        <div class="inv-detail-item" style="grid-column:1/-1;">
                            <dt>Reason</dt>
                            <dd>{{ $sale->void_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="inv-summary-grid">
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Total</div>
            <div class="inv-summary-card__value">{{ money($sale->total_amount) }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Items</div>
            <div class="inv-summary-card__value">{{ $itemCount }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Payment</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ \App\Models\Sale::paymentLabel($sale->payment_method) }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Cashier</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $sale->cashier?->displayName() ?? '—' }}</div>
        </div>
    </div>

    <div class="grid grid--2" style="gap:1.25rem;align-items:start;">
        {{-- Payment breakdown --}}
        <div class="card">
            <div class="card__header"><h2 class="card__title">Payment Breakdown</h2></div>
            <div class="card__body">
                <div class="pos-payment-card">
                    <div class="pos-payment-card__row">
                        <span>Subtotal</span>
                        <span>{{ money($sale->subtotal) }}</span>
                    </div>
                    <div class="pos-payment-card__row">
                        <span>Discount</span>
                        <span>{{ money($sale->discount) }}</span>
                    </div>
                    <div class="pos-payment-card__row">
                        <span>Tax</span>
                        <span>{{ money($sale->tax) }}</span>
                    </div>
                    <div class="pos-payment-card__row pos-payment-card__row--grand">
                        <span>Total</span>
                        <span class="pos-amount">{{ money($sale->total_amount) }}</span>
                    </div>
                    @if($sale->amount_tendered !== null)
                        <div class="pos-payment-card__row">
                            <span>Cash Received</span>
                            <span>{{ money($sale->amount_tendered) }}</span>
                        </div>
                        <div class="pos-payment-card__row pos-payment-card__row--change">
                            <span>Change</span>
                            <span>{{ money($sale->change_due) }}</span>
                        </div>
                    @endif
                </div>

                <dl class="inv-detail-grid mt-2">
                    <div class="inv-detail-item">
                        <dt>Customer</dt>
                        <dd>{{ $sale->customer_name ?: 'Walk-in' }}</dd>
                    </div>
                    <div class="inv-detail-item">
                        <dt>Payment Method</dt>
                        <dd>{{ \App\Models\Sale::paymentLabel($sale->payment_method) }}</dd>
                    </div>
                </dl>

                @if($sale->remarks)
                    <div class="mt-2">
                        <h3 class="inv-form-section__title" style="margin-bottom:0.35rem;font-size:0.78rem;">Remarks</h3>
                        <p class="inv-detail-text text-muted">{{ $sale->remarks }}</p>
                    </div>
                @endif

                @if($sale->isCompleted())
                    <p class="form-hint mt-2">Inventory quantity was deducted through Sale ledger transactions.</p>
                @else
                    <p class="form-hint mt-2">Stock was restored through Sale Return ledger transactions.</p>
                @endif
            </div>
        </div>

        {{-- Line items --}}
        <div class="card">
            <div class="card__header"><h2 class="card__title">Line Items</h2></div>
            <div class="card__body">
                <div class="table-wrap pos-table-desktop">
                    <table class="pos-data-table" aria-label="Sale line items">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Unit Price</th>
                                <th scope="col">Unit Cost</th>
                                <th scope="col">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($sale->items as $line)
                            <tr>
                                <td>
                                    @if($line->item)
                                        <a href="{{ route('inventory.show', $line->item) }}" class="pos-sale-link">
                                            <span class="pos-sale-link__num">{{ $line->item->item_code }}</span>
                                        </a>
                                        <div class="text-muted" style="font-size:0.82rem;">{{ $line->item->name }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $line->quantity }}</td>
                                <td>{{ money($line->unit_price) }}</td>
                                <td>{{ money($line->unit_cost) }}</td>
                                <td><span class="pos-amount">{{ money($line->line_total) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pos-cards">
                    @foreach($sale->items as $line)
                        <article class="pos-card">
                            @if($line->item)
                                <a href="{{ route('inventory.show', $line->item) }}" class="pos-sale-link">
                                    <span class="pos-sale-link__num">{{ $line->item->item_code }}</span>
                                </a>
                                <div style="font-weight:600;margin:0.15rem 0 0.75rem;">{{ $line->item->name }}</div>
                            @else
                                <div style="font-weight:600;margin-bottom:0.75rem;">—</div>
                            @endif
                            <dl class="pos-card__meta">
                                <div><dt>Qty</dt><dd>{{ $line->quantity }}</dd></div>
                                <div><dt>Unit Price</dt><dd>{{ money($line->unit_price) }}</dd></div>
                                <div><dt>Unit Cost</dt><dd>{{ money($line->unit_cost) }}</dd></div>
                                <div><dt>Line Total</dt><dd class="pos-amount">{{ money($line->line_total) }}</dd></div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
