<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QrStation extends Model
{
    protected $fillable = [
        'station_name',
        'station_code',
        'password',
        'location',
        'description',
        'building',
        'department',
        'floor_area',
        'timezone',
        'status',
        'authorized_device_id',
        'authorized_at',
        'last_activity_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizedDevice(): BelongsTo
    {
        return $this->belongsTo(QrStationDevice::class, 'authorized_device_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(QrStationDevice::class, 'station_id');
    }

    public function activeDevice(): HasOne
    {
        return $this->hasOne(QrStationDevice::class, 'station_id')
            ->where('status', 'authorized')
            ->latest('id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(QrStationActivityLog::class, 'station_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasAuthorizedDevice(): bool
    {
        return $this->authorized_device_id !== null;
    }

    public function deviceStatusLabel(): string
    {
        if (! $this->hasAuthorizedDevice()) {
            return 'Unassigned';
        }

        $device = $this->authorizedDevice;

        if ($device === null || $device->status !== 'authorized') {
            return 'Revoked';
        }

        return 'Authorized';
    }

    public function displayLabel(): string
    {
        return $this->station_name.' ('.$this->station_code.')';
    }
}
