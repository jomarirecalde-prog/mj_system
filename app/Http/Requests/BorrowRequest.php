<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BorrowRequest extends FormRequest
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
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_id_number' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'date_borrowed' => ['nullable', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:date_borrowed'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'condition_before' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
