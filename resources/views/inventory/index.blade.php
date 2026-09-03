@extends('layouts.app')

@section('title', 'Inventory')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
@endpush

@section('content')
<div class="inv-module">
    {{-- Page header --}}
    <header class="inv-page-header">
        <div class="inv-page-header__left">
            <span class="inv-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
            <div>
                <h1 class="inv-page-header__title">Inventory</h1>
                <p class="inv-page-header__desc">Search, filter, and manage all inventory items</p>
                <p class="inv-page-header__count" id="inventory-count" aria-live="polite"></p>
            </div>
        </div>
        <div class="inv-page-header__actions">
            @if(auth()->user()->canModifyInventory())
                <a href="{{ route('inventory.create') }}" class="btn btn--primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Item
                </a>
            @endif
            <a href="{{ route('inventory.export', request()->query()) }}" class="btn btn--secondary" id="inventory-export" data-export-url="{{ route('inventory.export') }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Excel
            </a>
        </div>
    </header>

    {{-- Filters --}}
    <div class="card inv-filters">
        <div class="card__body">
            <form id="inventory-filters" method="get" action="{{ route('inventory.index') }}">
                <div class="inv-filters__top">
                    <div class="inv-category-filter">
                        <label class="form-label" for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-select" aria-label="Filter by category">
                            <option value="">All categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inv-search">
                        <label class="form-label" for="search">Item</label>
                        <div class="inv-search__field">
                            <svg class="inv-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" name="search" id="search" class="inv-search__input" value="{{ request('search') }}" placeholder="Name, brand, model, code…" aria-label="Search inventory item" aria-describedby="inv-search-scope">
                        </div>
                    </div>
                    <div class="inv-part-filter">
                        <label class="form-label" for="part_number_filter">Part Number</label>
                        <input type="search" name="part_number" id="part_number_filter" class="form-control inv-part-filter__input" value="{{ request('part_number') }}" placeholder="e.g. PN-000001 or OF-001" aria-label="Search by part number" autocomplete="off">
                    </div>
                    <div class="inv-filters__actions">
                        <button type="button" class="btn btn--secondary inv-filters__toggle" id="inv-filters-toggle" aria-expanded="false" aria-controls="inv-filters-advanced inv-filters-mobile">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filters
                        </button>
                        <button type="button" class="btn btn--ghost" id="inv-filters-clear">Clear Filters</button>
                    </div>
                </div>
                <p class="inv-search-scope" id="inv-search-scope" hidden></p>
                <p class="inv-results-context" id="inv-results-context" hidden></p>

                {{-- Desktop advanced filters --}}
                <div class="inv-filters__advanced inv-filters__advanced-desktop" id="inv-filters-advanced">
                    <div class="inv-filters__advanced-inner">
                        <div class="inv-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="location_id">Location</label>
                                <select name="location_id" id="location_id" class="form-select">
                                    <option value="">All locations</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="inventory_type">Inventory Type</label>
                                <select name="inventory_type" id="inventory_type" class="form-select">
                                    <option value="">All types</option>
                                    <option value="consumable" @selected(request('inventory_type') === 'consumable')>Consumable</option>
                                    <option value="asset" @selected(request('inventory_type') === 'asset')>Asset</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="status">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All statuses</option>
                                    @foreach(['Available', 'Borrowed', 'Under Maintenance', 'Out of Stock'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="condition">Condition</label>
                                <select name="condition" id="condition" class="form-select">
                                    <option value="">All conditions</option>
                                    @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                                        <option value="{{ $cond }}" @selected(request('condition') === $cond)>{{ $cond }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-check" style="min-height:var(--inv-input-h); align-items:center;">
                                    <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock'))>
                                    Low stock only
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="inv-active-filters" id="inv-active-filters" aria-live="polite"></div>
            </form>
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div class="inv-filters__drawer-backdrop" id="inv-filters-backdrop" aria-hidden="true"></div>
    <div class="inv-filters__advanced-mobile" id="inv-filters-mobile" role="dialog" aria-label="Filter inventory" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="inv-filters__grid">
            <div class="form-group">
                <label class="form-label" for="category_id_mobile">Category</label>
                <select id="category_id_mobile" class="form-select" data-sync="category_id">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="location_id_mobile">Location</label>
                <select id="location_id_mobile" class="form-select" data-sync="location_id">
                    <option value="">All locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="inventory_type_mobile">Inventory Type</label>
                <select id="inventory_type_mobile" class="form-select" data-sync="inventory_type">
                    <option value="">All types</option>
                    <option value="consumable" @selected(request('inventory_type') === 'consumable')>Consumable</option>
                    <option value="asset" @selected(request('inventory_type') === 'asset')>Asset</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="status_mobile">Status</label>
                <select id="status_mobile" class="form-select" data-sync="status">
                    <option value="">All statuses</option>
                    @foreach(['Available', 'Borrowed', 'Under Maintenance', 'Out of Stock'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="condition_mobile">Condition</label>
                <select id="condition_mobile" class="form-select" data-sync="condition">
                    <option value="">All conditions</option>
                    @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                        <option value="{{ $cond }}" @selected(request('condition') === $cond)>{{ $cond }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" id="low_stock_mobile" data-sync="low_stock" value="1" @checked(request()->boolean('low_stock'))>
                    Low stock only
                </label>
            </div>
        </div>
        <div class="btn-group mt-2">
            <button type="button" class="btn btn--primary btn--block" id="inv-filters-mobile-apply">Apply Filters</button>
        </div>
    </div>

    {{-- Table panel --}}
    <div class="card">
        <div class="card__body inv-table-panel" id="inventory-table-panel">
            <div class="table-wrap">
                <table class="inv-data-table" id="inventory-table" style="display:none;" aria-label="Inventory items">
                    <thead>
                        <tr>
                            <th scope="col">Part Number</th>
                            <th scope="col">Item</th>
                            <th scope="col">Category</th>
                            <th scope="col">Location</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Status</th>
                            <th scope="col">Condition</th>
                            <th scope="col">Total Value</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="inventory-tbody"></tbody>
                </table>
            </div>

            {{-- Empty state --}}
            <div class="inv-state" id="inventory-empty" hidden>
                <svg class="inv-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <h2 class="inv-state__title">No inventory items found</h2>
                <p class="inv-state__text" id="inventory-empty-text">Try adjusting your filters or create a new inventory item.</p>
                @if(auth()->user()->canModifyInventory())
                    <a href="{{ route('inventory.create') }}" class="btn btn--primary">Add New Item</a>
                @endif
            </div>

            {{-- Error state --}}
            <div class="inv-state inv-state--error" id="inventory-error" hidden>
                <svg class="inv-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <h2 class="inv-state__title">Unable to load inventory</h2>
                <p class="inv-state__text" id="inventory-error-msg">Something went wrong. Please try again.</p>
                <button type="button" class="btn btn--primary" id="inventory-retry">Retry</button>
            </div>

            <nav class="inv-pagination" id="inventory-pagination" aria-label="Inventory pagination" hidden></nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
<script>
(function () {
    // Sync mobile filter drawer with main form
    document.querySelectorAll('[data-sync]').forEach(function (el) {
        el.addEventListener('change', function () {
            var name = el.dataset.sync;
            var target = document.querySelector('[name="' + name + '"]');
            if (!target) return;
            if (el.type === 'checkbox') {
                target.checked = el.checked;
            } else {
                target.value = el.value;
            }
        });
    });

    document.getElementById('inv-filters-mobile-apply')?.addEventListener('click', function () {
        document.querySelectorAll('[data-sync]').forEach(function (el) {
            el.dispatchEvent(new Event('change'));
        });
    });

    InventoryModule.initIndex({
        baseUrl: @json(route('inventory.index')),
        inventoryBase: @json(url('inventory')),
        canModify: @json(auth()->user()->canModifyInventory()),
        filterLabels: {
            category_id: 'Category',
            part_number: 'Part Number',
            location_id: 'Location',
            inventory_type: 'Type',
            status: 'Status',
            condition: 'Condition',
            low_stock: 'Stock',
        },
    });
})();
</script>
@endpush
