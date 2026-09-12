<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\EmployeeQrCode;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
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

        $this->seedHolidays();

        $shiftPool = AttendanceShift::query()->where('is_active', true)->orderBy('code')->get();
        $shiftB = $shiftPool->firstWhere('code', 'SHIFT-B') ?? $shiftPool->first();
        $service = app(AttendanceService::class);
        $adminUser = User::query()->where('role', 'admin')->first();

        User::query()
            ->whereNotNull('employee_id')
            ->orderBy('id')
            ->each(function (User $user) use ($shiftPool, $shiftB, $service, $adminUser) {
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

                $assignedShift = $this->isSampleEmployee($user)
                    ? $shiftPool[$user->id % max(1, $shiftPool->count())]
                    : $shiftB;

                EmployeeSchedule::query()->updateOrCreate(
                    ['user_id' => $user->id, 'is_active' => true],
                    [
                        'shift_id' => $assignedShift?->id,
                        'schedule_type' => 'shift',
                        'time_in' => $assignedShift?->time_in ?? '08:00:00',
                        'time_out' => $assignedShift?->time_out ?? '17:00:00',
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

        $this->seedLeaveRecords($adminUser);
        $this->seedDailyTimeRecords($service);
    }

    protected function seedHolidays(): void
    {
        $holidays = [
            ['name' => "New Year's Day", 'holiday_date' => '2026-01-01', 'type' => 'regular'],
            ['name' => 'Araw ng Kagitingan', 'holiday_date' => '2026-04-09', 'type' => 'regular'],
            ['name' => 'Maundy Thursday', 'holiday_date' => '2026-04-02', 'type' => 'regular'],
            ['name' => 'Good Friday', 'holiday_date' => '2026-04-03', 'type' => 'regular'],
            ['name' => 'Labor Day', 'holiday_date' => '2026-05-01', 'type' => 'regular'],
            ['name' => 'Independence Day', 'holiday_date' => '2026-06-12', 'type' => 'regular'],
            ['name' => 'Ninoy Aquino Day', 'holiday_date' => '2026-08-21', 'type' => 'regular'],
            ['name' => 'National Heroes Day', 'holiday_date' => '2026-08-31', 'type' => 'regular'],
            ['name' => 'All Saints Day', 'holiday_date' => '2026-11-01', 'type' => 'special'],
            ['name' => 'Bonifacio Day', 'holiday_date' => '2026-11-30', 'type' => 'regular'],
            ['name' => 'Christmas Day', 'holiday_date' => '2026-12-25', 'type' => 'regular'],
            ['name' => 'Rizal Day', 'holiday_date' => '2026-12-30', 'type' => 'regular'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::query()->updateOrCreate(
                ['holiday_date' => $holiday['holiday_date']],
                $holiday + ['is_active' => true]
            );
        }
    }

    protected function seedLeaveRecords(?User $approver): void
    {
        $employees = $this->sampleEmployeesQuery()->orderBy('id')->get();
        if ($employees->isEmpty()) {
            return;
        }

        $leaves = [
            ['index' => 4, 'start' => '2026-08-10', 'end' => '2026-08-11', 'type' => 'leave', 'reason' => 'Personal leave'],
            ['index' => 11, 'start' => '2026-08-24', 'end' => '2026-08-24', 'type' => 'official_business', 'reason' => 'Client site visit'],
            ['index' => 18, 'start' => '2026-09-03', 'end' => '2026-09-04', 'type' => 'leave', 'reason' => 'Sick leave'],
            ['index' => 27, 'start' => '2026-08-14', 'end' => '2026-08-14', 'type' => 'half_day', 'reason' => 'Medical appointment'],
            ['index' => 36, 'start' => '2026-09-07', 'end' => '2026-09-08', 'type' => 'leave', 'reason' => 'Family emergency'],
        ];

        foreach ($leaves as $leave) {
            $employee = $employees->get($leave['index']);
            if ($employee === null) {
                continue;
            }

            LeaveRecord::query()->updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'start_date' => $leave['start'],
                    'end_date' => $leave['end'],
                ],
                [
                    'leave_type' => $leave['type'],
                    'status' => 'approved',
                    'reason' => $leave['reason'],
                    'approved_by' => $approver?->id,
                ]
            );
        }
    }

    protected function seedDailyTimeRecords(AttendanceService $service): void
    {
        $tz = 'Asia/Manila';
        $start = Carbon::parse('2026-08-01', $tz)->startOfDay();
        $end = now($tz)->subDay()->startOfDay();

        if ($end->lt($start)) {
            $end = $start->copy()->addDays(20);
        }

        $employees = $this->sampleEmployeesQuery()->orderBy('id')->get();
        $staffScanner = User::query()->where('email', 'staff@inventory.local')->first();

        foreach ($employees as $employee) {
            $schedule = $service->resolveSchedule($employee, $start->toDateString());
            $cursor = $start->copy();

            while ($cursor->lte($end)) {
                $date = $cursor->toDateString();
                $exists = AttendanceRecord::query()
                    ->where('user_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists();

                if (! $exists) {
                    $this->createDayRecord($employee, $date, $schedule, $service, $staffScanner);
                }

                $cursor->addDay();
            }
        }
    }

    /**
     * @param  array{time_in: string, time_out: string, break_start: ?string, break_end: ?string, shift_name: ?string, rest_days: array<int, int>, work_days: array<int, int>, grace_minutes: int}  $schedule
     */
    protected function createDayRecord(
        User $employee,
        string $date,
        array $schedule,
        AttendanceService $service,
        ?User $scanner
    ): void {
        $preStatus = $service->precomputeDayStatus($employee, $date, $schedule);

        $base = [
            'user_id' => $employee->id,
            'attendance_date' => $date,
            'schedule_time_in' => $schedule['time_in'],
            'schedule_time_out' => $schedule['time_out'],
            'shift_name' => $schedule['shift_name'],
            'source' => 'qr',
            'time_in_by' => $scanner?->id,
            'time_out_by' => $scanner?->id,
            'time_in_device' => 'Seeded QR Station',
            'time_out_device' => 'Seeded QR Station',
        ];

        if (in_array($preStatus, ['rest_day', 'on_leave', 'official_business', 'half_day'], true)) {
            AttendanceRecord::query()->create($base + [
                'status' => $preStatus,
                'remarks' => match ($preStatus) {
                    'rest_day' => 'Scheduled rest day / holiday',
                    'on_leave' => 'Approved leave',
                    'official_business' => 'Official business',
                    'half_day' => 'Approved half day',
                    default => null,
                },
            ]);

            return;
        }

        $dayOfYear = Carbon::parse($date, 'Asia/Manila')->dayOfYear;
        $bucket = ($employee->id * 17 + $dayOfYear) % 12;

        if ($bucket === 0) {
            AttendanceRecord::query()->create($base + [
                'status' => 'absent',
                'source' => 'system',
                'time_in_by' => null,
                'time_out_by' => null,
                'time_in_device' => null,
                'time_out_device' => null,
                'remarks' => 'No time-in recorded',
            ]);

            return;
        }

        $scheduledIn = Carbon::parse($date.' '.$schedule['time_in'], 'Asia/Manila');
        $scheduledOut = Carbon::parse($date.' '.$schedule['time_out'], 'Asia/Manila');

        $in = $scheduledIn->copy()->subMinutes(($employee->id + $dayOfYear) % 8);
        $out = $scheduledOut->copy()->addMinutes(($employee->id + $dayOfYear) % 12);

        if ($bucket === 1 || $bucket === 4) {
            $in = $scheduledIn->copy()->addMinutes(20 + ($employee->id % 25));
            $out = $scheduledOut->copy();
        } elseif ($bucket === 2) {
            $out = $scheduledOut->copy()->subMinutes(20 + ($employee->id % 25));
        } elseif ($bucket === 3) {
            $out = $scheduledOut->copy()->addMinutes(45 + ($employee->id % 40));
        }

        $grace = $schedule['grace_minutes'];
        $late = 0;
        if ($in->gt($scheduledIn->copy()->addMinutes($grace))) {
            $late = $scheduledIn->diffInMinutes($in);
        }

        $undertime = $out->lt($scheduledOut) ? $out->diffInMinutes($scheduledOut) : 0;
        $overtime = $out->gt($scheduledOut) ? $scheduledOut->diffInMinutes($out) : 0;

        $status = 'present';
        if ($late > 0) {
            $status = 'late';
        } elseif ($undertime > 0) {
            $status = 'undertime';
        }

        AttendanceRecord::query()->create($base + [
            'time_in' => $in,
            'time_out' => $out,
            'total_minutes' => max(0, $in->diffInMinutes($out)),
            'late_minutes' => $late,
            'undertime_minutes' => $undertime,
            'overtime_minutes' => $overtime,
            'status' => $status,
        ]);
    }

    protected function isSampleEmployee(User $user): bool
    {
        $email = (string) $user->email;
        $employeeId = (string) $user->employee_id;

        return $email === 'employee@inventory.local'
            || str_ends_with($email, '@employees.local')
            || str_starts_with($employeeId, 'EMP-01');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    protected function sampleEmployeesQuery()
    {
        return User::query()
            ->where('status', 'active')
            ->whereNotNull('employee_id')
            ->where(function ($query) {
                $query->where('email', 'employee@inventory.local')
                    ->orWhere('email', 'like', '%@employees.local')
                    ->orWhere('employee_id', 'like', 'EMP-01%');
            });
    }
}
