@extends('layouts.app')

@section('title', $purchase->purchase_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}">
@endpush

@section('content')
@php
    $canModify = auth()->user()->canModifyInventory();
    $itemCount = $purchase->items->count();
    $isReceived = $purchase->isReceived();
    $isCancelled = $purchase->isCancelled();
    $justReceived = session('success') && str_contains(session('success'), 'received');
@endphp

<div class="pur-module inv-module">
    {{-- Success panel after receiving --}}
    @if($justReceived && $isReceived)
        <div class="pur-success-panel" role="status">
            <div class="pur-success-panel__content">
                <svg class="pur-success-panel__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="pur-success-panel__title">Purchase Received</p>
                    <p class="pur-success-panel__text">Inventory has been updated successfully.</p>
                </div>
            </div>
            <div class="pur-success-panel__actions">
                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--secondary btn--sm">View Purchase</a>
                <a href="{{ route('inventory.index') }}" class="btn btn--primary btn--sm">View Inventory</a>
            </div>
        </div>
    @endif

    {{-- Hero header --}}
    <header class="inv-show-hero">
        <a href="{{ route('purchases.index') }}" class="inv-back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Purchases
        </a>
        <div class="inv-show-hero__top">
            <div>
                <h1 class="inv-show-hero__title">{{ $purchase->purchase_number }}</h1>
                <div style="margin-top:0.5rem;">
                    @include('partials.purchase-status-badge', ['status' => $purchase->status])
                </div>
                <div class="inv-show-hero__meta">
                    <span class="inv-show-hero__meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $purchase->purchase_date?->format('M d, Y') ?? '—' }}
                    </span>
                    @if($purchase->supplier)
                        <span class="inv-show-hero__meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $purchase->supplier->name }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="inv-page-header__actions">
                <a href="{{ route('purchases.index') }}" class="btn btn--secondary">Back</a>
                @if($canModify && $purchase->canReceive())
                    <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn--secondary">Edit</a>
                    @if($purchase->status === 'pending')
                        <form method="post" action="{{ route('purchases.ordered', $purchase) }}" style="display:inline;">
                            @csrf
                            <button class="btn btn--secondary" type="submit">Mark Ordered</button>
                        </form>
                    @endif
                    <a href="{{ route('purchases.receive.form', $purchase) }}" class="btn btn--primary">Receive &amp; Stock In</a>
                    <form method="post" action="{{ route('purchases.cancel', $purchase) }}" data-confirm="Cancel this purchase? No stock will change." data-confirm-title="Cancel purchase" style="display:inline;">
                        @csrf
                        <button class="btn btn--danger" type="submit">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    {{-- Status workflow --}}
    <nav class="pur-workflow @if($isCancelled) pur-workflow--cancelled @endif" aria-label="Purchase lifecycle">
        @if($isCancelled)
            <div class="pur-workflow__step is-complete">
                <span class="pur-workflow__dot">✓</span>
                <span class="pur-workflow__label">Created</span>
            </div>
            <div class="pur-workflow__step is-active">
                <span class="pur-workflow__dot">×</span>
                <span class="pur-workflow__label">Cancelled</span>
            </div>
        @else
            <div class="pur-workflow__step @if(in_array($purchase->status, ['pending','ordered','received'])) is-complete @endif">
                <span class="pur-workflow__dot">@if($purchase->status !== 'pending')✓@else 1 @endif</span>
                <span class="pur-workflow__label">Created</span>
            </div>
            <div class="pur-workflow__step @if(in_array($purchase->status, ['ordered','received'])) is-complete @elseif($purchase->status === 'pending') is-active @endif">
                <span class="pur-workflow__dot">@if(in_array($purchase->status, ['ordered','received']))✓@else 2 @endif</span>
                <span class="pur-workflow__label">Ordered</span>
            </div>
            <div class="pur-workflow__step @if($isReceived) is-complete @elseif($purchase->status === 'ordered') is-active @endif">
                <span class="pur-workflow__dot">@if($isReceived)✓@else 3 @endif</span>
                <span class="pur-workflow__label">Received</span>
            </div>
        @endif
    </nav>

    {{-- Summary cards --}}
    <div class="inv-summary-grid">
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Supplier</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $purchase->supplier?->name ?? '—' }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Total Cost</div>
            <div class="inv-summary-card__value">{{ money($purchase->total_cost) }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Items</div>
            <div class="inv-summary-card__value">{{ $itemCount }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Status</div>
            <div class="inv-summary-card__value" style="font-size:1rem;margin-top:0.5rem;">
                @include('partials.purchase-status-badge', ['status' => $purchase->status])
            </div>
        </div>
    </div>

    @if(!$isReceived && !$isCancelled)
        <div class="pur-banner pur-banner--info" role="note">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="pur-banner__text">Inventory quantity is <strong>not</strong> increased until this purchase is received.</p>
            </div>
        </div>
    @endif

    <div class="grid grid--2" style="gap:1.25rem;align-items:start;">
        {{-- Purchase details --}}
        <div class="card">
            <div class="card__header"><h2 class="card__title">Purchase Details</h2></div>
            <div class="card__body">
                <div class="pur-detail-sections">
                    <section aria-labelledby="pur-detail-supplier">
                        <h3 class="pur-detail-section__title" id="pur-detail-supplier">Supplier</h3>
                        <dl class="pur-detail-grid">
                            <div class="pur-detail-item">
                                <dt>Supplier</dt>
                                <dd>{{ $purchase->supplier?->name ?? '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="pur-detail-docs">
                        <h3 class="pur-detail-section__title" id="pur-detail-docs">Documents</h3>
                        <dl class="pur-detail-grid">
                            <div class="pur-detail-item">
                                <dt>PO Number</dt>
                                <dd>{{ $purchase->purchase_order_number ?? '—' }}</dd>
                            </div>
                            <div class="pur-detail-item">
                                <dt>Invoice Number</dt>
                                <dd>{{ $purchase->invoice_number ?? '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="pur-detail-receiving">
                        <h3 class="pur-detail-section__title" id="pur-detail-receiving">Receiving</h3>
                        <dl class="pur-detail-grid">
                            <div class="pur-detail-item">
                                <dt>Received By</dt>
                                <dd>{{ $purchase->receiver?->displayName() ?? '—' }}</dd>
                            </div>
                            <div class="pur-detail-item">
                                <dt>Received At</dt>
                                <dd>{{ $purchase->received_at ? ph_datetime($purchase->received_at) : '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="pur-detail-audit">
                        <h3 class="pur-detail-section__title" id="pur-detail-audit">Audit</h3>
                        <dl class="pur-detail-grid">
                            <div class="pur-detail-item">
                                <dt>Created By</dt>
                                <dd>{{ $purchase->creator?->displayName() ?? '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    @if($purchase->remarks)
                        <section aria-labelledby="pur-detail-notes">
                            <h3 class="pur-detail-section__title" id="pur-detail-notes">Notes</h3>
                            <p class="inv-detail-text text-muted">{{ $purchase->remarks }}</p>
                        </section>
                    @endif
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="card">
            <div class="card__header"><h2 class="card__title">Line Items</h2></div>
            <div class="card__body">
                {{-- Desktop table --}}
                <div class="table-wrap pur-show-lines-wrap">
                    <table class="pur-show-lines" aria-label="Purchase line items">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Ordered</th>
                                <th scope="col">Received</th>
                                <th scope="col">Unit Cost</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($purchase->items as $line)
                            @php
                                $ordered = (float) $line->quantity_ordered;
                                $received = (float) $line->quantity_received;
                                if ($received >= $ordered && $ordered > 0) {
                                    $recvStatus = 'complete';
                                    $recvLabel = 'Complete';
                                } elseif ($received > 0) {
                                    $recvStatus = 'partial';
                                    $recvLabel = 'Partial';
                                } else {
                                    $recvStatus = 'none';
                                    $recvLabel = 'Not Received';
                                }
                            @endphp
                            <tr>
                                <td>
                                    @if($line->item)
                                        <a href="{{ route('inventory.show', $line->item) }}" class="pur-purchase-link">
                                            <span class="pur-purchase-link__num">{{ $line->item->item_code }}</span>
                                            <span class="pur-purchase-link__sub">{{ $line->item->name }}</span>
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $line->quantity_ordered }}</div>
                                    <span class="pur-recv-status pur-recv-status--{{ $recvStatus }}" role="status">
                                        @if($recvStatus === 'complete')
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @elseif($recvStatus === 'partial')
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                        @endif
                                        {{ $recvLabel }}
                                    </span>
                                </td>
                                <td>{{ $line->quantity_received }}</td>
                                <td>{{ money($line->unit_cost) }}</td>
                                <td><span class="pur-amount">{{ money($line->total_cost) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="pur-show-line-cards" aria-label="Purchase line items">
                    @foreach($purchase->items as $line)
                        @php
                            $ordered = (float) $line->quantity_ordered;
                            $received = (float) $line->quantity_received;
                            if ($received >= $ordered && $ordered > 0) {
                                $recvStatus = 'complete';
                                $recvLabel = 'Complete';
                            } elseif ($received > 0) {
                                $recvStatus = 'partial';
                                $recvLabel = 'Partial';
                            } else {
                                $recvStatus = 'none';
                                $recvLabel = 'Not Received';
                            }
                        @endphp
                        <article class="pur-show-line-card">
                            @if($line->item)
                                <div class="pur-show-line-card__code">
                                    <a href="{{ route('inventory.show', $line->item) }}">{{ $line->item->item_code }}</a>
                                </div>
                                <div class="pur-show-line-card__name">{{ $line->item->name }}</div>
                            @else
                                <div class="pur-show-line-card__name">—</div>
                            @endif
                            <span class="pur-recv-status pur-recv-status--{{ $recvStatus }}" role="status" style="margin-bottom:0.75rem;display:inline-flex;">
                                @if($recvStatus === 'complete') ✓ @elseif($recvStatus === 'partial') ⚠ @endif
                                {{ $recvLabel }}
                            </span>
                            <dl class="pur-show-line-card__grid">
                                <div><dt>Ordered</dt><dd>{{ $line->quantity_ordered }}</dd></div>
                                <div><dt>Received</dt><dd>{{ $line->quantity_received }}</dd></div>
                                <div><dt>Unit Cost</dt><dd>{{ money($line->unit_cost) }}</dd></div>
                                <div><dt>Total</dt><dd class="pur-amount">{{ money($line->total_cost) }}</dd></div>
                            </dl>
                        </article>
                    @endforeach
                </div>

                <p class="form-hint mt-2">Total cost = Quantity received × Unit cost (after receiving).</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/purchases.js') }}"></script>
<script>
PurchasesModule.initDropdowns(document);
</script>
@endpush
