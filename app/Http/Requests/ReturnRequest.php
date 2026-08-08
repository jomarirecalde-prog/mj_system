<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnRequest extends FormRequest
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
            'date_returned' => ['nullable', 'date'],
            'condition_after' => ['nullable', 'string', Rule::in(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'])],
            'return_remarks' => ['nullable', 'string'],
        ];
    }
}
