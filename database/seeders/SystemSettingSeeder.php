<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'organization_name', 'value' => 'QR Inventory System', 'group' => 'general'],
            ['key' => 'code_prefix', 'value' => 'INV', 'group' => 'inventory'],
            ['key' => 'qr_format', 'value' => 'svg', 'group' => 'inventory'],
            ['key' => 'default_min_stock', 'value' => '5', 'group' => 'inventory'],
            ['key' => 'currency', 'value' => 'PHP', 'group' => 'general'],
            ['key' => 'date_format', 'value' => 'M d, Y', 'group' => 'general'],
            ['key' => 'timezone', 'value' => 'Asia/Manila', 'group' => 'general'],
            ['key' => 'notifications_enabled', 'value' => '1', 'group' => 'notifications'],
            ['key' => 'session_timeout', 'value' => '120', 'group' => 'security'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']],
            );
        }
    }
}
