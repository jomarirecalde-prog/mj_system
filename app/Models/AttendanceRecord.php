<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'schedule_time_in',
        'schedule_time_out',
        'shift_name',
        'time_in',
        'time_out',
        'total_minutes',
        'late_minutes',
        'undertime_minutes',
        'overtime_minutes',
        'status',
        'source',
        'time_in_by',
        'time_out_by',
        'time_in_device',
        'time_out_device',
        'time_in_location',
        'time_out_location',
        'remarks',
        'is_corrected',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'is_corrected' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'time_in_by');
    }

    public function timeOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'time_out_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(AttendanceAdjustment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function totalHoursLabel(): string
    {
        if ($this->total_minutes === null) {
            return '—';
        }

        $h = intdiv((int) $this->total_minutes, 60);
        $m = (int) $this->total_minutes % 60;

        return sprintf('%dh %02dm', $h, $m);
    }

    public function minutesLabel(?int $minutes): string
    {
        if ($minutes === null || $minutes === 0) {
            return '0';
        }

        if ($minutes < 60) {
            return $minutes.'m';
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'on_leave' => 'On Leave',
            'official_business' => 'Official Business',
            'half_day' => 'Half Day',
            'undertime' => 'Undertime',
            'incomplete' => 'Incomplete',
            'rest_day' => 'Rest Day',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function isCurrentlyIn(): bool
    {
        return $this->time_in !== null && $this->time_out === null;
    }

    public function scheduleLabel(): string
    {
        if (! $this->schedule_time_in || ! $this->schedule_time_out) {
            return '—';
        }

        $label = substr((string) $this->schedule_time_in, 0, 5).' – '.substr((string) $this->schedule_time_out, 0, 5);

        return $this->shift_name ? $this->shift_name.' ('.$label.')' : $label;
    }
}
