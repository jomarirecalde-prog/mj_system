@php
    $item = $item ?? null;
    $isEdit = $item !== null;
@endphp

<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="item_code">Item code <span class="req">*</span></label>
        <input type="text" name="item_code" id="item_code" class="form-control" value="{{ old('item_code', $item?->item_code) }}" required {{ $isEdit ? 'readonly' : '' }}>
    </div>
    <div class="form-group">
        <label class="form-label" for="qr_code">QR code</label>
        <input type="text" name="qr_code" id="qr_code" class="form-control" value="{{ old('qr_code', $item?->qr_code) }}" placeholder="Auto-generated if empty">
        <span class="form-hint">Leave blank to auto-generate on save.</span>
    </div>
    <div class="form-group form-group--full">
        <label class="form-label" for="name">Name <span class="req">*</span></label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $item?->name) }}" required>
    </div>
    <div class="form-group form-group--full">
        <label class="form-label" for="description">Description</label>
        <textarea name="description" id="description" class="form-textarea">{{ old('description', $item?->description) }}</textarea>
    </div>
    <div class="form-group">
        <label class="form-label" for="inventory_type">Inventory type <span class="req">*</span></label>
        <select name="inventory_type" id="inventory_type" class="form-select" required>
            <option value="consumable" @selected(old('inventory_type', $item?->inventory_type ?? 'consumable') === 'consumable')>Consumable</option>
            <option value="asset" @selected(old('inventory_type', $item?->inventory_type) === 'asset')>Non-consumable / Asset</option>
        </select>
        <span class="form-hint">Consumables are deducted on issue/consume. Assets use borrow/return without reducing stock.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="category_id">Category</label>
        <select name="category_id" id="category_id" class="form-select">
            <option value="">— None —</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $item?->category_id) == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="location_id">Location</label>
        <select name="location_id" id="location_id" class="form-select">
            <option value="">— None —</option>
            @foreach($locations as $loc)
                <option value="{{ $loc->id }}" @selected(old('location_id', $item?->location_id) == $loc->id)>{{ $loc->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="department_id">Department</label>
        <select name="department_id" id="department_id" class="form-select">
            <option value="">— None —</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" @selected(old('department_id', $item?->department_id) == $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="supplier_id">Supplier</label>
        <select name="supplier_id" id="supplier_id" class="form-select">
            <option value="">— None —</option>
            @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" @selected(old('supplier_id', $item?->supplier_id) == $sup->id)>{{ $sup->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="brand">Brand</label>
        <input type="text" name="brand" id="brand" class="form-control" value="{{ old('brand', $item?->brand) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="model">Model</label>
        <input type="text" name="model" id="model" class="form-control" value="{{ old('model', $item?->model) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="serial_number">Serial number</label>
        <input type="text" name="serial_number" id="serial_number" class="form-control" value="{{ old('serial_number', $item?->serial_number) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="assigned_to">Assigned to</label>
        <select name="assigned_to" id="assigned_to" class="form-select">
            <option value="">— Unassigned —</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(old('assigned_to', $item?->assigned_to) == $u->id)>{{ $u->displayName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="quantity">{{ $isEdit ? 'Current quantity' : 'Initial quantity' }}</label>
        @if($isEdit)
            <input type="text" id="quantity" class="form-control" value="{{ $item->quantity }} {{ $item->unit }}" readonly>
            <span class="form-hint">Quantity cannot be edited directly. Use Stock In, Issue/Consume, Return, Purchase Receiving, or Adjustment.</span>
        @else
            <input type="number" step="0.01" min="0" name="quantity" id="quantity" class="form-control" value="{{ old('quantity', 0) }}">
            <span class="form-hint">Recorded as an Initial Stock transaction on create.</span>
        @endif
    </div>
    <div class="form-group">
        <label class="form-label" for="unit">Unit</label>
        <input type="text" name="unit" id="unit" class="form-control" value="{{ old('unit', $item?->unit ?? 'pcs') }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="unit_cost">Unit cost</label>
        <input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost" class="form-control" value="{{ old('unit_cost', $item?->unit_cost ?? 0) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="selling_price">Selling price</label>
        <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" class="form-control" value="{{ old('selling_price', $item?->selling_price ?? $item?->unit_cost ?? 0) }}">
        <span class="form-hint">Used by POS. Falls back to unit cost when zero.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="total_value_display">Total value</label>
        <input type="text" id="total_value_display" class="form-control" readonly value="{{ money(($item?->unit_cost ?? old('unit_cost', 0)) * ($item?->quantity ?? old('quantity', 0))) }}">
        <span class="form-hint">Calculated from quantity × unit cost</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="minimum_stock">Minimum stock</label>
        <input type="number" step="0.01" min="0" name="minimum_stock" id="minimum_stock" class="form-control" value="{{ old('minimum_stock', $item?->minimum_stock ?? setting('default_min_stock', 0)) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="reorder_level">Reorder level</label>
        <input type="number" step="0.01" min="0" name="reorder_level" id="reorder_level" class="form-control" value="{{ old('reorder_level', $item?->reorder_level ?? 0) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="condition">Condition</label>
        <select name="condition" id="condition" class="form-select">
            @foreach(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'] as $cond)
                <option value="{{ $cond }}" @selected(old('condition', $item?->condition ?? 'New') === $cond)>{{ $cond }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select name="status" id="status" class="form-select">
            @foreach(['Available', 'Borrowed', 'Under Maintenance', 'Archived', 'Out of Stock'] as $st)
                <option value="{{ $st }}" @selected(old('status', $item?->status ?? 'Available') === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="date_acquired">Date acquired</label>
        <input type="date" name="date_acquired" id="date_acquired" class="form-control" value="{{ old('date_acquired', $item?->date_acquired?->format('Y-m-d')) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="warranty_expiration">Warranty expiration</label>
        <input type="date" name="warranty_expiration" id="warranty_expiration" class="form-control" value="{{ old('warranty_expiration', $item?->warranty_expiration?->format('Y-m-d')) }}">
    </div>
    <div class="form-group form-group--full">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks', $item?->remarks) }}</textarea>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const qty = document.getElementById('quantity');
    const cost = document.getElementById('unit_cost');
    const display = document.getElementById('total_value_display');
    const isEdit = {{ $isEdit ? 'true' : 'false' }};
    function recalc() {
        if (isEdit) return;
        const q = parseFloat(qty?.value) || 0;
        const c = parseFloat(cost?.value) || 0;
        if (display && window.App) display.value = App.formatMoney(q * c);
    }
    if (!isEdit) qty?.addEventListener('input', recalc);
    cost?.addEventListener('input', recalc);
})();
</script>
@endpush
