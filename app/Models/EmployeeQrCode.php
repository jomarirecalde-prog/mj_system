<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeQrCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'status',
        'generated_at',
        'generated_by',
        'disabled_at',
        'disabled_by',
        'disable_reason',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
