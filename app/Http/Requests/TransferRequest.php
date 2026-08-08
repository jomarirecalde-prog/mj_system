<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
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
            'to_location_id' => ['nullable', 'exists:inventory_locations,id'],
            'to_department_id' => ['nullable', 'exists:departments,id'],
            'to_custodian_id' => ['nullable', 'exists:users,id'],
            'transfer_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
