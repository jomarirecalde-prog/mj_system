<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_GCASH = 'gcash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_BANK = 'bank_transfer';

    public const PAYMENT_OTHER = 'other';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sale_number',
        'sale_date',
        'customer_name',
        'payment_method',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'amount_tendered',
        'change_due',
        'cashier_id',
        'voided_by',
        'voided_at',
        'void_reason',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function canVoid(): bool
    {
        return $this->isCompleted();
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = round((float) $this->items()->sum('line_total'), 2);
        $discount = max(0, (float) $this->discount);
        $tax = max(0, (float) $this->tax);
        $this->total_amount = round(max(0, $this->subtotal - $discount + $tax), 2);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_VOIDED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_CASH,
            self::PAYMENT_GCASH,
            self::PAYMENT_CARD,
            self::PAYMENT_BANK,
            self::PAYMENT_OTHER,
        ];
    }

    public static function paymentLabel(string $method): string
    {
        return match ($method) {
            self::PAYMENT_GCASH => 'GCash',
            self::PAYMENT_BANK => 'Bank transfer',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }
}
