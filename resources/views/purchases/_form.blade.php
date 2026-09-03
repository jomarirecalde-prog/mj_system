@php
    $purchase = $purchase ?? null;
    $isEdit = $purchase !== null;
    $oldItems = old('items', $purchase?->items?->map(fn ($line) => [
        'inventory_item_id' => $line->inventory_item_id,
        'quantity_ordered' => $line->quantity_ordered,
        'unit_cost' => $line->unit_cost,
        'remarks' => $line->remarks,
    ])->values()->all() ?? [['inventory_item_id' => '', 'quantity_ordered' => '', 'unit_cost' => '', 'remarks' => '']]);
@endphp

<div class="inv-form">
    {{-- 1. Purchase Information --}}
    <section class="inv-form-section" aria-labelledby="pur-section-info">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="pur-section-info">Purchase Information</h2>
                <p class="inv-form-section__desc">Core purchase order details and status.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="purchase_number">Purchase Number</label>
                    <input type="text" name="purchase_number" id="purchase_number"
                           class="form-control @error('purchase_number') is-invalid @enderror"
                           value="{{ old('purchase_number', $purchase?->purchase_number ?? $nextNumber) }}"
                           {{ $isEdit ? 'readonly aria-readonly="true"' : '' }}
                           @error('purchase_number') aria-invalid="true" aria-describedby="purchase_number_error" @enderror>
                    @if(!$isEdit)
                        <span class="form-hint">Auto-generated purchase number.</span>
                    @endif
                    @error('purchase_number')
                        <span class="form-error" id="purchase_number_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="purchase_date">Purchase Date <span class="req" aria-hidden="true">*</span></label>
                    <input type="date" name="purchase_date" id="purchase_date"
                           class="form-control @error('purchase_date') is-invalid @enderror"
                           value="{{ old('purchase_date', $purchase?->purchase_date?->format('Y-m-d') ?? now('Asia/Manila')->format('Y-m-d')) }}"
                           required
                           @error('purchase_date') aria-invalid="true" aria-describedby="purchase_date_error" @enderror>
                    @error('purchase_date')
                        <span class="form-error" id="purchase_date_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror"
                            @error('supplier_id') aria-invalid="true" aria-describedby="supplier_id_error" @enderror>
                        <option value="">— Optional —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" @selected(old('supplier_id', $purchase?->supplier_id) == $sup->id)>{{ $sup->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <span class="form-error" id="supplier_id_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status <span class="req" aria-hidden="true">*</span></label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required
                            aria-describedby="pur-status-hint status_error"
                            @error('status') aria-invalid="true" @enderror>
                        <option value="pending" @selected(old('status', $purchase?->status ?? 'pending') === 'pending')>Pending</option>
                        <option value="ordered" @selected(old('status', $purchase?->status) === 'ordered')>Ordered</option>
                    </select>
                    <p class="pur-status-hint" id="pur-status-hint"></p>
                    <span class="form-hint">Do not mark Received here — use the Receive action so stock updates correctly.</span>
                    @error('status')
                        <span class="form-error" id="status_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Document Information --}}
    <section class="inv-form-section" aria-labelledby="pur-section-docs">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="pur-section-docs">Document Information</h2>
                <p class="inv-form-section__desc">Purchase order and invoice reference numbers.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="purchase_order_number">Purchase Order Number</label>
                    <input type="text" name="purchase_order_number" id="purchase_order_number"
                           class="form-control @error('purchase_order_number') is-invalid @enderror"
                           value="{{ old('purchase_order_number', $purchase?->purchase_order_number) }}"
                           placeholder="e.g. PO-2026-0045"
                           @error('purchase_order_number') aria-invalid="true" aria-describedby="purchase_order_number_error" @enderror>
                    @error('purchase_order_number')
                        <span class="form-error" id="purchase_order_number_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="invoice_number">Invoice Number</label>
                    <input type="text" name="invoice_number" id="invoice_number"
                           class="form-control @error('invoice_number') is-invalid @enderror"
                           value="{{ old('invoice_number', $purchase?->invoice_number) }}"
                           @error('invoice_number') aria-invalid="true" aria-describedby="invoice_number_error" @enderror>
                    @error('invoice_number')
                        <span class="form-error" id="invoice_number_error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Notes --}}
    <section class="inv-form-section" aria-labelledby="pur-section-notes">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="pur-section-notes">Notes</h2>
                <p class="inv-form-section__desc">Optional remarks about this purchase.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="form-group">
                <label class="form-label" for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-textarea @error('remarks') is-invalid @enderror" rows="3"
                          @error('remarks') aria-invalid="true" aria-describedby="remarks_error" @enderror>{{ old('remarks', $purchase?->remarks) }}</textarea>
                @error('remarks')
                    <span class="form-error" id="remarks_error" role="alert">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </section>

    {{-- 4. Line Items --}}
    <section class="inv-form-section" aria-labelledby="pur-section-lines">
        <header class="inv-form-section__header">
            <span class="inv-form-section__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </span>
            <div>
                <h2 class="inv-form-section__title" id="pur-section-lines">Line Items</h2>
                <p class="inv-form-section__desc">Items to order. Quantities are added to inventory only after receiving.</p>
            </div>
        </header>
        <div class="inv-form-section__body">
            <div class="pur-lines" id="pur-lines">
                @foreach($oldItems as $i => $line)
                    <div class="pur-line">
                        <div class="pur-line__head">
                            <span class="pur-line__num">Item {{ $i + 1 }}</span>
                            <button type="button" class="pur-remove-line remove-line" aria-label="Remove item" title="Remove item"
                                    {{ $i === 0 ? 'disabled' : '' }}>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <div class="pur-line__grid">
                            <div class="form-group">
                                <label class="form-label">Item <span class="req" aria-hidden="true">*</span></label>
                                <select name="items[{{ $i }}][inventory_item_id]" class="form-select pur-item-select @error('items.'.$i.'.inventory_item_id') is-invalid @enderror" required aria-label="Select inventory item">
                                    <option value="">— Select item —</option>
                                    @foreach($inventoryItems as $inv)
                                        <option value="{{ $inv->id }}"
                                            data-cost="{{ $inv->unit_cost }}"
                                            @selected(($line['inventory_item_id'] ?? '') == $inv->id)>
                                            {{ $inv->part_number }} — {{ $inv->name }} (stock: {{ $inv->quantity }} {{ $inv->unit }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('items.'.$i.'.inventory_item_id')
                                    <span class="form-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Qty Ordered <span class="req" aria-hidden="true">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="items[{{ $i }}][quantity_ordered]"
                                       class="form-control @error('items.'.$i.'.quantity_ordered') is-invalid @enderror"
                                       value="{{ $line['quantity_ordered'] ?? '' }}" required aria-label="Quantity ordered">
                                @error('items.'.$i.'.quantity_ordered')
                                    <span class="form-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit Cost <span class="req" aria-hidden="true">*</span></label>
                                <input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]"
                                       class="form-control line-cost @error('items.'.$i.'.unit_cost') is-invalid @enderror"
                                       value="{{ $line['unit_cost'] ?? '' }}" required aria-label="Unit cost">
                                <span class="pur-cost-hint @if(!empty($line['unit_cost'])) is-visible @endif">Default cost loaded from inventory</span>
                                @error('items.'.$i.'.unit_cost')
                                    <span class="form-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="items[{{ $i }}][remarks]" class="form-control"
                                       value="{{ $line['remarks'] ?? '' }}" aria-label="Line remarks">
                            </div>
                        </div>
                        <div class="pur-line__total">
                            @php
                                $qty = (float) ($line['quantity_ordered'] ?? 0);
                                $cost = (float) ($line['unit_cost'] ?? 0);
                            @endphp
                            <div class="pur-line__total-formula">{{ $qty ?: 0 }} × {{ money($cost) }}</div>
                            <div class="pur-line__total-amount">= {{ money($qty * $cost) }}</div>
                            <div class="pur-line__total-preview">Line total preview</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn--secondary pur-add-line" id="add-line">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Item
            </button>

            {{-- Purchase summary --}}
            <div class="pur-form-summary" aria-label="Purchase total preview">
                <div>
                    <div class="pur-form-summary__label">Subtotal</div>
                    <div class="pur-form-summary__value" id="pur-summary-subtotal">{{ money(0) }}</div>
                    <div class="pur-form-summary__preview">UI preview only</div>
                </div>
                <div>
                    <div class="pur-form-summary__label">Total Items</div>
                    <div class="pur-form-summary__value" id="pur-summary-items">0</div>
                </div>
                <div>
                    <div class="pur-form-summary__label">Grand Total</div>
                    <div class="pur-form-summary__value pur-form-summary__value--grand" id="pur-summary-grand">{{ money(0) }}</div>
                    <div class="pur-form-summary__preview">Backend calculates final total</div>
                </div>
            </div>
        </div>
    </section>
</div>
