<?php

namespace App\Services;

use App\Models\BorrowingRecord;
use App\Models\InventoryHistory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\TransferRecord;
use App\Models\User;
use App\Support\InventoryTransactionType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected AuditService $auditService,
        protected NotificationService $notificationService,
        protected QrCodeService $qrCodeService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data, User $user): InventoryItem
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueIdentifiers($data);

            if (empty($data['qr_code'])) {
                $data['qr_code'] = $this->qrCodeService->generateIdentifier();
            } else {
                $data['qr_code'] = $this->qrCodeService->ensureUnique($data['qr_code']);
            }

            $initialQuantity = max(0, (float) ($data['quantity'] ?? 0));
            $data['quantity'] = 0;
            $data['inventory_type'] = $data['inventory_type'] ?? InventoryItem::TYPE_CONSUMABLE;
            if (! isset($data['selling_price']) || (float) $data['selling_price'] <= 0) {
                $data['selling_price'] = (float) ($data['unit_cost'] ?? 0);
            }
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;

            if ($initialQuantity <= 0 && ($data['status'] ?? null) === 'Out of Stock') {
                // keep status
            } elseif ($initialQuantity <= 0) {
                $data['status'] = $data['status'] ?? 'Available';
            }

            $item = new InventoryItem(Arr::only($data, (new InventoryItem)->getFillable()));
            $item->recalculateTotalValue();
            $item->save();

            $this->recordHistory(
                $item,
                'created',
                $user,
                null,
                $item->item_code,
                0,
                'Inventory item created',
                $item->id,
                InventoryItem::class,
            );

            $this->auditService->log('create', 'inventory', InventoryItem::class, $item->id, null, $item->toArray());

            $this->notificationService->notifyAdmins(
                'inventory.created',
                'New inventory item',
                sprintf('Item %s (%s) was created.', $item->name, $item->item_code),
            );

            if ($initialQuantity > 0) {
                $this->applyQuantityChange(
                    $item,
                    InventoryTransactionType::INITIAL_STOCK,
                    $initialQuantity,
                    $user,
                    [
                        'transaction_date' => $data['date_acquired'] ?? now('Asia/Manila')->toDateString(),
                        'remarks' => 'Initial stock',
                        'reference_number' => $item->item_code,
                    ],
                );
            }

            $item = $item->fresh();

            if ($item->isLowStock()) {
                $this->notifyLowStock($item);
            }

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(InventoryItem $item, array $data, User $user): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $user) {
            if ($item->isArchived()) {
                throw ValidationException::withMessages([
                    'item' => ['Archived items cannot be modified.'],
                ]);
            }

            $this->assertUniqueIdentifiers($data, $item->id);

            // Quantity must only change through inventory transactions.
            unset($data['quantity']);

            $previous = $item->getOriginal();
            $fillable = array_values(array_diff($item->getFillable(), ['quantity']));

            $item->fill(Arr::only($data, $fillable));
            $item->updated_by = $user->id;
            $item->recalculateTotalValue();
            $item->save();

            $this->recordHistory(
                $item,
                'updated',
                $user,
                (string) ($previous['name'] ?? ''),
                $item->name,
                (float) $item->quantity,
                'Inventory item updated',
                $item->id,
                InventoryItem::class,
            );

            $this->auditService->log('update', 'inventory', InventoryItem::class, $item->id, $previous, $item->toArray());

            if ($item->isLowStock()) {
                $this->notifyLowStock($item);
            }

            return $item->fresh();
        });
    }

    public function archiveItem(InventoryItem $item, User $user, ?string $remarks = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $user, $remarks) {
            if ($item->isArchived()) {
                return $item;
            }

            if ($item->isBorrowed()) {
                throw ValidationException::withMessages([
                    'item' => ['Cannot archive an item that is currently borrowed.'],
                ]);
            }

            $previousStatus = $item->status;

            $item->forceFill([
                'is_archived' => true,
                'archived_at' => now('Asia/Manila'),
                'status' => 'Archived',
                'updated_by' => $user->id,
            ])->save();

            $this->recordHistory(
                $item,
                'archived',
                $user,
                $previousStatus,
                'Archived',
                (float) $item->quantity,
                $remarks ?? 'Item archived',
                $item->id,
                InventoryItem::class,
            );

            $this->auditService->log('archive', 'inventory', InventoryItem::class, $item->id, ['status' => $previousStatus], $item->toArray());

            return $item->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function stockIn(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $user, $context) {
            $this->applyQuantityChange($item, InventoryTransactionType::STOCK_IN, $quantity, $user, $context);

            return $item->fresh();
        });
    }

    /**
     * Issue items to a recipient (permanent deduction for consumables).
     *
     * @param  array<string, mixed>  $context
     */
    public function issue(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $user, $context) {
            $this->assertConsumableQuantityAction($item, 'issue');
            $this->applyQuantityChange($item, InventoryTransactionType::ISSUE, -$quantity, $user, $context);

            return $item->fresh();
        });
    }

    /**
     * Consume items (permanent deduction).
     *
     * @param  array<string, mixed>  $context
     */
    public function consume(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $user, $context) {
            $this->assertConsumableQuantityAction($item, 'consume');
            $this->applyQuantityChange($item, InventoryTransactionType::CONSUMPTION, -$quantity, $user, $context);

            return $item->fresh();
        });
    }

    /**
     * Legacy stock-out alias — maps to issue.
     *
     * @param  array<string, mixed>  $context
     */
    public function stockOut(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        $type = $context['type'] ?? InventoryTransactionType::ISSUE;

        if ($type === InventoryTransactionType::CONSUMPTION) {
            return $this->consume($item, $quantity, $user, $context);
        }

        return $this->issue($item, $quantity, $user, $context);
    }

    /**
     * Return unused stock (increase quantity).
     *
     * @param  array<string, mixed>  $context
     */
    public function returnStock(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $user, $context) {
            $this->applyQuantityChange($item, InventoryTransactionType::RETURN, $quantity, $user, $context);

            return $item->fresh();
        });
    }

    /**
     * Set absolute stock count via adjustment transaction.
     *
     * @param  array<string, mixed>  $context
     */
    public function adjust(InventoryItem $item, float $newQuantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $newQuantity, $user, $context) {
            $this->assertCanTransact($item);

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Adjusted quantity cannot be negative.'],
                ]);
            }

            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = (float) $locked->quantity;
            $delta = $newQuantity - $before;

            $context['quantity_override'] = abs($delta);
            $context['remarks'] = $context['remarks'] ?? sprintf('Stock adjusted from %s to %s', $before, $newQuantity);

            $this->applyQuantityChange(
                $locked,
                InventoryTransactionType::ADJUSTMENT,
                $delta,
                $user,
                $context,
                $newQuantity,
            );

            return $locked->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function recordLoss(InventoryItem $item, float $quantity, User $user, array $context = []): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity, $user, $context) {
            $type = $context['type'] ?? InventoryTransactionType::LOST;
            if (! in_array($type, [
                InventoryTransactionType::DAMAGED,
                InventoryTransactionType::LOST,
                InventoryTransactionType::DISPOSAL,
            ], true)) {
                $type = InventoryTransactionType::LOST;
            }

            $this->applyQuantityChange($item, $type, -$quantity, $user, $context);

            return $item->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function borrow(InventoryItem $item, array $data, User $user): BorrowingRecord
    {
        return DB::transaction(function () use ($item, $data, $user) {
            $item = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $this->assertCanTransact($item);

            if (! $item->isAsset()) {
                throw ValidationException::withMessages([
                    'item' => ['Only non-consumable / asset items can be borrowed. Use Issue or Consume for consumables.'],
                ]);
            }

            if ($item->isBorrowed()) {
                throw ValidationException::withMessages([
                    'item' => ['This item is already borrowed and cannot be borrowed again.'],
                ]);
            }

            if ((float) $item->quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Cannot borrow an item with zero quantity.'],
                ]);
            }

            $statusBefore = $item->status;
            $conditionBefore = $item->condition;
            $borrowerName = $data['borrower_name'];

            $item->forceFill([
                'status' => 'Borrowed',
                'updated_by' => $user->id,
            ])->save();

            $record = BorrowingRecord::query()->create([
                'inventory_item_id' => $item->id,
                'borrower_name' => $borrowerName,
                'borrower_id_number' => $data['borrower_id_number'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'date_borrowed' => $data['date_borrowed'] ?? now('Asia/Manila')->toDateString(),
                'expected_return_date' => $data['expected_return_date'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'condition_before' => $data['condition_before'] ?? $conditionBefore,
                'approved_by' => $data['approved_by'] ?? $user->id,
                'processed_by' => $user->id,
                'status' => 'borrowed',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->createTransaction($item, [
                'type' => InventoryTransactionType::BORROW,
                'quantity' => 0,
                'quantity_before' => (float) $item->quantity,
                'quantity_after' => (float) $item->quantity,
                'transaction_date' => $record->date_borrowed,
                'department_id' => $record->department_id,
                'to_person' => $borrowerName,
                'purpose' => $record->purpose,
                'status_before' => $statusBefore,
                'status_after' => 'Borrowed',
                'condition_before' => $conditionBefore,
                'condition_after' => $conditionBefore,
                'performed_by' => $user->id,
                'approved_by' => $record->approved_by,
                'remarks' => $record->remarks,
                'meta' => [
                    'borrowing_record_id' => $record->id,
                    'asset_status' => 'Borrowed',
                    'borrower' => $borrowerName,
                ],
            ]);

            $this->recordHistory(
                $item,
                'borrow',
                $user,
                $statusBefore,
                'Borrowed',
                0,
                $record->remarks,
                $record->id,
                BorrowingRecord::class,
            );

            $this->auditService->log('borrow', 'inventory', InventoryItem::class, $item->id, ['status' => $statusBefore], ['status' => 'Borrowed']);

            $this->notificationService->notifyAdmins(
                'inventory.borrowed',
                'Item borrowed',
                sprintf('%s was borrowed by %s.', $item->name, $record->borrower_name),
            );

            return $record->fresh(['item', 'department']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function returnItem(BorrowingRecord $record, array $data, User $user): BorrowingRecord
    {
        return DB::transaction(function () use ($record, $data, $user) {
            $item = $record->item()->lockForUpdate()->firstOrFail();

            if ($record->status === 'returned') {
                throw ValidationException::withMessages([
                    'record' => ['This borrowing record has already been returned.'],
                ]);
            }

            if ($item->isArchived()) {
                throw ValidationException::withMessages([
                    'item' => ['Archived items cannot be returned through inventory transactions.'],
                ]);
            }

            $statusBefore = $item->status;
            $conditionAfter = $data['condition_after'] ?? $item->condition;

            $record->forceFill([
                'date_returned' => $data['date_returned'] ?? now('Asia/Manila')->toDateString(),
                'condition_after' => $conditionAfter,
                'returned_by' => $user->id,
                'processed_by' => $user->id,
                'status' => 'returned',
                'return_remarks' => $data['return_remarks'] ?? null,
            ])->save();

            $item->forceFill([
                'status' => 'Available',
                'condition' => $conditionAfter,
                'updated_by' => $user->id,
            ])->save();

            $this->createTransaction($item, [
                'type' => InventoryTransactionType::RETURN,
                'quantity' => 0,
                'quantity_before' => (float) $item->quantity,
                'quantity_after' => (float) $item->quantity,
                'transaction_date' => $record->date_returned,
                'from_person' => $record->borrower_name,
                'status_before' => $statusBefore,
                'status_after' => 'Available',
                'condition_before' => $record->condition_before,
                'condition_after' => $conditionAfter,
                'performed_by' => $user->id,
                'remarks' => $record->return_remarks,
                'meta' => [
                    'borrowing_record_id' => $record->id,
                    'asset_status' => 'Available',
                    'borrower' => $record->borrower_name,
                ],
            ]);

            $this->recordHistory(
                $item,
                'return',
                $user,
                $statusBefore,
                'Available',
                0,
                $record->return_remarks,
                $record->id,
                BorrowingRecord::class,
            );

            $this->auditService->log('return', 'inventory', InventoryItem::class, $item->id, ['status' => $statusBefore], ['status' => 'Available']);

            return $record->fresh(['item', 'department']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function transfer(InventoryItem $item, array $data, User $user): TransferRecord
    {
        return DB::transaction(function () use ($item, $data, $user) {
            $item = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $this->assertCanTransact($item);

            if ($item->isBorrowed()) {
                throw ValidationException::withMessages([
                    'item' => ['Cannot transfer an item while it is borrowed.'],
                ]);
            }

            $fromLocationId = $item->location_id;
            $fromDepartmentId = $item->department_id;
            $fromCustodianId = $item->assigned_to;
            $beforeQty = (float) $item->quantity;

            $record = TransferRecord::query()->create([
                'inventory_item_id' => $item->id,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $data['to_location_id'] ?? null,
                'from_department_id' => $fromDepartmentId,
                'to_department_id' => $data['to_department_id'] ?? null,
                'from_custodian_id' => $fromCustodianId,
                'to_custodian_id' => $data['to_custodian_id'] ?? null,
                'transfer_date' => $data['transfer_date'] ?? now('Asia/Manila')->toDateString(),
                'reason' => $data['reason'] ?? null,
                'approved_by' => $data['approved_by'] ?? $user->id,
                'processed_by' => $user->id,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $item->forceFill([
                'location_id' => $data['to_location_id'] ?? $item->location_id,
                'department_id' => $data['to_department_id'] ?? $item->department_id,
                'assigned_to' => array_key_exists('to_custodian_id', $data) ? $data['to_custodian_id'] : $item->assigned_to,
                'updated_by' => $user->id,
            ])->save();

            $this->createTransaction($item, [
                'type' => InventoryTransactionType::TRANSFER,
                'quantity' => 0,
                'quantity_before' => $beforeQty,
                'quantity_after' => $beforeQty,
                'transaction_date' => $record->transfer_date,
                'from_location' => $data['from_location_label'] ?? null,
                'to_location' => $data['to_location_label'] ?? null,
                'performed_by' => $user->id,
                'approved_by' => $record->approved_by,
                'remarks' => $record->remarks,
                'meta' => [
                    'transfer_record_id' => $record->id,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => $item->location_id,
                ],
            ]);

            $this->recordHistory(
                $item,
                'transfer',
                $user,
                (string) $fromLocationId,
                (string) ($item->location_id ?? ''),
                $beforeQty,
                $record->remarks ?? 'Item transferred',
                $record->id,
                TransferRecord::class,
            );

            $this->auditService->log('transfer', 'inventory', InventoryItem::class, $item->id, [
                'location_id' => $fromLocationId,
                'department_id' => $fromDepartmentId,
            ], [
                'location_id' => $item->location_id,
                'department_id' => $item->department_id,
            ]);

            return $record->fresh(['item', 'fromLocation', 'toLocation']);
        });
    }

    public function updateCondition(InventoryItem $item, string $newCondition, User $user, ?string $remarks = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $newCondition, $user, $remarks) {
            $item = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $this->assertCanTransact($item);

            $before = $item->condition;

            $item->forceFill([
                'condition' => $newCondition,
                'updated_by' => $user->id,
            ])->save();

            $this->createTransaction($item, [
                'type' => InventoryTransactionType::CONDITION_CHANGE,
                'quantity' => 0,
                'quantity_before' => (float) $item->quantity,
                'quantity_after' => (float) $item->quantity,
                'transaction_date' => now('Asia/Manila')->toDateString(),
                'condition_before' => $before,
                'condition_after' => $newCondition,
                'performed_by' => $user->id,
                'remarks' => $remarks,
            ]);

            $this->recordHistory(
                $item,
                'condition_change',
                $user,
                $before,
                $newCondition,
                (float) $item->quantity,
                $remarks,
                $item->id,
                InventoryItem::class,
            );

            $this->auditService->log('condition_change', 'inventory', InventoryItem::class, $item->id, ['condition' => $before], ['condition' => $newCondition]);

            return $item->fresh();
        });
    }

    /**
     * Apply a signed quantity delta and write the ledger row.
     * Positive delta increases stock; negative decreases. Never allows negative stock.
     *
     * @param  array<string, mixed>  $context
     */
    public function applyQuantityChange(
        InventoryItem $item,
        string $type,
        float $signedDelta,
        User $user,
        array $context = [],
        ?float $absoluteNewQuantity = null,
    ): InventoryTransaction {
        $this->assertCanTransact($item);

        $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
        $before = (float) $locked->quantity;

        if ($absoluteNewQuantity !== null) {
            $after = $absoluteNewQuantity;
            $signedDelta = $after - $before;
        } else {
            $after = $before + $signedDelta;
        }

        $movementQty = (float) ($context['quantity_override'] ?? abs($signedDelta));

        if ($movementQty <= 0 && $type !== InventoryTransactionType::ADJUSTMENT) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be greater than zero.'],
            ]);
        }

        if ($after < 0) {
            $action = match ($type) {
                InventoryTransactionType::ISSUE => 'issue',
                InventoryTransactionType::CONSUMPTION => 'consume',
                InventoryTransactionType::SALE => 'sell',
                InventoryTransactionType::DAMAGED => 'mark as damaged',
                InventoryTransactionType::LOST => 'mark as lost',
                InventoryTransactionType::DISPOSAL => 'dispose',
                InventoryTransactionType::TRANSFER_OUT => 'transfer out',
                default => 'remove',
            };

            throw ValidationException::withMessages([
                'quantity' => [sprintf(
                    'Insufficient Inventory. Only %s units are currently available. You cannot %s %s units.',
                    rtrim(rtrim(number_format($before, 2, '.', ''), '0'), '.'),
                    $action,
                    rtrim(rtrim(number_format($movementQty, 2, '.', ''), '0'), '.'),
                )],
            ]);
        }

        $statusBefore = $locked->status;

        $locked->quantity = $after;
        $locked->updated_by = $user->id;

        if ($after <= 0 && $locked->isConsumable()) {
            $locked->status = 'Out of Stock';
        } elseif ($statusBefore === 'Out of Stock' && $after > 0) {
            $locked->status = 'Available';
        }

        if (! empty($context['unit_cost'])) {
            $locked->unit_cost = (float) $context['unit_cost'];
        }

        $locked->recalculateTotalValue();
        $locked->save();

        // Keep caller's model in sync.
        $item->setRawAttributes($locked->getAttributes(), true);

        $transaction = $this->createTransaction($locked, [
            'type' => $type,
            'quantity' => $movementQty,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'transaction_date' => $context['transaction_date'] ?? now('Asia/Manila')->toDateString(),
            'reference_number' => $context['reference_number'] ?? null,
            'from_location' => $context['from_location'] ?? null,
            'to_location' => $context['to_location'] ?? null,
            'from_person' => $context['from_person'] ?? null,
            'to_person' => $context['to_person'] ?? $context['recipient'] ?? null,
            'supplier_id' => $context['supplier_id'] ?? null,
            'department_id' => $context['department_id'] ?? null,
            'purpose' => $context['purpose'] ?? null,
            'status_before' => $statusBefore,
            'status_after' => $locked->status,
            'performed_by' => $user->id,
            'approved_by' => $context['approved_by'] ?? null,
            'remarks' => $context['remarks'] ?? null,
            'meta' => array_filter([
                'signed_delta' => $signedDelta,
                'recipient' => $context['recipient'] ?? null,
                'purchase_id' => $context['purchase_id'] ?? null,
                'purchase_item_id' => $context['purchase_item_id'] ?? null,
                'sale_id' => $context['sale_id'] ?? null,
                'sale_item_id' => $context['sale_item_id'] ?? null,
            ] + (array) ($context['meta'] ?? [])),
        ]);

        $this->recordHistory(
            $locked,
            $type,
            $user,
            (string) $before,
            (string) $after,
            $movementQty,
            $context['remarks'] ?? InventoryTransactionType::label($type),
            $transaction->id,
            InventoryTransaction::class,
        );

        $this->auditService->log($type, 'inventory', InventoryItem::class, $locked->id, [
            'quantity' => $before,
        ], [
            'quantity' => $after,
            'type' => $type,
        ]);

        if ($locked->isLowStock()) {
            $this->notifyLowStock($locked);
        }

        return $transaction;
    }

    public function recordHistory(
        InventoryItem $item,
        string $transactionType,
        User $user,
        ?string $fromValue,
        ?string $toValue,
        ?float $quantity,
        ?string $remarks,
        ?int $referenceId = null,
        ?string $referenceType = null,
    ): InventoryHistory {
        return InventoryHistory::query()->create([
            'inventory_item_id' => $item->id,
            'transaction_type' => $transactionType,
            'user_id' => $user->id,
            'from_value' => $fromValue,
            'to_value' => $toValue,
            'quantity' => $quantity,
            'remarks' => $remarks,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'occurred_at' => now('Asia/Manila'),
        ]);
    }

    protected function notifyLowStock(InventoryItem $item): void
    {
        $this->notificationService->notifyAdmins(
            'inventory.low_stock',
            'Low stock alert',
            sprintf(
                '⚠️ Low Stock: %s has %s %s available (reorder level: %s).',
                $item->name,
                $item->quantity,
                $item->unit,
                $item->reorder_level,
            ),
        );
    }

    protected function assertConsumableQuantityAction(InventoryItem $item, string $action): void
    {
        if ($item->isAsset()) {
            throw ValidationException::withMessages([
                'item' => [sprintf(
                    'Asset items cannot be permanently %sd. Use Borrow / Return to track custody instead.',
                    $action,
                )],
            ]);
        }
    }

    protected function assertCanTransact(InventoryItem $item): void
    {
        if ($item->isArchived()) {
            throw ValidationException::withMessages([
                'item' => ['Archived items cannot be transacted.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertUniqueIdentifiers(array $data, ?int $ignoreItemId = null): void
    {
        foreach (['item_code', 'serial_number', 'qr_code'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                continue;
            }

            $query = InventoryItem::query()->where($field, $data[$field]);

            if ($ignoreItemId !== null) {
                $query->where('id', '!=', $ignoreItemId);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    $field => [sprintf('The %s is already in use.', str_replace('_', ' ', $field))],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createTransaction(InventoryItem $item, array $attributes): InventoryTransaction
    {
        return InventoryTransaction::query()->create(array_merge([
            'inventory_item_id' => $item->id,
        ], $attributes));
    }
}
