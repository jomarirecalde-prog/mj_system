<?php

namespace App\Http\Requests;

use App\Support\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockOutRequest extends FormRequest
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
            'quantity' => ['required', 'numeric', 'gt:0'],
            'transaction_type' => ['nullable', Rule::in([
                InventoryTransactionType::ISSUE,
                InventoryTransactionType::CONSUMPTION,
            ])],
            'transaction_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'recipient' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient.required' => 'Recipient is required for issue/consume transactions.',
            'department_id.required' => 'Department is required for issue/consume transactions.',
            'purpose.required' => 'Purpose is required for issue/consume transactions.',
        ];
    }
}
