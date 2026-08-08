<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AttendanceSettingController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function edit(): View
    {
        $this->authorizeRoles(['admin']);

        $settings = [
            'grace_period_minutes' => AttendanceSetting::get('grace_period_minutes', '15'),
            'default_time_in' => substr((string) AttendanceSetting::get('default_time_in', '08:00:00'), 0, 5),
            'default_time_out' => substr((string) AttendanceSetting::get('default_time_out', '17:00:00'), 0, 5),
            'default_break_start' => substr((string) AttendanceSetting::get('default_break_start', '12:00:00'), 0, 5),
            'default_break_end' => substr((string) AttendanceSetting::get('default_break_end', '13:00:00'), 0, 5),
            'scan_cooldown_seconds' => AttendanceSetting::get('scan_cooldown_seconds', '30'),
            'treat_holiday_as_rest' => AttendanceSetting::get('treat_holiday_as_rest', '1'),
            'location_capture' => AttendanceSetting::get('location_capture', '0'),
            'require_reason_on_correction' => AttendanceSetting::get('require_reason_on_correction', '1'),
        ];

        $holidays = Holiday::query()->orderByDesc('holiday_date')->limit(50)->get();

        return view('attendance.settings', compact('settings', 'holidays'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'default_time_in' => ['required', 'date_format:H:i'],
            'default_time_out' => ['required', 'date_format:H:i'],
            'default_break_start' => ['nullable', 'date_format:H:i'],
            'default_break_end' => ['nullable', 'date_format:H:i'],
            'scan_cooldown_seconds' => ['required', 'integer', 'min:0', 'max:600'],
            'treat_holiday_as_rest' => ['nullable', 'boolean'],
            'location_capture' => ['nullable', 'boolean'],
            'require_reason_on_correction' => ['nullable', 'boolean'],
        ]);

        AttendanceSetting::set('grace_period_minutes', $data['grace_period_minutes'], 'rules', 'Grace Period (minutes)');
        AttendanceSetting::set('default_time_in', $data['default_time_in'].':00', 'schedule', 'Default Time In');
        AttendanceSetting::set('default_time_out', $data['default_time_out'].':00', 'schedule', 'Default Time Out');
        AttendanceSetting::set('default_break_start', ($data['default_break_start'] ?? '12:00').':00', 'schedule', 'Default Break Start');
        AttendanceSetting::set('default_break_end', ($data['default_break_end'] ?? '13:00').':00', 'schedule', 'Default Break End');
        AttendanceSetting::set('scan_cooldown_seconds', $data['scan_cooldown_seconds'], 'scanner', 'Scan Cooldown (seconds)');
        AttendanceSetting::set('treat_holiday_as_rest', $request->boolean('treat_holiday_as_rest') ? '1' : '0', 'rules', 'Treat Holiday as Rest Day');
        AttendanceSetting::set('location_capture', $request->boolean('location_capture') ? '1' : '0', 'scanner', 'Capture Location');
        AttendanceSetting::set('require_reason_on_correction', $request->boolean('require_reason_on_correction') ? '1' : '0', 'rules', 'Require Reason on Correction');

        Cache::flush();

        $this->audit->log('update_attendance_settings', 'attendance', null, null, null, $data);

        return redirect()->route('attendance.settings.edit')->with('success', 'Attendance settings saved.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'type' => ['required', 'in:regular,special'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Holiday::query()->create($data + ['is_active' => true]);

        return redirect()->route('attendance.settings.edit')->with('success', 'Holiday added.');
    }
}
