<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\InventoryTransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AuditService $auditService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Complete a POS sale and permanently deduct consumable stock.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function checkout(array $data, array $lines, User $user): Sale
    {
        return DB::transaction(function () use ($data, $lines, $user) {
            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => ['Add at least one item to the cart.'],
                ]);
            }

            $merged = $this->mergeLines($lines);
            $discount = max(0, (float) ($data['discount'] ?? 0));
            $tax = max(0, (float) ($data['tax'] ?? 0));
            $paymentMethod = $data['payment_method'] ?? Sale::PAYMENT_CASH;

            if ($paymentMethod !== Sale::PAYMENT_CASH) {
                throw ValidationException::withMessages([
                    'payment_method' => ['POS accepts cash payments only.'],
                ]);
            }

            $sale = Sale::query()->create([
                'sale_number' => $data['sale_number'] ?? $this->nextSaleNumber(),
                'sale_date' => $data['sale_date'] ?? now('Asia/Manila')->toDateString(),
                'customer_name' => $data['customer_name'] ?? null,
                'payment_method' => $paymentMethod,
                'status' => Sale::STATUS_COMPLETED,
                'subtotal' => 0,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => 0,
                'amount_tendered' => $data['amount_tendered'] ?? null,
                'change_due' => null,
                'cashier_id' => $user->id,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $subtotal = 0.0;

            foreach ($merged as $index => $line) {
                $itemId = (int) ($line['inventory_item_id'] ?? 0);
                $qty = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) ($line['unit_price'] ?? 0);

                if ($itemId <= 0 || $qty <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => ['Each line needs a valid item and quantity greater than zero.'],
                    ]);
                }

                if ($unitPrice < 0) {
                    throw ValidationException::withMessages([
                        "items.$index.unit_price" => ['Unit price cannot be negative.'],
                    ]);
                }

                $item = InventoryItem::query()->whereKey($itemId)->lockForUpdate()->first();

                if ($item === null || $item->isArchived()) {
                    throw ValidationException::withMessages([
                        "items.$index.inventory_item_id" => ['Selected inventory item is invalid or archived.'],
                    ]);
                }

                if (! $item->isConsumable()) {
                    throw ValidationException::withMessages([
                        "items.$index.inventory_item_id" => [
                            'Asset items cannot be sold through POS. Use Borrow / Return to track custody instead.',
                        ],
                    ]);
                }

                $lineTotal = round($qty * $unitPrice, 2);
                $subtotal += $lineTotal;

                $saleItem = SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => (float) $item->unit_cost,
                    'line_total' => $lineTotal,
                    'remarks' => $line['remarks'] ?? null,
                ]);

                $this->inventoryService->applyQuantityChange(
                    $item,
                    InventoryTransactionType::SALE,
                    -$qty,
                    $user,
                    [
                        'transaction_date' => $sale->sale_date?->toDateString() ?? now('Asia/Manila')->toDateString(),
                        'reference_number' => $sale->sale_number,
                        'to_person' => $sale->customer_name,
                        'purpose' => 'POS sale',
                        'remarks' => $data['remarks'] ?? sprintf('POS sale %s', $sale->sale_number),
                        'meta' => [
                            'sale_id' => $sale->id,
                            'sale_item_id' => $saleItem->id,
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                            'payment_method' => $sale->payment_method,
                        ],
                    ],
                );
            }

            $sale->subtotal = round($subtotal, 2);
            $sale->total_amount = round(max(0, $sale->subtotal - $discount + $tax), 2);

            $tenderedRaw = $data['amount_tendered'] ?? null;
            if ($tenderedRaw === null || $tenderedRaw === '') {
                throw ValidationException::withMessages([
                    'amount_tendered' => ['Cash received / amount paid is required.'],
                ]);
            }

            $tendered = round((float) $tenderedRaw, 2);
            if ($tendered < (float) $sale->total_amount) {
                $shortfall = round((float) $sale->total_amount - $tendered, 2);
                throw ValidationException::withMessages([
                    'amount_tendered' => [sprintf(
                        'Insufficient cash. Cash received (%s) is less than the total (%s). Still need %s.',
                        money($tendered),
                        money($sale->total_amount),
                        money($shortfall),
                    )],
                ]);
            }

            $sale->amount_tendered = $tendered;
            $sale->change_due = round($tendered - (float) $sale->total_amount, 2);

            $sale->save();

            $this->auditService->log('checkout', 'pos', Sale::class, $sale->id, null, $sale->load('items')->toArray());

            $this->notificationService->notifyAdmins(
                'pos.sale',
                'POS sale completed',
                sprintf('Sale %s totaling %s was completed.', $sale->sale_number, money($sale->total_amount)),
            );

            return $sale->fresh(['items.item', 'cashier']);
        });
    }

    /**
     * Void a completed sale and restore stock.
     */
    public function void(Sale $sale, User $user, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $sale->canVoid()) {
                throw ValidationException::withMessages([
                    'sale' => ['Only completed sales can be voided.'],
                ]);
            }

            foreach ($sale->items as $line) {
                $item = InventoryItem::query()->whereKey($line->inventory_item_id)->lockForUpdate()->first();

                if ($item === null || $item->isArchived()) {
                    throw ValidationException::withMessages([
                        'sale' => [sprintf(
                            'Cannot void sale: item #%s is missing or archived. Adjust stock manually if needed.',
                            $line->inventory_item_id,
                        )],
                    ]);
                }

                $this->inventoryService->applyQuantityChange(
                    $item,
                    InventoryTransactionType::SALE_RETURN,
                    (float) $line->quantity,
                    $user,
                    [
                        'transaction_date' => now('Asia/Manila')->toDateString(),
                        'reference_number' => $sale->sale_number,
                        'from_person' => $sale->customer_name,
                        'purpose' => 'POS void',
                        'remarks' => $reason
                            ? sprintf('Voided sale %s: %s', $sale->sale_number, $reason)
                            : sprintf('Voided sale %s', $sale->sale_number),
                        'meta' => [
                            'sale_id' => $sale->id,
                            'sale_item_id' => $line->id,
                            'void' => true,
                        ],
                    ],
                );
            }

            $sale->forceFill([
                'status' => Sale::STATUS_VOIDED,
                'voided_by' => $user->id,
                'voided_at' => now('Asia/Manila'),
                'void_reason' => $reason,
            ])->save();

            $this->auditService->log('void', 'pos', Sale::class, $sale->id, ['status' => Sale::STATUS_COMPLETED], $sale->fresh('items')->toArray());

            $this->notificationService->notifyAdmins(
                'pos.void',
                'POS sale voided',
                sprintf('Sale %s was voided and stock was restored.', $sale->sale_number),
            );

            return $sale->fresh(['items.item', 'cashier', 'voider']);
        });
    }

    public function nextSaleNumber(): string
    {
        $year = now('Asia/Manila')->format('Y');
        $prefix = 'POS-'.$year.'-';

        $latest = Sale::query()
            ->where('sale_number', 'like', $prefix.'%')
            ->orderByDesc('sale_number')
            ->value('sale_number');

        $seq = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Combine duplicate cart lines for the same item.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    protected function mergeLines(array $lines): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $itemId = (int) ($line['inventory_item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $key = (string) $itemId.'|'.(string) ($line['unit_price'] ?? '');

            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'inventory_item_id' => $itemId,
                    'quantity' => 0.0,
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'remarks' => $line['remarks'] ?? null,
                ];
            }

            $merged[$key]['quantity'] += (float) ($line['quantity'] ?? 0);
        }

        return array_values($merged);
    }
}
