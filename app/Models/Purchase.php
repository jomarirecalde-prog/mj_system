<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_number',
        'purchase_date',
        'supplier_id',
        'purchase_order_number',
        'invoice_number',
        'status',
        'total_cost',
        'received_by',
        'received_at',
        'created_by',
        'updated_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'received_at' => 'datetime',
            'total_cost' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ORDERED], true);
    }

    public function canCancel(): bool
    {
        return ! $this->isReceived() && ! $this->isCancelled();
    }

    public function recalculateTotalCost(): void
    {
        $this->total_cost = round((float) $this->items()->sum('total_cost'), 2);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ORDERED,
            self::STATUS_RECEIVED,
            self::STATUS_CANCELLED,
        ];
    }
}
