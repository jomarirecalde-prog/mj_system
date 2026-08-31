@extends('layouts.app')

@section('title', 'Purchases')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
<link rel="stylesheet" href="{{ asset('css/purchases.css') }}">
@endpush

@section('content')
<div class="pur-module inv-module">
    <header class="inv-page-header">
        <div class="inv-page-header__left">
            <span class="inv-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </span>
            <div>
                <h1 class="inv-page-header__title">Purchases &amp; Receiving</h1>
                <p class="inv-page-header__desc">Manage purchase orders, suppliers, and inventory receiving.</p>
            </div>
        </div>
        <div class="inv-page-header__actions">
            @if(auth()->user()->canModifyInventory())
                <a href="{{ route('purchases.create') }}" class="btn btn--primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Purchase
                </a>
            @endif
        </div>
    </header>

    {{-- Filters --}}
    <div class="card inv-filters">
        <div class="card__body">
            <form id="pur-filters" method="get" action="{{ route('purchases.index') }}">
                <div class="inv-filters__top">
                    <div class="inv-search">
                        <svg class="inv-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" id="pur-search" class="inv-search__input" value="{{ request('search') }}" placeholder="Search purchase number, PO, or invoice…" aria-label="Search purchases">
                    </div>
                    <div class="inv-filters__actions">
                        <button type="button" class="btn btn--secondary inv-filters__toggle" id="pur-filters-toggle" aria-expanded="false" aria-controls="pur-filters-advanced pur-filters-mobile">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filters
                        </button>
                        <button type="submit" class="btn btn--primary">Apply</button>
                        <button type="button" class="btn btn--ghost" id="pur-filters-clear">Clear</button>
                    </div>
                </div>

                <div class="inv-filters__advanced inv-filters__advanced-desktop" id="pur-filters-advanced">
                    <div class="inv-filters__advanced-inner">
                        <div class="inv-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="pur-status">Status</label>
                                <select name="status" id="pur-status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach(['pending','ordered','received','cancelled'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="inv-active-filters" id="pur-active-filters" aria-live="polite"></div>
            </form>
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div class="inv-filters__drawer-backdrop" id="pur-filters-backdrop" aria-hidden="true"></div>
    <div class="inv-filters__advanced-mobile" id="pur-filters-mobile" role="dialog" aria-label="Filter purchases" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="inv-filters__grid">
            <div class="form-group">
                <label class="form-label" for="pur-status-mobile">Status</label>
                <select id="pur-status-mobile" class="form-select" data-pur-sync="status">
                    <option value="">All Statuses</option>
                    @foreach(['pending','ordered','received','cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="btn-group mt-2">
            <button type="button" class="btn btn--primary btn--block" id="pur-filters-mobile-apply">Apply Filters</button>
        </div>
    </div>

    {{-- Table / cards --}}
    <div class="card">
        <div class="card__body">
            @if($purchases->isEmpty())
                <div class="pur-empty">
                    <svg class="pur-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <h2 class="pur-empty__title">No purchases found</h2>
                    <p class="pur-empty__text">
                        @if(request()->hasAny(['search', 'status']))
                            Try adjusting your filters or search terms.
                        @else
                            Create a purchase order to start receiving inventory.
                        @endif
                    </p>
                    @if(auth()->user()->canModifyInventory())
                        <a href="{{ route('purchases.create') }}" class="btn btn--primary">New Purchase</a>
                    @endif
                </div>
            @else
                {{-- Desktop table --}}
                <div class="table-wrap pur-table-desktop">
                    <table class="pur-data-table" aria-label="Purchases">
                        <thead>
                            <tr>
                                <th scope="col">Purchase</th>
                                <th scope="col">Supplier</th>
                                <th scope="col">Date</th>
                                <th scope="col">PO / Invoice</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="pur-col-actions"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($purchases as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('purchases.show', $row) }}" class="pur-purchase-link">
                                        <span class="pur-purchase-link__num">{{ $row->purchase_number }}</span>
                                        @if($row->purchase_order_number)
                                            <span class="pur-purchase-link__sub">{{ $row->purchase_order_number }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td>{{ $row->supplier?->name ?? '—' }}</td>
                                <td>{{ $row->purchase_date?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    <span>{{ $row->purchase_order_number ?? '—' }}</span>
                                    @if($row->invoice_number)
                                        <span class="text-muted"> / {{ $row->invoice_number }}</span>
                                    @endif
                                </td>
                                <td><span class="pur-amount">{{ money($row->total_cost) }}</span></td>
                                <td>@include('partials.purchase-status-badge', ['status' => $row->status])</td>
                                <td class="pur-col-actions">
                                    <a href="{{ route('purchases.show', $row) }}" class="btn btn--ghost btn--sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="pur-cards" aria-label="Purchases">
                    @foreach($purchases as $row)
                        <article class="pur-card">
                            <div class="pur-card__head">
                                <div>
                                    <a href="{{ route('purchases.show', $row) }}" class="pur-purchase-link">
                                        <span class="pur-purchase-link__num">{{ $row->purchase_number }}</span>
                                    </a>
                                </div>
                                @include('partials.purchase-status-badge', ['status' => $row->status])
                            </div>
                            <dl class="pur-card__meta">
                                <div>
                                    <dt>Supplier</dt>
                                    <dd>{{ $row->supplier?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Date</dt>
                                    <dd>{{ $row->purchase_date?->format('M d, Y') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Total</dt>
                                    <dd class="pur-amount">{{ money($row->total_cost) }}</dd>
                                </div>
                                @if($row->purchase_order_number)
                                    <div>
                                        <dt>PO</dt>
                                        <dd>{{ $row->purchase_order_number }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <div class="pur-card__actions">
                                <a href="{{ route('purchases.show', $row) }}" class="btn btn--secondary btn--sm btn--block">View Purchase</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pur-pagination">
                    @include('partials.pagination', ['paginator' => $purchases->withQueryString()])
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/purchases.js') }}"></script>
<script>
PurchasesModule.initIndexFilters();
</script>
@endpush
