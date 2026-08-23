<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrStationActivityLog extends Model
{
    protected $fillable = [
        'station_id',
        'device_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'performed_by',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(QrStation::class, 'station_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(QrStationDevice::class, 'device_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
