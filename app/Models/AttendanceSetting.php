<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'attendance_setting.'.$key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting !== null ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general', ?string $label = null): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value),
                'group' => $group,
                'label' => $label,
            ]
        );

        Cache::forget('attendance_setting.'.$key);

        return $setting;
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
