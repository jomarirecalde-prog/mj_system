<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'active', 'role:admin']);
    }

    public function edit(): View
    {
        $settings = [
            'organization_name' => setting('organization_name', 'QR Inventory System'),
            'code_prefix' => setting('code_prefix', 'INV'),
            'qr_format' => setting('qr_format', 'svg'),
            'default_min_stock' => setting('default_min_stock', '0'),
            'currency' => setting('currency', 'PHP'),
            'date_format' => setting('date_format', 'M d, Y'),
            'timezone' => setting('timezone', 'Asia/Manila'),
            'notifications_enabled' => setting('notifications_enabled', '1'),
            'session_timeout' => setting('session_timeout', '120'),
        ];

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'code_prefix' => ['required', 'string', 'max:20'],
            'qr_format' => ['required', 'in:svg,png'],
            'default_min_stock' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:100'],
            'notifications_enabled' => ['nullable', 'boolean'],
            'session_timeout' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        SystemSetting::set('organization_name', $data['organization_name'], 'general');
        SystemSetting::set('code_prefix', $data['code_prefix'], 'inventory');
        SystemSetting::set('qr_format', $data['qr_format'], 'inventory');
        SystemSetting::set('default_min_stock', $data['default_min_stock'], 'inventory');
        SystemSetting::set('currency', $data['currency'], 'general');
        SystemSetting::set('date_format', $data['date_format'], 'general');
        SystemSetting::set('timezone', $data['timezone'], 'general');
        SystemSetting::set('notifications_enabled', $request->boolean('notifications_enabled') ? '1' : '0', 'notifications');
        SystemSetting::set('session_timeout', (string) $data['session_timeout'], 'security');

        return back()->with('success', 'Settings updated successfully.');
    }
}
