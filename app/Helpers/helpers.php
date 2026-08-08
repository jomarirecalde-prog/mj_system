<?php

use App\Models\SystemSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return SystemSetting::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);

        return '₱'.number_format($value, 2, '.', ',');
    }
}

if (! function_exists('ph_datetime')) {
    function ph_datetime(CarbonInterface|string|null $dt, string $format = 'M d, Y h:i A'): ?string
    {
        if ($dt === null || $dt === '') {
            return null;
        }

        if (! $dt instanceof CarbonInterface) {
            $dt = Carbon::parse($dt);
        }

        return $dt->timezone('Asia/Manila')->format($format);
    }
}
