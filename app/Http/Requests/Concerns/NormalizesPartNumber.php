<?php

namespace App\Http\Requests\Concerns;

use App\Support\PartNumber;

trait NormalizesPartNumber
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('part_number')) {
            return;
        }

        $normalized = PartNumber::normalize($this->input('part_number'));

        $this->merge([
            'part_number' => $normalized === '' ? null : $normalized,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'part_number.required' => 'A Part Number is required.',
            'part_number.unique' => PartNumber::DUPLICATE_MESSAGE,
            'part_number.regex' => 'Part Number may only contain letters, numbers, and hyphens.',
            'part_number.max' => 'Part Number may not be greater than 100 characters.',
        ];
    }
}
