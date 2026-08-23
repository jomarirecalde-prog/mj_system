<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrStationDevice extends Model
{
    protected $fillable = [
        'station_id',
        'device_identifier',
        'device_token_hash',
        'device_name',
        'browser',
        'operating_system',
        'ip_address',
        'status',
        'authorized_at',
        'last_activity_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(QrStation::class, 'station_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(QrStationActivityLog::class, 'device_id');
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'authorized' && $this->revoked_at === null;
    }

    public function displayName(): string
    {
        $parts = array_filter([
            $this->browser,
            $this->operating_system,
        ]);

        return $parts !== [] ? implode(' on ', $parts) : ($this->device_name ?? 'Unknown device');
    }
}
