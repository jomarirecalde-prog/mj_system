<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPartNumber;
use App\Support\PartNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStoreRequest extends FormRequest
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
        return [
            'item_code' => ['required', 'string', 'max:255', 'unique:inventory_items,item_code'],
            'part_number' => ['nullable', 'string', 'max:'.PartNumber::MAX_LENGTH, 'regex:'.PartNumber::PATTERN, 'unique:inventory_items,part_number'],
            'qr_code' => ['nullable', 'string', 'max:255', 'unique:inventory_items,qr_code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'inventory_type' => ['required', 'string', Rule::in(['consumable', 'asset'])],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', 'unique:inventory_items,serial_number'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
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
