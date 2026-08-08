<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceQrScanLog extends Model
{
    protected $fillable = [
        'user_id',
        'qr_code',
        'action',
        'scan_date',
        'scan_time',
        'scanned_by',
        'device',
        'result',
        'remarks',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'scan_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
