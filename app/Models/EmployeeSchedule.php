<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'schedule_type',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'work_days',
        'rest_days',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'rest_days' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'shift_id');
    }

    public function scheduleLabel(): string
    {
        $in = substr((string) $this->time_in, 0, 5);
        $out = substr((string) $this->time_out, 0, 5);

        if ($this->shift) {
            return $this->shift->name.' ('.$in.' – '.$out.')';
        }

        return $in.' – '.$out;
    }
}
