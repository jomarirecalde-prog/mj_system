<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'system_setting.'.$key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting !== null ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value),
                'group' => $group,
            ]
        );

        Cache::forget('system_setting.'.$key);

        return $setting;
    }
}
