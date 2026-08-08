<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingRecord extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'inventory_item_id',
        'borrower_name',
        'borrower_id_number',
        'department_id',
        'date_borrowed',
        'expected_return_date',
        'date_returned',
        'purpose',
        'condition_before',
        'condition_after',
        'approved_by',
        'returned_by',
        'processed_by',
        'status',
        'remarks',
        'return_remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_borrowed' => 'date',
            'expected_return_date' => 'date',
            'date_returned' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
