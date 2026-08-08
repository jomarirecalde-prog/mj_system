<?php

namespace Database\Seeders;

use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\EmployeeQrCode;
use App\Models\EmployeeSchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'grace_period_minutes', 'value' => '15', 'group' => 'rules', 'label' => 'Grace Period (minutes)'],
            ['key' => 'default_time_in', 'value' => '08:00:00', 'group' => 'schedule', 'label' => 'Default Time In'],
            ['key' => 'default_time_out', 'value' => '17:00:00', 'group' => 'schedule', 'label' => 'Default Time Out'],
            ['key' => 'default_break_start', 'value' => '12:00:00', 'group' => 'schedule', 'label' => 'Default Break Start'],
            ['key' => 'default_break_end', 'value' => '13:00:00', 'group' => 'schedule', 'label' => 'Default Break End'],
            ['key' => 'scan_cooldown_seconds', 'value' => '30', 'group' => 'scanner', 'label' => 'Scan Cooldown (seconds)'],
            ['key' => 'treat_holiday_as_rest', 'value' => '1', 'group' => 'rules', 'label' => 'Treat Holiday as Rest Day'],
            ['key' => 'location_capture', 'value' => '0', 'group' => 'scanner', 'label' => 'Capture Location'],
            ['key' => 'require_reason_on_correction', 'value' => '1', 'group' => 'rules', 'label' => 'Require Reason on Correction'],
        ];

        foreach ($settings as $setting) {
            AttendanceSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        $shifts = [
            ['name' => 'Shift A', 'code' => 'SHIFT-A', 'time_in' => '07:00:00', 'time_out' => '16:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00'],
            ['name' => 'Shift B', 'code' => 'SHIFT-B', 'time_in' => '08:00:00', 'time_out' => '17:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00'],
            ['name' => 'Shift C', 'code' => 'SHIFT-C', 'time_in' => '09:00:00', 'time_out' => '18:00:00', 'break_start' => '12:00:00', 'break_end' => '13:00:00'],
        ];

        foreach ($shifts as $shift) {
            AttendanceShift::query()->updateOrCreate(['code' => $shift['code']], $shift + ['is_active' => true]);
        }

        $permissions = [
            ['name' => 'View Attendance', 'slug' => 'attendance.view', 'module' => 'attendance'],
            ['name' => 'Scan Attendance QR', 'slug' => 'attendance.scan', 'module' => 'attendance'],
            ['name' => 'Manage Attendance', 'slug' => 'attendance.manage', 'module' => 'attendance'],
            ['name' => 'Correct DTR', 'slug' => 'attendance.correct', 'module' => 'attendance'],
            ['name' => 'Attendance Settings', 'slug' => 'attendance.settings', 'module' => 'attendance'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $admin = Role::query()->where('slug', 'admin')->first();
        $staff = Role::query()->where('slug', 'staff')->first();

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(
                Permission::query()->where('module', 'attendance')->pluck('id')
            );
        }

        if ($staff) {
            $staff->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('slug', [
                    'attendance.view',
                    'attendance.scan',
                ])->pluck('id')
            );
        }

        $shiftB = AttendanceShift::query()->where('code', 'SHIFT-B')->first();
        $service = app(AttendanceService::class);
        $adminUser = User::query()->where('role', 'admin')->first();

        User::query()
            ->whereNotNull('employee_id')
            ->each(function (User $user) use ($shiftB, $service, $adminUser) {
                if (! $user->position) {
                    $user->forceFill([
                        'position' => match ($user->role) {
                            'admin' => 'System Administrator',
                            'staff' => 'Inventory Staff',
                            'employee' => 'Office Staff',
                            default => 'Staff',
                        },
                    ])->save();
                }

                EmployeeSchedule::query()->updateOrCreate(
                    ['user_id' => $user->id, 'is_active' => true],
                    [
                        'shift_id' => $shiftB?->id,
                        'schedule_type' => 'shift',
                        'time_in' => $shiftB?->time_in ?? '08:00:00',
                        'time_out' => $shiftB?->time_out ?? '17:00:00',
                        'break_start' => '12:00:00',
                        'break_end' => '13:00:00',
                        'work_days' => [1, 2, 3, 4, 5],
                        'rest_days' => [0, 6],
                        'is_active' => true,
                    ]
                );

                if (! EmployeeQrCode::query()->where('user_id', $user->id)->where('status', 'active')->exists()) {
                    $service->generateQrCode($user, $adminUser ?? $user);
                }
            });
    }
}
