<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Support\InventoryTransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AuditService $auditService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(array $data, array $lines, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $lines, $user) {
            if ($lines === []) {
                throw ValidationException::withMessages([
                    'items' => ['Add at least one purchase item.'],
                ]);
            }

            $status = $data['status'] ?? Purchase::STATUS_PENDING;
            if ($status === Purchase::STATUS_RECEIVED) {
                throw ValidationException::withMessages([
                    'status' => ['Create the purchase as Pending or Ordered first, then mark it Received to add stock.'],
                ]);
            }

            $purchase = Purchase::query()->create([
                'purchase_number' => $data['purchase_number'] ?? $this->nextPurchaseNumber(),
                'purchase_date' => $data['purchase_date'] ?? now('Asia/Manila')->toDateString(),
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_order_number' => $data['purchase_order_number'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'status' => $status,
                'total_cost' => 0,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->syncLines($purchase, $lines);
            $purchase->recalculateTotalCost();
            $purchase->save();

            $this->auditService->log('create', 'purchases', Purchase::class, $purchase->id, null, $purchase->load('items')->toArray());

            return $purchase->fresh(['items.item', 'supplier']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>|null  $lines
     */
    public function update(Purchase $purchase, array $data, ?array $lines, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $lines, $user) {
            if ($purchase->isReceived()) {
                throw ValidationException::withMessages([
                    'purchase' => ['Received purchases cannot be edited. Create an adjustment transaction if stock must change.'],
                ]);
            }

            if ($purchase->isCancelled()) {
                throw ValidationException::withMessages([
                    'purchase' => ['Cancelled purchases cannot be edited.'],
                ]);
            }

            $previous = $purchase->toArray();

            $purchase->fill([
                'purchase_date' => $data['purchase_date'] ?? $purchase->purchase_date,
                'supplier_id' => array_key_exists('supplier_id', $data) ? $data['supplier_id'] : $purchase->supplier_id,
                'purchase_order_number' => array_key_exists('purchase_order_number', $data) ? $data['purchase_order_number'] : $purchase->purchase_order_number,
                'invoice_number' => array_key_exists('invoice_number', $data) ? $data['invoice_number'] : $purchase->invoice_number,
                'status' => $data['status'] ?? $purchase->status,
                'remarks' => array_key_exists('remarks', $data) ? $data['remarks'] : $purchase->remarks,
                'updated_by' => $user->id,
            ]);

            if ($purchase->status === Purchase::STATUS_RECEIVED) {
                throw ValidationException::withMessages([
                    'status' => ['Use the Receive action to mark a purchase as received and update inventory.'],
                ]);
            }

            $purchase->save();

            if ($lines !== null) {
                $purchase->items()->delete();
                $this->syncLines($purchase, $lines);
            }

            $purchase->recalculateTotalCost();
            $purchase->save();

            $this->auditService->log('update', 'purchases', Purchase::class, $purchase->id, $previous, $purchase->fresh('items')->toArray());

            return $purchase->fresh(['items.item', 'supplier']);
        });
    }

    /**
     * Mark purchase as Received and add quantities to inventory.
     *
     * @param  array<string, mixed>  $data
     */
    public function receive(Purchase $purchase, array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $data, $user) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->with('items')->firstOrFail();

            if (! $purchase->canReceive()) {
                throw ValidationException::withMessages([
                    'purchase' => ['Only Pending or Ordered purchases can be received.'],
                ]);
            }

            if ($purchase->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['This purchase has no line items to receive.'],
                ]);
            }

            $receivedQtys = $data['quantities'] ?? [];

            foreach ($purchase->items as $line) {
                $receivedQty = array_key_exists($line->id, $receivedQtys)
                    ? (float) $receivedQtys[$line->id]
                    : (float) $line->quantity_ordered;

                if ($receivedQty <= 0) {
                    throw ValidationException::withMessages([
                        'quantities.'.$line->id => ['Received quantity must be greater than zero.'],
                    ]);
                }

                $line->quantity_received = $receivedQty;
                $line->total_cost = round($receivedQty * (float) $line->unit_cost, 2);
                $line->save();

                $item = InventoryItem::query()->whereKey($line->inventory_item_id)->firstOrFail();

                $this->inventoryService->applyQuantityChange(
                    $item,
                    InventoryTransactionType::PURCHASE,
                    $receivedQty,
                    $user,
                    [
                        'transaction_date' => $data['received_date'] ?? now('Asia/Manila')->toDateString(),
                        'reference_number' => $purchase->purchase_order_number ?: $purchase->purchase_number,
                        'supplier_id' => $purchase->supplier_id,
                        'unit_cost' => (float) $line->unit_cost,
                        'remarks' => $data['remarks'] ?? sprintf('Purchase received (%s)', $purchase->purchase_number),
                        'purchase_id' => $purchase->id,
                        'purchase_item_id' => $line->id,
                        'meta' => [
                            'invoice_number' => $purchase->invoice_number,
                            'purchase_order_number' => $purchase->purchase_order_number,
                        ],
                    ],
                );
            }

            $purchase->status = Purchase::STATUS_RECEIVED;
            $purchase->received_by = $user->id;
            $purchase->received_at = now('Asia/Manila');
            $purchase->updated_by = $user->id;
            if (! empty($data['invoice_number'])) {
                $purchase->invoice_number = $data['invoice_number'];
            }
            if (! empty($data['remarks'])) {
                $purchase->remarks = trim(($purchase->remarks ? $purchase->remarks."\n" : '').$data['remarks']);
            }
            $purchase->recalculateTotalCost();
            $purchase->save();

            $this->auditService->log('receive', 'purchases', Purchase::class, $purchase->id, ['status' => 'ordered'], $purchase->fresh('items')->toArray());

            $this->notificationService->notifyAdmins(
                'purchase.received',
                'Purchase received',
                sprintf('Purchase %s was received and inventory quantities were updated.', $purchase->purchase_number),
            );

            return $purchase->fresh(['items.item', 'supplier', 'receiver']);
        });
    }

    public function markOrdered(Purchase $purchase, User $user): Purchase
    {
        if ($purchase->status !== Purchase::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending purchases can be marked as ordered.'],
            ]);
        }

        $purchase->forceFill([
            'status' => Purchase::STATUS_ORDERED,
            'updated_by' => $user->id,
        ])->save();

        return $purchase->fresh();
    }

    public function cancel(Purchase $purchase, User $user, ?string $remarks = null): Purchase
    {
        if (! $purchase->canCancel()) {
            throw ValidationException::withMessages([
                'purchase' => ['This purchase cannot be cancelled.'],
            ]);
        }

        $purchase->forceFill([
            'status' => Purchase::STATUS_CANCELLED,
            'updated_by' => $user->id,
            'remarks' => $remarks ? trim(($purchase->remarks ? $purchase->remarks."\n" : '').'Cancelled: '.$remarks) : $purchase->remarks,
        ])->save();

        $this->auditService->log('cancel', 'purchases', Purchase::class, $purchase->id, null, $purchase->toArray());

        return $purchase->fresh();
    }

    public function nextPurchaseNumber(): string
    {
        $year = now('Asia/Manila')->format('Y');
        $prefix = 'PO-'.$year.'-';

        $latest = Purchase::query()
            ->where('purchase_number', 'like', $prefix.'%')
            ->orderByDesc('purchase_number')
            ->value('purchase_number');

        $seq = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(Purchase $purchase, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $itemId = (int) ($line['inventory_item_id'] ?? 0);
            $qtyOrdered = (float) ($line['quantity_ordered'] ?? 0);
            $unitCost = (float) ($line['unit_cost'] ?? 0);

            if ($itemId <= 0 || $qtyOrdered <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity_ordered" => ['Each line needs a valid item and ordered quantity greater than zero.'],
                ]);
            }

            if (! InventoryItem::query()->whereKey($itemId)->where('is_archived', false)->exists()) {
                throw ValidationException::withMessages([
                    "items.$index.inventory_item_id" => ['Selected inventory item is invalid or archived.'],
                ]);
            }

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'inventory_item_id' => $itemId,
                'quantity_ordered' => $qtyOrdered,
                'quantity_received' => 0,
                'unit_cost' => $unitCost,
                'total_cost' => round($qtyOrdered * $unitCost, 2),
                'remarks' => $line['remarks'] ?? null,
            ]);
        }
    }
}
