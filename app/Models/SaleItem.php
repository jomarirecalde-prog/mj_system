<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'line_total',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function recalculateLineTotal(): void
    {
        $this->line_total = round((float) $this->quantity * (float) $this->unit_price, 2);
    }
}
