<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceShift extends Model
{
    protected $fillable = [
        'name',
        'code',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'grace_period_minutes',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class, 'shift_id');
    }
}
