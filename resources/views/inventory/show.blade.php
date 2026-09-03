@extends('layouts.app')

@section('title', $item->part_number ?: $item->item_code)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
@endpush

@section('content')
@php
    $canModify = auth()->user()->canModifyInventory();
    $hasActiveBorrow = $item->borrowings->contains(fn ($b) => $b->status === 'borrowed');
@endphp

<div class="inv-module">
    {{-- Hero header --}}
    <header class="inv-show-hero">
        <a href="{{ route('inventory.index') }}" class="inv-back-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Inventory
        </a>
        <div class="inv-show-hero__top">
            <div>
                <h1 class="inv-show-hero__title">{{ $item->name }}</h1>
                <p class="inv-show-hero__part">{{ $item->part_number }}</p>
                <p class="inv-show-hero__code">Item Code: {{ $item->item_code }}</p>
                <div style="margin-top:0.5rem;">@include('partials.status-badge', ['status' => $item->status])</div>
                <div class="inv-show-hero__meta">
                    @if($item->category)
                        <span class="inv-show-hero__meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                            {{ $item->category->name }}
                        </span>
                    @endif
                    @if($item->location)
                        <span class="inv-show-hero__meta-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $item->location->name }}
                        </span>
                    @endif
                    <span class="inv-show-hero__meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        {{ $item->isAsset() ? 'Asset' : 'Consumable' }}
                    </span>
                </div>
            </div>
            <div class="inv-page-header__actions">
                @if($canModify)
                    <a href="{{ route('inventory.edit', $item) }}" class="btn btn--primary">Edit Item</a>
                    <div class="inv-dropdown" id="inv-actions-menu">
                        <button type="button" class="btn btn--secondary inv-dropdown__trigger" aria-haspopup="true" aria-expanded="false" aria-label="Item actions">
                            Actions
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="inv-dropdown__menu" hidden role="menu">
                            <a href="{{ route('stock.in.form', $item) }}" class="inv-dropdown__item" role="menuitem">Stock In</a>
                            @if($item->isConsumable())
                                <a href="{{ route('stock.out.form', $item) }}" class="inv-dropdown__item" role="menuitem">Issue</a>
                                <a href="{{ route('stock.out.form', ['item' => $item, 'mode' => 'consumption']) }}" class="inv-dropdown__item" role="menuitem">Consume</a>
                                <a href="{{ route('stock.return.form', $item) }}" class="inv-dropdown__item" role="menuitem">Return</a>
                            @else
                                <a href="{{ route('borrow.create', $item) }}" class="inv-dropdown__item" role="menuitem">Borrow</a>
                            @endif
                            <a href="{{ route('transfer.create', $item) }}" class="inv-dropdown__item" role="menuitem">Transfer</a>
                            <a href="{{ route('stock.adjust.form', $item) }}" class="inv-dropdown__item" role="menuitem">Adjust Stock</a>
                            <a href="{{ route('qr.show', $item) }}" class="inv-dropdown__item" role="menuitem">QR Code</a>
                            <form action="{{ route('inventory.archive', $item) }}" method="post" data-confirm="Archive this item?" data-confirm-title="Archive item">
                                @csrf
                                <button type="submit" class="inv-dropdown__item inv-dropdown__item--danger" role="menuitem">Archive</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </header>

    {{-- Summary cards --}}
    <div class="inv-summary-grid">
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Available Quantity</div>
            <div class="inv-summary-card__value">{{ $item->quantity }} {{ $item->unit }}</div>
            @if($item->isLowStock())
                <div class="inv-summary-card__sub" role="status">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    Low Stock
                </div>
            @endif
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Total Value</div>
            <div class="inv-summary-card__value">{{ money($item->total_value) }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Inventory Type</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $item->isAsset() ? 'Asset' : 'Consumable' }}</div>
        </div>
        <div class="inv-summary-card">
            <div class="inv-summary-card__label">Condition</div>
            <div class="inv-summary-card__value" style="font-size:1.1rem;">{{ $item->condition }}</div>
        </div>
    </div>

    {{-- Main layout: QR + Details --}}
    <div class="inv-show-layout">
        {{-- QR Code card --}}
        <div class="card">
            <div class="card__body inv-qr-card">
                <div class="inv-qr-card__label">QR Identity</div>
                <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->margin(1)->generate($item->qr_code) !!}</div>
                <p class="inv-qr-card__code">{{ $item->qr_code }}</p>
                @if($canModify)
                    <div class="btn-group mt-1" style="justify-content:center;">
                        <a href="{{ route('qr.print.single', $item) }}" class="btn btn--secondary btn--sm" target="_blank" rel="noopener">Print Label</a>
                        <a href="{{ route('qr.download', $item) }}" class="btn btn--ghost btn--sm">Download QR</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Details tabs --}}
        <div class="card">
            <div class="inv-tabs" role="tablist" aria-label="Item details">
                <button type="button" class="inv-tabs__btn is-active" role="tab" aria-selected="true" data-tab="overview" id="tab-overview">Overview</button>
                <button type="button" class="inv-tabs__btn" role="tab" aria-selected="false" data-tab="product" id="tab-product">Product Info</button>
                <button type="button" class="inv-tabs__btn" role="tab" aria-selected="false" data-tab="stock" id="tab-stock">Stock</button>
                <button type="button" class="inv-tabs__btn" role="tab" aria-selected="false" data-tab="lifecycle" id="tab-lifecycle">Lifecycle</button>
                <button type="button" class="inv-tabs__btn" role="tab" aria-selected="false" data-tab="notes" id="tab-notes">Notes</button>
            </div>

            <div class="inv-tab-panel is-active" id="inv-tab-overview" role="tabpanel" aria-labelledby="tab-overview">
                <dl class="inv-detail-grid">
                    <div class="inv-detail-item"><dt>Part Number</dt><dd><strong>{{ $item->part_number }}</strong></dd></div>
                    <div class="inv-detail-item"><dt>Item Code</dt><dd>{{ $item->item_code }}</dd></div>
                    <div class="inv-detail-item"><dt>Inventory Type</dt><dd>{{ $item->isAsset() ? 'Non-consumable / Asset' : 'Consumable' }}</dd></div>
                    <div class="inv-detail-item"><dt>Category</dt><dd>{{ $item->category?->name ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Location</dt><dd>{{ $item->location?->name ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Department</dt><dd>{{ $item->department?->name ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Condition</dt><dd>{{ $item->condition }}</dd></div>
                    <div class="inv-detail-item"><dt>Status</dt><dd>@include('partials.status-badge', ['status' => $item->status])</dd></div>
                    <div class="inv-detail-item"><dt>Unit Cost</dt><dd>{{ money($item->unit_cost) }}</dd></div>
                    <div class="inv-detail-item"><dt>Selling Price</dt><dd>{{ money($item->effectiveSellingPrice()) }}</dd></div>
                </dl>
            </div>

            <div class="inv-tab-panel" id="inv-tab-product" role="tabpanel" aria-labelledby="tab-product">
                <dl class="inv-detail-grid">
                    <div class="inv-detail-item"><dt>Part Number</dt><dd><strong>{{ $item->part_number }}</strong></dd></div>
                    <div class="inv-detail-item"><dt>Brand</dt><dd>{{ $item->brand ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Model</dt><dd>{{ $item->model ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Serial Number</dt><dd>{{ $item->serial_number ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Supplier</dt><dd>{{ $item->supplier?->name ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Assigned To</dt><dd>{{ $item->assignee?->displayName() ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="inv-tab-panel" id="inv-tab-stock" role="tabpanel" aria-labelledby="tab-stock">
                <dl class="inv-detail-grid">
                    <div class="inv-detail-item"><dt>Available Quantity</dt><dd><strong>{{ $item->quantity }} {{ $item->unit }}</strong></dd></div>
                    <div class="inv-detail-item"><dt>Minimum Stock</dt><dd>{{ $item->minimum_stock }}</dd></div>
                    <div class="inv-detail-item"><dt>Reorder Level</dt><dd>{{ $item->reorder_level }}</dd></div>
                    <div class="inv-detail-item"><dt>Total Value</dt><dd>{{ money($item->total_value) }}</dd></div>
                </dl>
            </div>

            <div class="inv-tab-panel" id="inv-tab-lifecycle" role="tabpanel" aria-labelledby="tab-lifecycle">
                <dl class="inv-detail-grid">
                    <div class="inv-detail-item"><dt>Date Acquired</dt><dd>{{ $item->date_acquired?->format('M d, Y') ?? '—' }}</dd></div>
                    <div class="inv-detail-item"><dt>Warranty Expiration</dt><dd>{{ $item->warranty_expiration?->format('M d, Y') ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="inv-tab-panel" id="inv-tab-notes" role="tabpanel" aria-labelledby="tab-notes">
                @if($item->description)
                    <div style="margin-bottom:1.25rem;">
                        <h3 class="inv-form-section__title" style="margin-bottom:0.5rem;">Description</h3>
                        <p class="inv-detail-text">{{ $item->description }}</p>
                    </div>
                @endif
                @if($item->remarks)
                    <div>
                        <h3 class="inv-form-section__title" style="margin-bottom:0.5rem;">Remarks</h3>
                        <p class="inv-detail-text text-muted">{{ $item->remarks }}</p>
                    </div>
                @endif
                @if(!$item->description && !$item->remarks)
                    <p class="text-muted">No description or remarks recorded.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- History sections --}}
    <div class="inv-history-grid mt-2" id="transactions">
        {{-- Transaction ledger --}}
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Transaction Ledger</h2>
            </div>
            <div class="card__body table-wrap">
                @if($item->transactions->isEmpty())
                    <div class="inv-state" style="padding:1.5rem;">
                        <p class="text-muted" style="margin:0;">No transactions recorded.</p>
                    </div>
                @else
                    <table class="inv-data-table">
                        <thead>
                            <tr>
                                <th scope="col">Part Number</th>
                                <th scope="col">Item</th>
                                <th scope="col">Type</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Previous</th>
                                <th scope="col">New</th>
                                <th scope="col">Performed By</th>
                                <th scope="col">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($item->transactions as $tx)
                            @php
                                $txLabel = \App\Support\InventoryTransactionType::label($tx->type);
                                $inbound = in_array($tx->type, \App\Support\InventoryTransactionType::inbound(), true);
                                $outbound = in_array($tx->type, \App\Support\InventoryTransactionType::outbound(), true);
                                $badgeClass = $inbound ? 'inv-tx-badge--in' : ($outbound ? 'inv-tx-badge--out' : ($tx->type === 'adjustment' ? 'inv-tx-badge--adjust' : ($tx->type === 'borrow' ? 'inv-tx-badge--borrow' : '')));
                            @endphp
                            <tr>
                                <td><span class="inv-part-number">{{ $item->part_number }}</span></td>
                                <td>{{ $item->name }}</td>
                                <td><span class="inv-tx-badge {{ $badgeClass }}">{{ $txLabel }}</span></td>
                                <td>{{ $tx->quantity }}</td>
                                <td>{{ $tx->quantity_before }}</td>
                                <td>{{ $tx->quantity_after }}</td>
                                <td>{{ $tx->performer?->displayName() ?? '—' }}</td>
                                <td>{{ ph_datetime($tx->created_at) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Borrowing history --}}
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Borrowing History</h2>
                @if($hasActiveBorrow)
                    <span class="badge badge--warn">Currently Borrowed</span>
                @endif
            </div>
            <div class="card__body table-wrap">
                @if($item->borrowings->isEmpty())
                    <div class="inv-state" style="padding:1.5rem;">
                        <p class="text-muted" style="margin:0;">No borrowing records yet.</p>
                    </div>
                @else
                    <table class="inv-data-table">
                        <thead>
                            <tr>
                                <th scope="col">Borrower</th>
                                <th scope="col">Status</th>
                                <th scope="col">Borrowed</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($item->borrowings as $br)
                            <tr class="@if($br->status === 'borrowed') inv-borrow-active @endif">
                                <td>{{ $br->borrower_name }}</td>
                                <td>
                                    <span class="inv-borrow-status inv-borrow-status--{{ $br->status === 'borrowed' ? 'borrowed' : 'returned' }}">
                                        {{ ucfirst($br->status) }}
                                    </span>
                                </td>
                                <td>{{ $br->date_borrowed?->format('M d, Y') }}</td>
                                <td>
                                    @if($br->status === 'borrowed' && $canModify)
                                        <a href="{{ route('borrow.return', $br) }}" class="btn btn--sm btn--primary">Return</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Change history --}}
    <div class="card mt-2">
        <div class="card__header"><h2 class="card__title">Change History</h2></div>
        <div class="card__body">
            @if($item->history->isEmpty())
                <div class="inv-state" style="padding:1.5rem;">
                    <p class="text-muted" style="margin:0;">No history entries.</p>
                </div>
            @else
                <div class="inv-timeline" role="list">
                    @foreach($item->history as $h)
                        <article class="inv-timeline__item" role="listitem">
                            <span class="inv-timeline__dot" aria-hidden="true"></span>
                            <div>
                                <p class="inv-timeline__action">
                                    {{ $h->user?->displayName() ?? 'System' }}
                                    updated {{ $h->transaction_type }}
                                </p>
                                <p class="inv-timeline__change">
                                    <strong>{{ Str::limit($h->from_value, 40) ?: '—' }}</strong>
                                    →
                                    <strong>{{ Str::limit($h->to_value, 40) ?: '—' }}</strong>
                                </p>
                                <p class="inv-timeline__meta">{{ ph_datetime($h->occurred_at ?? $h->created_at) }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
<script>
InventoryModule.initDropdowns(document);
InventoryModule.initShowTabs();
</script>
@endpush
