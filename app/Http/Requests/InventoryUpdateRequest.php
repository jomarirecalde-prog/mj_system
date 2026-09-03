<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPartNumber;
use App\Models\InventoryItem;
use App\Support\PartNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryUpdateRequest extends FormRequest
{
    use NormalizesPartNumber;

    public function authorize(): bool
    {
        return $this->user()?->canModifyInventory() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var InventoryItem $item */
        $item = $this->route('item');

        return [
            'item_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('inventory_items', 'item_code')->ignore($item->id)],
            'part_number' => ['required', 'string', 'max:'.PartNumber::MAX_LENGTH, 'regex:'.PartNumber::PATTERN, Rule::unique('inventory_items', 'part_number')->ignore($item->id)],
            'qr_code' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('inventory_items', 'qr_code')->ignore($item->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_type' => ['sometimes', 'required', 'string', Rule::in(['consumable', 'asset'])],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items', 'serial_number')->ignore($item->id)],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'location_id' => ['nullable', 'exists:inventory_locations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'condition' => ['nullable', 'string', Rule::in(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'])],
            'status' => ['nullable', 'string', Rule::in(['Available', 'Borrowed', 'Under Maintenance', 'Archived', 'Out of Stock'])],
            'date_acquired' => ['nullable', 'date'],
            'warranty_expiration' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
