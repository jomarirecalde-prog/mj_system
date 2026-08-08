<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_id',
        'inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:2',
            'quantity_received' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function recalculateTotalCost(): void
    {
        $qty = (float) ($this->quantity_received > 0 ? $this->quantity_received : $this->quantity_ordered);
        $this->total_cost = round($qty * (float) $this->unit_cost, 2);
    }
}
