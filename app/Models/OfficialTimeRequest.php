<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialTimeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_type',
        'effective_from',
        'effective_to',
        'current_time_in',
        'current_time_out',
        'current_break_start',
        'current_break_end',
        'current_schedule_type',
        'requested_time_in',
        'requested_time_out',
        'requested_break_start',
        'requested_break_end',
        'reason',
        'notes',
        'status',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
        'employee_schedule_id',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function employeeSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function requestTypeLabel(): string
    {
        return match ($this->request_type) {
            'permanent' => 'Permanent Official Time',
            'temporary' => 'Temporary Official Time',
            default => ucfirst(str_replace('_', ' ', (string) $this->request_type)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function effectivePeriodLabel(): string
    {
        $from = $this->effective_from?->format('M j, Y') ?? '—';
        if ($this->effective_to === null) {
            return $from.' – No end date';
        }

        return $from.' – '.$this->effective_to->format('M j, Y');
    }

    public function timeRangeLabel(string $prefix = 'requested'): string
    {
        $in = self::formatTimeField($this->{$prefix.'_time_in'});
        $out = self::formatTimeField($this->{$prefix.'_time_out'});

        return $in.' – '.$out;
    }

    public function breakRangeLabel(string $prefix = 'requested'): string
    {
        $start = self::formatTimeField($this->{$prefix.'_break_start'});
        $end = self::formatTimeField($this->{$prefix.'_break_end'});

        if ($start === '—' || $end === '—') {
            return '—';
        }

        return $start.' – '.$end;
    }

    public static function formatTimeField(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $time = substr((string) $value, 0, 5);

        try {
            return Carbon::createFromFormat('H:i', $time, 'Asia/Manila')->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    /**
     * @return list<string>
     */
    public function detectConflicts(): array
    {
        return app(\App\Services\OfficialTimeRequestService::class)
            ->detectConflicts(
                $this->user,
                $this->effective_from->toDateString(),
                $this->effective_to?->toDateString(),
                $this->id
            );
    }
}
