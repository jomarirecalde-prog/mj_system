@php
    $purchase = $purchase ?? null;
    $oldItems = old('items', $purchase?->items?->map(fn ($line) => [
        'inventory_item_id' => $line->inventory_item_id,
        'quantity_ordered' => $line->quantity_ordered,
        'unit_cost' => $line->unit_cost,
        'remarks' => $line->remarks,
    ])->values()->all() ?? [['inventory_item_id' => '', 'quantity_ordered' => '', 'unit_cost' => '', 'remarks' => '']]);
@endphp

<div class="form-grid">
    <div class="form-group">
        <label class="form-label" for="purchase_number">Purchase number</label>
        <input type="text" name="purchase_number" id="purchase_number" class="form-control"
               value="{{ old('purchase_number', $purchase?->purchase_number ?? $nextNumber) }}"
               {{ $purchase ? 'readonly' : '' }}>
    </div>
    <div class="form-group">
        <label class="form-label" for="purchase_date">Purchase date <span class="req">*</span></label>
        <input type="date" name="purchase_date" id="purchase_date" class="form-control"
               value="{{ old('purchase_date', $purchase?->purchase_date?->format('Y-m-d') ?? now('Asia/Manila')->format('Y-m-d')) }}" required>
    </div>
    <div class="form-group">
        <label class="form-label" for="supplier_id">Supplier</label>
        <select name="supplier_id" id="supplier_id" class="form-select">
            <option value="">— Optional —</option>
            @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}" @selected(old('supplier_id', $purchase?->supplier_id) == $sup->id)>{{ $sup->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label" for="status">Status <span class="req">*</span></label>
        <select name="status" id="status" class="form-select" required>
            <option value="pending" @selected(old('status', $purchase?->status ?? 'pending') === 'pending')>Pending</option>
            <option value="ordered" @selected(old('status', $purchase?->status) === 'ordered')>Ordered</option>
        </select>
        <span class="form-hint">Do not mark Received here — use the Receive action so stock updates correctly.</span>
    </div>
    <div class="form-group">
        <label class="form-label" for="purchase_order_number">Purchase order number</label>
        <input type="text" name="purchase_order_number" id="purchase_order_number" class="form-control"
               value="{{ old('purchase_order_number', $purchase?->purchase_order_number) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="invoice_number">Invoice number</label>
        <input type="text" name="invoice_number" id="invoice_number" class="form-control"
               value="{{ old('invoice_number', $purchase?->invoice_number) }}">
    </div>
    <div class="form-group form-group--full">
        <label class="form-label" for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-textarea">{{ old('remarks', $purchase?->remarks) }}</textarea>
    </div>
</div>

<div class="mt-2">
    <div class="card__header" style="padding-left:0;"><h2 class="card__title">Line items</h2></div>
    <div class="table-wrap">
        <table class="data-table" id="purchase-lines">
            <thead>
            <tr>
                <th>Item</th>
                <th style="width:140px;">Qty ordered</th>
                <th style="width:140px;">Unit cost</th>
                <th>Remarks</th>
                <th style="width:60px;"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($oldItems as $i => $line)
                <tr class="purchase-line">
                    <td>
                        <select name="items[{{ $i }}][inventory_item_id]" class="form-select" required>
                            <option value="">— Select item —</option>
                            @foreach($inventoryItems as $inv)
                                <option value="{{ $inv->id }}"
                                    data-cost="{{ $inv->unit_cost }}"
                                    @selected(($line['inventory_item_id'] ?? '') == $inv->id)>
                                    {{ $inv->item_code }} — {{ $inv->name }} (stock: {{ $inv->quantity }} {{ $inv->unit }})
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0.01" name="items[{{ $i }}][quantity_ordered]" class="form-control" value="{{ $line['quantity_ordered'] ?? '' }}" required></td>
                    <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" class="form-control line-cost" value="{{ $line['unit_cost'] ?? '' }}" required></td>
                    <td><input type="text" name="items[{{ $i }}][remarks]" class="form-control" value="{{ $line['remarks'] ?? '' }}"></td>
                    <td><button type="button" class="btn btn--ghost btn--sm remove-line" {{ $i === 0 ? 'disabled' : '' }}>×</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn--secondary btn--sm mt-1" id="add-line">Add line</button>
</div>

@push('scripts')
<script>
(function () {
    const tbody = document.querySelector('#purchase-lines tbody');
    const addBtn = document.getElementById('add-line');
    const itemOptions = @json($inventoryItems->map(fn ($i) => [
        'id' => $i->id,
        'label' => $i->item_code.' — '.$i->name.' (stock: '.$i->quantity.' '.$i->unit.')',
        'cost' => (float) $i->unit_cost,
    ]));

    function reindex() {
        [...tbody.querySelectorAll('tr')].forEach((tr, i) => {
            tr.querySelectorAll('select, input').forEach((el) => {
                if (!el.name) return;
                el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
            });
            const remove = tr.querySelector('.remove-line');
            if (remove) remove.disabled = tbody.querySelectorAll('tr').length === 1;
        });
    }

    function bindRow(tr) {
        const select = tr.querySelector('select');
        const cost = tr.querySelector('.line-cost');
        select?.addEventListener('change', () => {
            const opt = itemOptions.find(o => String(o.id) === select.value);
            if (opt && cost && !cost.value) cost.value = opt.cost;
        });
        tr.querySelector('.remove-line')?.addEventListener('click', () => {
            if (tbody.querySelectorAll('tr').length > 1) {
                tr.remove();
                reindex();
            }
        });
    }

    [...tbody.querySelectorAll('tr')].forEach(bindRow);

    addBtn?.addEventListener('click', () => {
        const i = tbody.querySelectorAll('tr').length;
        const options = itemOptions.map(o => `<option value="${o.id}" data-cost="${o.cost}">${o.label}</option>`).join('');
        const tr = document.createElement('tr');
        tr.className = 'purchase-line';
        tr.innerHTML = `
            <td><select name="items[${i}][inventory_item_id]" class="form-select" required><option value="">— Select item —</option>${options}</select></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${i}][quantity_ordered]" class="form-control" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${i}][unit_cost]" class="form-control line-cost" required></td>
            <td><input type="text" name="items[${i}][remarks]" class="form-control"></td>
            <td><button type="button" class="btn btn--ghost btn--sm remove-line">×</button></td>`;
        tbody.appendChild(tr);
        bindRow(tr);
        reindex();
    });
})();
</script>
@endpush
