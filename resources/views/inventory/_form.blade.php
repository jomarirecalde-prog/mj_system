@php
    $item = $item ?? null;
    $isEdit = $item !== null;
    $invType = old('inventory_type', $item?->inventory_type ?? 'consumable');
    $nextPartNumber = $nextPartNumber ?? null;
@endphp

<div class="inv-form">
    {{-- 1. Basic Information --}}
    <section class="inv-form-section" aria-labelledby="inv-section-basic">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-basic">Basic Information</h2>
                <p class="inv-form-section__desc">Core item identity and classification type.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="part_number">Part Number @if($isEdit)<span class="req" aria-hidden="true">*</span>@endif</label>
                    <input type="text" name="part_number" id="part_number" class="form-control @error('part_number') is-invalid @enderror" value="{{ old('part_number', $item?->part_number) }}" maxlength="100" autocomplete="off" {{ $isEdit ? 'required' : '' }} @error('part_number') aria-invalid="true" aria-describedby="part_number_hint part_number_error" @else aria-describedby="part_number_hint" @enderror placeholder="{{ $isEdit ? $item->part_number : 'e.g. OF-001' }}">
                    <span class="form-hint" id="part_number_hint">
                        @if($isEdit)
                            Current Part Number: <strong>{{ $item->part_number }}</strong>. Changing this value must remain unique across all inventory items.
                        @else
                            Enter a unique part number such as <strong>OF-001</strong>. Leave blank to auto-generate ({{ $nextPartNumber ?? 'PN-000001' }}).
                        @endif
                    </span>
                    @error('part_number')
                        <span class="form-error" id="part_number_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="item_code">Item Code <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" name="item_code" id="item_code" class="form-control @error('item_code') is-invalid @enderror" value="{{ old('item_code', $item?->item_code) }}" required {{ $isEdit ? 'readonly aria-readonly="true"' : '' }} @error('item_code') aria-invalid="true" aria-describedby="item_code_error" @enderror placeholder="e.g. IT-2026-001">
                    @error('item_code')
                        <span class="form-error" id="item_code_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="qr_code">QR Code</label>
                    <input type="text" name="qr_code" id="qr_code" class="form-control @error('qr_code') is-invalid @enderror" value="{{ old('qr_code', $item?->qr_code) }}" placeholder="{{ $isEdit ? $item->qr_code : 'Auto-generated if empty' }}" @error('qr_code') aria-invalid="true" aria-describedby="qr_code_hint qr_code_error" @else aria-describedby="qr_code_hint" @enderror>
                    <span class="form-hint" id="qr_code_hint">
                        @if($isEdit)
                            Current QR: <strong>{{ $item->qr_code }}</strong>. Leave blank to keep existing, or enter a new value.
                        @else
                            Leave blank to automatically generate a unique QR code.
                        @endif
                    </span>
                    @error('qr_code')
                        <span class="form-error" id="qr_code_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="name">Item Name <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item?->name) }}" required @error('name') aria-invalid="true" aria-describedby="name_error" @enderror placeholder="e.g. Dell Latitude Laptop">
                    @error('name')
                        <span class="form-error" id="name_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="description">Description</label>
                    <textarea name="description" id="description" class="form-textarea @error('description') is-invalid @enderror" rows="3" @error('description') aria-invalid="true" aria-describedby="description_error" @enderror placeholder="Optional description of the item">{{ old('description', $item?->description) }}</textarea>
                    @error('description')
                        <span class="form-error" id="description_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="inventory_type">Inventory Type <span class="req" aria-hidden="true">*</span></label>
                    <select name="inventory_type" id="inventory_type" class="inv-type-select__native @error('inventory_type') is-invalid @enderror" required @error('inventory_type') aria-invalid="true" aria-describedby="inv-type-hint inventory_type_error" @else aria-describedby="inv-type-hint" @enderror>
                        <option value="consumable" @selected($invType === 'consumable')>Consumable</option>
                        <option value="asset" @selected($invType === 'asset')>Asset</option>
                    </select>
                    <div class="inv-type-select" role="group" aria-label="Inventory type">
                        <div class="inv-type-option @if($invType === 'consumable') is-selected @endif" data-value="consumable" tabindex="0" role="button" aria-pressed="{{ $invType === 'consumable' ? 'true' : 'false' }}">
                            <div class="inv-type-option__title">Consumable</div>
                            <div class="inv-type-option__desc">Stock decreases when issued or consumed.</div>
                        </div>
                        <div class="inv-type-option @if($invType === 'asset') is-selected @endif" data-value="asset" tabindex="0" role="button" aria-pressed="{{ $invType === 'asset' ? 'true' : 'false' }}">
                            <div class="inv-type-option__title">Asset</div>
                            <div class="inv-type-option__desc">Tracked individually and can be borrowed and returned.</div>
                        </div>
                    </div>
                    <p class="inv-type-hint" id="inv-type-hint"></p>
                    @error('inventory_type')
                        <span class="form-error" id="inventory_type_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Classification & Assignment --}}
    <section class="inv-form-section" aria-labelledby="inv-section-classification">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-classification">Classification &amp; Assignment</h2>
                <p class="inv-form-section__desc">Organize the item by category, location, and ownership.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" @error('category_id') aria-invalid="true" aria-describedby="category_id_error" @enderror>
                        <option value="">— None —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $item?->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<span class="form-error" id="category_id_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="location_id">Location</label>
                    <select name="location_id" id="location_id" class="form-select @error('location_id') is-invalid @enderror" @error('location_id') aria-invalid="true" aria-describedby="location_id_error" @enderror>
                        <option value="">— None —</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected(old('location_id', $item?->location_id) == $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<span class="form-error" id="location_id_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department</label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" @error('department_id') aria-invalid="true" aria-describedby="department_id_error" @enderror>
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id', $item?->department_id) == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<span class="form-error" id="department_id_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" @error('supplier_id') aria-invalid="true" aria-describedby="supplier_id_error" @enderror>
                        <option value="">— None —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id', $item?->supplier_id) == $sup->id)>{{ $sup->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<span class="form-error" id="supplier_id_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="assigned_to">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" @error('assigned_to') aria-invalid="true" aria-describedby="assigned_to_error" @enderror>
                        <option value="">— Unassigned —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(old('assigned_to', $item?->assigned_to) == $u->id)>{{ $u->displayName() }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')<span class="form-error" id="assigned_to_error" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Product Details --}}
    <section class="inv-form-section" aria-labelledby="inv-section-product">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-product">Product Details</h2>
                <p class="inv-form-section__desc">Physical attributes, condition, and availability status.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="brand">Brand</label>
                    <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand', $item?->brand) }}" @error('brand') aria-invalid="true" aria-describedby="brand_error" @enderror placeholder="e.g. Dell">
                    @error('brand')<span class="form-error" id="brand_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="model">Model</label>
                    <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', $item?->model) }}" @error('model') aria-invalid="true" aria-describedby="model_error" @enderror placeholder="e.g. Latitude 5540">
                    @error('model')<span class="form-error" id="model_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="serial_number">Serial Number</label>
                    <input type="text" name="serial_number" id="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $item?->serial_number) }}" @error('serial_number') aria-invalid="true" aria-describedby="serial_number_error" @enderror placeholder="Manufacturer serial number">
                    @error('serial_number')<span class="form-error" id="serial_number_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="condition">Condition</label>
                    <select name="condition" id="condition" class="form-select @error('condition') is-invalid @enderror" @error('condition') aria-invalid="true" aria-describedby="condition_error" @enderror>
                        @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                            <option value="{{ $cond }}" @selected(old('condition', $item?->condition ?? 'New') === $cond)>{{ $cond }}</option>
                        @endforeach
                    </select>
                    @error('condition')<span class="form-error" id="condition_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" @error('status') aria-invalid="true" aria-describedby="status_error" @enderror>
                        @foreach(['Available', 'Borrowed', 'Under Maintenance', 'Archived', 'Out of Stock'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $item?->status ?? 'Available') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('status')<span class="form-error" id="status_error" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Stock & Pricing --}}
    <section class="inv-form-section" aria-labelledby="inv-section-stock">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-stock">Stock &amp; Pricing</h2>
                <p class="inv-form-section__desc">Manage quantity, pricing, and inventory thresholds.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="quantity">{{ $isEdit ? 'Current Quantity' : 'Initial Quantity' }}</label>
                    @if($isEdit)
                        <div class="inv-qty-readonly">
                            <span class="inv-qty-readonly__value" id="quantity">{{ $item->quantity }} {{ $item->unit }}</span>
                            <span class="form-hint">Quantity cannot be edited directly. Use stock transactions to change quantity.</span>
                            @if(auth()->user()->canModifyInventory())
                                <div class="inv-qty-readonly__links">
                                    <a href="{{ route('stock.in.form', $item) }}" class="btn btn--secondary btn--sm">Stock In</a>
                                    @if($item->isConsumable())
                                        <a href="{{ route('stock.out.form', $item) }}" class="btn btn--secondary btn--sm">Issue</a>
                                    @endif
                                    <a href="{{ route('stock.adjust.form', $item) }}" class="btn btn--secondary btn--sm">Adjust</a>
                                </div>
                            @endif
                        </div>
                    @else
                        <input type="number" step="0.01" min="0" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 0) }}" @error('quantity') aria-invalid="true" aria-describedby="quantity_hint quantity_error" @else aria-describedby="quantity_hint" @enderror>
                        <span class="form-hint" id="quantity_hint">Recorded as an Initial Stock transaction on create.</span>
                        @error('quantity')<span class="form-error" id="quantity_error" role="alert">{{ $message }}</span>@enderror
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label" for="unit">Unit</label>
                    <input type="text" name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $item?->unit ?? 'pcs') }}" @error('unit') aria-invalid="true" aria-describedby="unit_error" @enderror placeholder="e.g. pcs, boxes">
                    @error('unit')<span class="form-error" id="unit_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="unit_cost">Unit Cost</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', $item?->unit_cost ?? 0) }}" @error('unit_cost') aria-invalid="true" aria-describedby="unit_cost_error" @enderror placeholder="0.00">
                    @error('unit_cost')<span class="form-error" id="unit_cost_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="selling_price">Selling Price</label>
                    <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', $item?->selling_price ?? $item?->unit_cost ?? 0) }}" @error('selling_price') aria-invalid="true" aria-describedby="selling_price_hint selling_price_error" @else aria-describedby="selling_price_hint" @enderror placeholder="0.00">
                    <span class="form-hint" id="selling_price_hint">Used by POS. Falls back to unit cost when zero.</span>
                    @error('selling_price')<span class="form-error" id="selling_price_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group form-group--full">
                    <div class="inv-total-value">
                        <div class="inv-total-value__label">Total Value</div>
                        <div class="inv-total-value__amount" id="total_value_display" aria-live="polite">{{ money(($item?->unit_cost ?? old('unit_cost', 0)) * ($item?->quantity ?? old('quantity', 0))) }}</div>
                        <input type="hidden" id="total_value_display_input" value="">
                        <span class="form-hint">Calculated from quantity × unit cost</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="minimum_stock">Minimum Stock</label>
                    <input type="number" step="0.01" min="0" name="minimum_stock" id="minimum_stock" class="form-control @error('minimum_stock') is-invalid @enderror" value="{{ old('minimum_stock', $item?->minimum_stock ?? setting('default_min_stock', 0)) }}" @error('minimum_stock') aria-invalid="true" aria-describedby="minimum_stock_error" @enderror>
                    @error('minimum_stock')<span class="form-error" id="minimum_stock_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="reorder_level">Reorder Level</label>
                    <input type="number" step="0.01" min="0" name="reorder_level" id="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', $item?->reorder_level ?? 0) }}" @error('reorder_level') aria-invalid="true" aria-describedby="reorder_level_error" @enderror>
                    @error('reorder_level')<span class="form-error" id="reorder_level_error" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 5. Lifecycle Information --}}
    <section class="inv-form-section" aria-labelledby="inv-section-lifecycle">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-lifecycle">Lifecycle Information</h2>
                <p class="inv-form-section__desc">Acquisition date and warranty coverage.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="date_acquired">Date Acquired</label>
                    <input type="date" name="date_acquired" id="date_acquired" class="form-control @error('date_acquired') is-invalid @enderror" value="{{ old('date_acquired', $item?->date_acquired?->format('Y-m-d')) }}" @error('date_acquired') aria-invalid="true" aria-describedby="date_acquired_error" @enderror>
                    @error('date_acquired')<span class="form-error" id="date_acquired_error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="warranty_expiration">Warranty Expiration</label>
                    <input type="date" name="warranty_expiration" id="warranty_expiration" class="form-control @error('warranty_expiration') is-invalid @enderror" value="{{ old('warranty_expiration', $item?->warranty_expiration?->format('Y-m-d')) }}" @error('warranty_expiration') aria-invalid="true" aria-describedby="warranty_expiration_error" @enderror>
                    @error('warranty_expiration')<span class="form-error" id="warranty_expiration_error" role="alert">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 6. Additional Information --}}
    <section class="inv-form-section" aria-labelledby="inv-section-remarks">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="inv-section-remarks">Additional Information</h2>
                <p class="inv-form-section__desc">Notes and internal remarks about this item.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-group">
                <label class="form-label" for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-textarea @error('remarks') is-invalid @enderror" rows="3" @error('remarks') aria-invalid="true" aria-describedby="remarks_error" @enderror placeholder="Optional internal notes">{{ old('remarks', $item?->remarks) }}</textarea>
                @error('remarks')<span class="form-error" id="remarks_error" role="alert">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>
</div>
