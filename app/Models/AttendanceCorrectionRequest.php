<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'attendance_date',
        'issue_type',
        'requested_time_in',
        'requested_time_out',
        'reason',
        'status',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'requested_time_in' => 'datetime',
            'requested_time_out' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function issueTypeLabel(): string
    {
        return match ($this->issue_type) {
            'missing_time_in' => 'Missing Time In',
            'missing_time_out' => 'Missing Time Out',
            'incorrect_time_in' => 'Incorrect Time In',
            'incorrect_time_out' => 'Incorrect Time Out',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', (string) $this->issue_type)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst((string) $this->status),
        };
    }

    public function requestedTimeLabel(): string
    {
        $parts = [];
        if ($this->requested_time_in) {
            $parts[] = 'In '.$this->requested_time_in->timezone('Asia/Manila')->format('h:i A');
        }
        if ($this->requested_time_out) {
            $parts[] = 'Out '.$this->requested_time_out->timezone('Asia/Manila')->format('h:i A');
        }

        return $parts !== [] ? implode(' / ', $parts) : '—';
    }
}
