<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PosCheckoutRequest extends FormRequest
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
            'sale_date' => ['nullable', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in([Sale::PAYMENT_CASH])],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'amount_tendered' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_tendered.required' => 'Cash received / amount paid is required.',
            'amount_tendered.numeric' => 'Cash received must be a valid number.',
            'amount_tendered.min' => 'Cash received cannot be negative.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);
            if (! is_array($items) || $items === []) {
                return;
            }

            $subtotal = 0.0;
            foreach ($items as $line) {
                $qty = (float) ($line['quantity'] ?? 0);
                $price = (float) ($line['unit_price'] ?? 0);
                $subtotal += $qty * $price;
            }

            $discount = max(0, (float) $this->input('discount', 0));
            $tax = max(0, (float) $this->input('tax', 0));
            $total = round(max(0, $subtotal - $discount + $tax), 2);
            $tendered = round((float) $this->input('amount_tendered', 0), 2);

            if ($tendered < $total) {
                $shortfall = round($total - $tendered, 2);
                $validator->errors()->add(
                    'amount_tendered',
                    sprintf(
                        'Insufficient cash. Cash received (%s) is less than the total (%s). Still need %s.',
                        money($tendered),
                        money($total),
                        money($shortfall),
                    ),
                );
            }
        });
    }
}
