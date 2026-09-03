<?php

namespace App\Models;

use App\Support\PartNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    public const TYPE_CONSUMABLE = 'consumable';

    public const TYPE_ASSET = 'asset';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_code',
        'part_number',
        'qr_code',
        'name',
        'description',
        'inventory_type',
        'category_id',
        'brand',
        'model',
        'serial_number',
        'quantity',
        'unit',
        'unit_cost',
        'selling_price',
        'total_value',
        'supplier_id',
        'location_id',
        'department_id',
        'condition',
        'status',
        'date_acquired',
        'warranty_expiration',
        'assigned_to',
        'minimum_stock',
        'reorder_level',
        'remarks',
        'is_archived',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'total_value' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'date_acquired' => 'date',
            'warranty_expiration' => 'date',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BorrowingRecord::class, 'inventory_item_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(TransferRecord::class, 'inventory_item_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(InventoryHistory::class, 'inventory_item_id');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(QrScanLog::class, 'inventory_item_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'inventory_item_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'inventory_item_id');
    }

    /**
     * Effective POS unit price (falls back to unit cost when unset).
     */
    public function effectiveSellingPrice(): float
    {
        $price = (float) ($this->selling_price ?? 0);

        return $price > 0 ? $price : (float) $this->unit_cost;
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function isConsumable(): bool
    {
        return ($this->inventory_type ?? self::TYPE_CONSUMABLE) === self::TYPE_CONSUMABLE;
    }

    public function isAsset(): bool
    {
        return ($this->inventory_type ?? self::TYPE_CONSUMABLE) === self::TYPE_ASSET;
    }

    public function isLowStock(): bool
    {
        if (! $this->isConsumable()) {
            return false;
        }

        return (float) $this->quantity <= (float) $this->reorder_level
            && (float) $this->reorder_level > 0;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0 || $this->status === 'Out of Stock';
    }

    public function isBorrowed(): bool
    {
        if ($this->status === 'Borrowed') {
            return true;
        }

        return $this->borrowings()
            ->where('status', 'borrowed')
            ->exists();
    }

    /**
     * @return list<string>
     */
    public static function inventoryTypes(): array
    {
        return [self::TYPE_CONSUMABLE, self::TYPE_ASSET];
    }

    public function recalculateTotalValue(): void
    {
        $this->total_value = round((float) $this->quantity * (float) $this->unit_cost, 2);
    }

    /**
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('item_code', 'like', $like)
                ->orWhere('qr_code', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('model', 'like', $like);
        });
    }

    /**
     * @param  Builder<InventoryItem>  $query
     * @return Builder<InventoryItem>
     */
    public function scopePartNumber(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        return $query->where('part_number', 'like', $like);
    }

    public function brandModelLabel(): string
    {
        return trim(implode(' ', array_filter([(string) $this->brand, (string) $this->model], fn ($value) => $value !== '')));
    }

    public function labeledName(): string
    {
        $identifier = $this->part_number ?: $this->item_code;

        return trim($identifier.' — '.$this->name);
    }

    public function setPartNumberAttribute(?string $value): void
    {
        $normalized = PartNumber::normalize($value);

        $this->attributes['part_number'] = $normalized === '' ? null : $normalized;
    }
}
