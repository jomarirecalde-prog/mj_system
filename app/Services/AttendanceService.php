<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\AttendanceQrScanLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\EmployeeQrCode;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\LeaveRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        protected NotificationService $notifications,
        protected AuditService $audit,
    ) {}

    /**
     * Process a QR scan punch (Time In / Time Out).
     *
     * @return array{success: bool, code: string, message: string, title: string, action?: string, employee?: array<string, mixed>, record?: array<string, mixed>, time?: string}
     */
    public function processScan(string $qrPayload, User $scanner, Request $request): array
    {
        $now = now('Asia/Manila');
        $payload = trim($qrPayload);
        $device = $this->deviceLabel($request);
        $location = $request->input('location');

        if ($payload === '') {
            $this->logScan(null, $payload, 'rejected', 'invalid', 'Invalid QR payload.', $scanner, $request, $now);

            return $this->scanResponse(false, 'invalid', 'INVALID QR CODE', 'Employee record not found.');
        }

        $qr = EmployeeQrCode::query()
            ->with('user')
            ->where('code', $payload)
            ->first();

        if ($qr === null) {
            $this->logScan(null, $payload, 'rejected', 'invalid', 'Employee record not found.', $scanner, $request, $now);

            return $this->scanResponse(false, 'invalid', 'INVALID QR CODE', 'Employee record not found.');
        }

        $employee = $qr->user;

        if ($employee === null || ! $qr->isActive()) {
            $this->logScan($employee?->id, $payload, 'rejected', 'inactive', 'QR code is disabled.', $scanner, $request, $now);

            return $this->scanResponse(false, 'inactive', 'ACCOUNT INACTIVE', 'This employee QR code is not active.');
        }

        if (! $employee->isActive()) {
            $this->logScan($employee->id, $payload, 'rejected', 'inactive', 'Employee account inactive.', $scanner, $request, $now);

            return $this->scanResponse(false, 'inactive', 'ACCOUNT INACTIVE', 'This employee is not currently active.', $employee);
        }

        $cooldown = AttendanceSetting::int('scan_cooldown_seconds', 30);
        $lastScan = AttendanceQrScanLog::query()
            ->where('user_id', $employee->id)
            ->where('result', 'success')
            ->latest('id')
            ->first();

        if ($lastScan && $cooldown > 0 && $lastScan->created_at && $lastScan->created_at->diffInSeconds($now) < $cooldown) {
            $todayRecord = AttendanceRecord::query()
                ->where('user_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->first();

            if ($todayRecord?->time_in && ! $todayRecord->time_out) {
                $this->logScan($employee->id, $payload, 'time_in', 'already_in', 'Already timed in (cooldown).', $scanner, $request, $now);

                return $this->scanResponse(
                    false,
                    'already_in',
                    'ALREADY TIMED IN',
                    $employee->displayName().' already recorded Time In at '.ph_datetime($todayRecord->time_in, 'h:i:s A').'.',
                    $employee,
                    $todayRecord
                );
            }

            $this->logScan($employee->id, $payload, $lastScan->action, 'cooldown', 'Scan cooldown active.', $scanner, $request, $now);

            return $this->scanResponse(
                false,
                'cooldown',
                'PLEASE WAIT',
                $employee->displayName().' was scanned recently. Please wait '.$cooldown.' seconds.',
                $employee
            );
        }

        return DB::transaction(function () use ($employee, $payload, $scanner, $request, $now, $device, $location) {
            $date = $now->toDateString();
            $record = AttendanceRecord::query()
                ->where('user_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $schedule = $this->resolveSchedule($employee, $date);
                $preStatus = $this->precomputeDayStatus($employee, $date, $schedule);

                $record = AttendanceRecord::query()->create([
                    'user_id' => $employee->id,
                    'attendance_date' => $date,
                    'schedule_time_in' => $schedule['time_in'],
                    'schedule_time_out' => $schedule['time_out'],
                    'shift_name' => $schedule['shift_name'],
                    'status' => $preStatus,
                    'source' => 'qr',
                ]);
            }

            if (in_array($record->status, ['on_leave', 'official_business', 'rest_day'], true) && $record->time_in === null) {
                // Allow punch if they still show up; update status after punch.
            }

            if ($record->time_in === null) {
                $lateMinutes = $this->calculateLateMinutes($now, $record->schedule_time_in);
                $status = $lateMinutes > 0 ? 'late' : 'present';

                $record->fill([
                    'time_in' => $now,
                    'late_minutes' => $lateMinutes,
                    'status' => $status,
                    'source' => 'qr',
                    'time_in_by' => $scanner->id,
                    'time_in_device' => $device,
                    'time_in_location' => $location,
                ])->save();

                $this->writeAttendanceLog($employee->id, $record->id, 'QR Time In', null, [
                    'time_in' => $now->format('Y-m-d H:i:s'),
                    'status' => $status,
                ], $scanner, $request, null);

                $this->logScan($employee->id, $payload, 'time_in', $status === 'late' ? 'late' : 'success', 'Time In recorded.', $scanner, $request, $now);

                $this->notifyPunch($employee, 'time_in', $status, $now);

                $this->audit->log('qr_time_in', 'attendance', AttendanceRecord::class, $record->id, null, $record->toArray());

                return $this->scanResponse(
                    true,
                    $status === 'late' ? 'late' : 'time_in',
                    'TIME IN SUCCESSFUL',
                    $status === 'late'
                        ? 'Time In Successfully Recorded (Late)'
                        : 'Time In Successfully Recorded',
                    $employee,
                    $record,
                    'time_in',
                    $now->format('h:i:s A')
                );
            }

            if ($record->time_out !== null) {
                $this->logScan($employee->id, $payload, 'time_out', 'already_out', 'Time Out already recorded.', $scanner, $request, $now);

                return $this->scanResponse(
                    false,
                    'already_out',
                    'TIME OUT ALREADY RECORDED',
                    $employee->displayName().' already recorded Time Out at '.ph_datetime($record->time_out, 'h:i:s A').'.',
                    $employee,
                    $record
                );
            }

            // Already timed in — this scan is Time Out
            $record->fill([
                'time_out' => $now,
                'time_out_by' => $scanner->id,
                'time_out_device' => $device,
                'time_out_location' => $location,
                'source' => 'qr',
            ])->save();

            $this->recalculateMetrics($record);
            $record->refresh();

            $this->writeAttendanceLog($employee->id, $record->id, 'QR Time Out', null, [
                'time_out' => $now->format('Y-m-d H:i:s'),
                'status' => $record->status,
            ], $scanner, $request, null);

            $this->logScan($employee->id, $payload, 'time_out', 'success', 'Time Out recorded.', $scanner, $request, $now);
            $this->notifyPunch($employee, 'time_out', $record->status, $now);
            $this->audit->log('qr_time_out', 'attendance', AttendanceRecord::class, $record->id, null, $record->toArray());

            return $this->scanResponse(
                true,
                'time_out',
                'TIME OUT SUCCESSFUL',
                'Time Out Successfully Recorded',
                $employee,
                $record,
                'time_out',
                $now->format('h:i:s A')
            );
        });
    }

    /**
     * @return array{time_in: string, time_out: string, break_start: ?string, break_end: ?string, shift_name: ?string, rest_days: array<int, int>, work_days: array<int, int>, grace_minutes: int}
     */
    public function resolveSchedule(User $employee, string $date): array
    {
        $defaultIn = AttendanceSetting::get('default_time_in', '08:00:00');
        $defaultOut = AttendanceSetting::get('default_time_out', '17:00:00');
        $grace = AttendanceSetting::int('grace_period_minutes', 15);
        $defaultRest = [0, 6];
        $defaultWork = [1, 2, 3, 4, 5];

        $schedule = EmployeeSchedule::query()
            ->with('shift')
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('id')
            ->first();

        if ($schedule === null) {
            return [
                'time_in' => $this->normalizeTime($defaultIn),
                'time_out' => $this->normalizeTime($defaultOut),
                'break_start' => $this->normalizeTime(AttendanceSetting::get('default_break_start', '12:00:00')),
                'break_end' => $this->normalizeTime(AttendanceSetting::get('default_break_end', '13:00:00')),
                'shift_name' => null,
                'rest_days' => $defaultRest,
                'work_days' => $defaultWork,
                'grace_minutes' => $grace,
            ];
        }

        $shiftGrace = $schedule->shift?->grace_period_minutes;

        return [
            'time_in' => $this->normalizeTime($schedule->time_in),
            'time_out' => $this->normalizeTime($schedule->time_out),
            'break_start' => $schedule->break_start ? $this->normalizeTime($schedule->break_start) : null,
            'break_end' => $schedule->break_end ? $this->normalizeTime($schedule->break_end) : null,
            'shift_name' => $schedule->shift?->name,
            'rest_days' => $schedule->rest_days ?? $defaultRest,
            'work_days' => $schedule->work_days ?? $defaultWork,
            'grace_minutes' => $shiftGrace !== null ? (int) $shiftGrace : $grace,
        ];
    }

    public function recalculateMetrics(AttendanceRecord $record): void
    {
        if ($record->time_in === null || $record->time_out === null) {
            if ($record->time_in !== null && $record->time_out === null) {
                $late = $this->calculateLateMinutes(
                    Carbon::parse($record->time_in)->timezone('Asia/Manila'),
                    $record->schedule_time_in
                );
                $record->late_minutes = $late;
                $record->status = $late > 0 ? 'late' : 'present';
                $record->total_minutes = null;
                $record->save();
            }

            return;
        }

        $in = Carbon::parse($record->time_in)->timezone('Asia/Manila');
        $out = Carbon::parse($record->time_out)->timezone('Asia/Manila');
        $total = max(0, $in->diffInMinutes($out));

        $schedule = $this->resolveSchedule($record->user, $record->attendance_date->toDateString());
        $expectedIn = Carbon::parse($record->attendance_date->toDateString().' '.$this->normalizeTime($record->schedule_time_in ?: $schedule['time_in']), 'Asia/Manila');
        $expectedOut = Carbon::parse($record->attendance_date->toDateString().' '.$this->normalizeTime($record->schedule_time_out ?: $schedule['time_out']), 'Asia/Manila');

        $expectedMinutes = max(0, $expectedIn->diffInMinutes($expectedOut));
        // Deduct default break if configured
        if (! empty($schedule['break_start']) && ! empty($schedule['break_end'])) {
            $breakStart = Carbon::parse($record->attendance_date->toDateString().' '.$schedule['break_start'], 'Asia/Manila');
            $breakEnd = Carbon::parse($record->attendance_date->toDateString().' '.$schedule['break_end'], 'Asia/Manila');
            $expectedMinutes = max(0, $expectedMinutes - $breakStart->diffInMinutes($breakEnd));
        }

        $late = $this->calculateLateMinutes($in, $record->schedule_time_in ?: $schedule['time_in'], $schedule['grace_minutes']);
        $undertime = 0;
        $overtime = 0;

        if ($out->lt($expectedOut)) {
            $undertime = $out->diffInMinutes($expectedOut);
        } elseif ($out->gt($expectedOut)) {
            $overtime = $expectedOut->diffInMinutes($out);
        }

        $status = 'present';
        if ($late > 0) {
            $status = 'late';
        }
        if ($undertime > 0 && $late === 0) {
            $status = 'undertime';
        }
        if ($late > 0 && $undertime > 0) {
            $status = 'late';
        }

        $record->fill([
            'total_minutes' => $total,
            'late_minutes' => $late,
            'undertime_minutes' => $undertime,
            'overtime_minutes' => $overtime,
            'status' => $status,
        ])->save();
    }

    public function calculateLateMinutes(Carbon $punchTime, ?string $scheduleTimeIn, ?int $graceOverride = null): int
    {
        if (! $scheduleTimeIn) {
            return 0;
        }

        $grace = $graceOverride ?? AttendanceSetting::int('grace_period_minutes', 15);
        $date = $punchTime->toDateString();
        $scheduled = Carbon::parse($date.' '.$this->normalizeTime($scheduleTimeIn), 'Asia/Manila');
        $allowed = $scheduled->copy()->addMinutes($grace);

        if ($punchTime->lte($allowed)) {
            return 0;
        }

        return $scheduled->diffInMinutes($punchTime);
    }

    public function precomputeDayStatus(User $employee, string $date, array $schedule): string
    {
        $dayOfWeek = Carbon::parse($date, 'Asia/Manila')->dayOfWeek; // 0=Sun

        if (in_array($dayOfWeek, $schedule['rest_days'] ?? [], true)) {
            return 'rest_day';
        }

        $holiday = Holiday::query()
            ->whereDate('holiday_date', $date)
            ->where('is_active', true)
            ->exists();

        if ($holiday && AttendanceSetting::bool('treat_holiday_as_rest', true)) {
            return 'rest_day';
        }

        $leave = LeaveRecord::query()
            ->where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($leave) {
            return match ($leave->leave_type) {
                'official_business' => 'official_business',
                'half_day' => 'half_day',
                default => 'on_leave',
            };
        }

        return 'incomplete';
    }

    /**
     * Mark absences for employees without attendance on a work day.
     */
    public function markAbsencesForDate(string $date): int
    {
        $now = Carbon::parse($date.' 23:59:59', 'Asia/Manila');
        if ($now->isFuture()) {
            return 0;
        }

        $count = 0;
        $employees = User::query()->where('status', 'active')->whereNotNull('employee_id')->get();

        foreach ($employees as $employee) {
            $exists = AttendanceRecord::query()
                ->where('user_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            $schedule = $this->resolveSchedule($employee, $date);
            $status = $this->precomputeDayStatus($employee, $date, $schedule);

            if ($status === 'incomplete') {
                $status = 'absent';
            }

            AttendanceRecord::query()->create([
                'user_id' => $employee->id,
                'attendance_date' => $date,
                'schedule_time_in' => $schedule['time_in'],
                'schedule_time_out' => $schedule['time_out'],
                'shift_name' => $schedule['shift_name'],
                'status' => $status,
                'source' => 'system',
            ]);

            if ($status === 'absent') {
                $this->notifications->notifyAdmins(
                    'attendance_absent',
                    'Absent employee',
                    $employee->displayName().' was marked absent on '.$date,
                    route('attendance.today')
                );
            }

            $count++;
        }

        return $count;
    }

    /**
     * @return array{total: int, present: int, late: int, absent: int, on_leave: int, currently_in: int, timed_out: int, incomplete: int}
     */
    public function todaySummary(?string $date = null): array
    {
        $date = $date ?: now('Asia/Manila')->toDateString();

        $totalEmployees = User::query()->where('status', 'active')->whereNotNull('employee_id')->count();

        $records = AttendanceRecord::query()->whereDate('attendance_date', $date)->get();

        return [
            'total' => $totalEmployees,
            'present' => $records->whereIn('status', ['present', 'undertime'])->filter(fn ($r) => $r->time_in !== null)->count()
                + $records->where('status', 'present')->count()
                - $records->where('status', 'present')->count()
                + $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'on_leave' => $records->whereIn('status', ['on_leave', 'official_business'])->count(),
            'currently_in' => $records->filter(fn ($r) => $r->isCurrentlyIn())->count(),
            'timed_out' => $records->filter(fn ($r) => $r->time_in && $r->time_out)->count(),
            'incomplete' => $records->where('status', 'incomplete')->count()
                + $records->filter(fn ($r) => $r->time_in && ! $r->time_out)->count(),
        ];
    }

    /**
     * Cleaner today summary counts.
     *
     * @return array<string, int>
     */
    public function dashboardCounts(?string $date = null): array
    {
        $date = $date ?: now('Asia/Manila')->toDateString();
        $totalEmployees = User::query()->where('status', 'active')->whereNotNull('employee_id')->count();

        $base = AttendanceRecord::query()->whereDate('attendance_date', $date);

        $present = (clone $base)->where('status', 'present')->count();
        $late = (clone $base)->where('status', 'late')->count();
        $absent = (clone $base)->where('status', 'absent')->count();
        $onLeave = (clone $base)->whereIn('status', ['on_leave', 'official_business', 'half_day'])->count();
        $currentlyIn = (clone $base)->whereNotNull('time_in')->whereNull('time_out')->count();
        $timedOut = (clone $base)->whereNotNull('time_in')->whereNotNull('time_out')->count();
        $incomplete = (clone $base)->where(function ($q) {
            $q->where('status', 'incomplete')
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('time_in')->whereNull('time_out');
                });
        })->count();
        $undertime = (clone $base)->where('status', 'undertime')->count();
        $restDay = (clone $base)->where('status', 'rest_day')->count();

        return [
            'total' => $totalEmployees,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'on_leave' => $onLeave,
            'currently_in' => $currentlyIn,
            'timed_out' => $timedOut,
            'incomplete' => $incomplete,
            'undertime' => $undertime,
            'rest_day' => $restDay,
        ];
    }

    public function generateQrCode(User $employee, User $actor): EmployeeQrCode
    {
        EmployeeQrCode::query()
            ->where('user_id', $employee->id)
            ->where('status', 'active')
            ->update([
                'status' => 'disabled',
                'disabled_at' => now('Asia/Manila'),
                'disabled_by' => $actor->id,
                'disable_reason' => 'Replaced by new QR code',
            ]);

        $code = $this->nextEmployeeQrIdentifier();

        $qr = EmployeeQrCode::query()->create([
            'user_id' => $employee->id,
            'code' => $code,
            'status' => 'active',
            'generated_at' => now('Asia/Manila'),
            'generated_by' => $actor->id,
        ]);

        $this->audit->log('generate_employee_qr', 'attendance', EmployeeQrCode::class, $qr->id, null, $qr->toArray());

        return $qr;
    }

    public function nextEmployeeQrIdentifier(): string
    {
        $year = now('Asia/Manila')->format('Y');
        $prefix = sprintf('EMP-%s-', $year);

        $latest = EmployeeQrCode::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $sequence = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('EMP-%s-%06d', $year, $sequence);
            $sequence++;
        } while (EmployeeQrCode::query()->where('code', $candidate)->exists());

        return $candidate;
    }

    /**
     * Apply a DTR correction while preserving original values.
     *
     * @param  array<string, mixed>  $changes
     */
    public function correctRecord(AttendanceRecord $record, array $changes, string $reason, User $actor, Request $request): AttendanceRecord
    {
        $original = $record->only(['time_in', 'time_out', 'status', 'remarks', 'late_minutes', 'undertime_minutes', 'overtime_minutes', 'total_minutes']);

        foreach (['time_in', 'time_out', 'status', 'remarks'] as $field) {
            if (! array_key_exists($field, $changes) || $changes[$field] === null || $changes[$field] === '') {
                continue;
            }

            $old = $record->{$field};
            $new = $changes[$field];

            if (in_array($field, ['time_in', 'time_out'], true)) {
                $new = Carbon::parse($new, 'Asia/Manila');
                $oldStr = $old ? Carbon::parse($old)->timezone('Asia/Manila')->format('Y-m-d H:i:s') : null;
                $newStr = $new->format('Y-m-d H:i:s');
            } else {
                $oldStr = $old !== null ? (string) $old : null;
                $newStr = (string) $new;
            }

            if ($oldStr === $newStr) {
                continue;
            }

            $record->{$field} = $new;

            $record->adjustments()->create([
                'user_id' => $record->user_id,
                'field_name' => $field,
                'original_value' => $oldStr,
                'corrected_value' => $newStr,
                'reason' => $reason,
                'corrected_by' => $actor->id,
                'corrected_at' => now('Asia/Manila'),
                'ip_address' => $request->ip(),
                'device' => $this->deviceLabel($request),
            ]);

            $this->writeAttendanceLog(
                $record->user_id,
                $record->id,
                'DTR Correction',
                [$field => $oldStr],
                [$field => $newStr],
                $actor,
                $request,
                $reason
            );
        }

        $record->is_corrected = true;
        $record->source = 'manual';
        $record->save();

        $this->recalculateMetrics($record->fresh());

        $this->audit->log('dtr_correction', 'attendance', AttendanceRecord::class, $record->id, $original, $record->fresh()->toArray());

        $this->notifications->notifyAdmins(
            'attendance_correction',
            'DTR correction applied',
            $actor->displayName().' corrected attendance for '.$record->user->displayName(),
            route('attendance.records.show', $record)
        );

        if ($record->user && $record->user->isEmployee()) {
            $this->notifications->notify(
                $record->user_id,
                'attendance_adjustment',
                'Attendance adjustment made',
                'An administrator updated your attendance for '.ph_datetime($record->attendance_date, 'M d, Y').'.',
                route('employee.attendance')
            );
        }

        return $record->fresh(['user', 'adjustments']);
    }

    protected function notifyPunch(User $employee, string $action, string $status, Carbon $time): void
    {
        $label = $action === 'time_in' ? 'Time In' : 'Time Out';
        $adminTitle = $label.' recorded';
        $adminMessage = $employee->displayName().' — '.$label.' at '.$time->format('h:i:s A');

        $employeeTitle = $label.' successfully recorded';
        $employeeMessage = 'Your '.$label.' was recorded at '.$time->format('h:i:s A').'.';

        if ($action === 'time_in' && $status === 'late') {
            $adminTitle = 'Late arrival';
            $adminMessage = $employee->displayName().' timed in late at '.$time->format('h:i:s A');
            $employeeTitle = 'Late attendance';
            $employeeMessage = 'Your Time In at '.$time->format('h:i:s A').' was marked late.';
        }

        $this->notifications->notifyAdmins(
            'attendance_'.$action,
            $adminTitle,
            $adminMessage,
            route('attendance.dashboard')
        );

        $this->notifications->notify(
            $employee->id,
            'attendance_'.$action,
            $employeeTitle,
            $employeeMessage,
            $employee->isEmployee()
                ? route('employee.dashboard')
                : route('attendance.monthly', ['employee_id' => $employee->id])
        );
    }

    protected function writeAttendanceLog(
        ?int $userId,
        ?int $recordId,
        string $action,
        mixed $original,
        mixed $new,
        ?User $actor,
        Request $request,
        ?string $reason,
    ): AttendanceLog {
        return AttendanceLog::query()->create([
            'user_id' => $userId,
            'attendance_record_id' => $recordId,
            'action' => $action,
            'original_value' => $original === null ? null : (is_string($original) ? $original : json_encode($original)),
            'new_value' => $new === null ? null : (is_string($new) ? $new : json_encode($new)),
            'performed_by' => $actor?->id,
            'logged_at' => now('Asia/Manila'),
            'ip_address' => $request->ip(),
            'device' => $this->deviceLabel($request),
            'reason' => $reason,
        ]);
    }

    protected function logScan(
        ?int $userId,
        string $qrCode,
        ?string $action,
        string $result,
        string $remarks,
        User $scanner,
        Request $request,
        Carbon $now,
    ): void {
        AttendanceQrScanLog::query()->create([
            'user_id' => $userId,
            'qr_code' => $qrCode,
            'action' => $action,
            'scan_date' => $now->toDateString(),
            'scan_time' => $now->format('H:i:s'),
            'scanned_by' => $scanner->id,
            'device' => $this->deviceLabel($request),
            'result' => $result,
            'remarks' => $remarks,
            'ip_address' => $request->ip(),
        ]);
    }

    protected function deviceLabel(Request $request): string
    {
        $ua = (string) $request->userAgent();
        if ($ua === '') {
            return 'Unknown device';
        }

        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            return 'Mobile/Tablet';
        }

        if (preg_match('/Windows|Macintosh|Linux/i', $ua)) {
            return 'Desktop/Webcam';
        }

        return substr($ua, 0, 120);
    }

    protected function normalizeTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $time = (string) $time;
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        return $time;
    }

    /**
     * @return array{success: bool, code: string, message: string, title: string, action?: string, employee?: array<string, mixed>, record?: array<string, mixed>, time?: string}
     */
    protected function scanResponse(
        bool $success,
        string $code,
        string $title,
        string $message,
        ?User $employee = null,
        ?AttendanceRecord $record = null,
        ?string $action = null,
        ?string $time = null,
    ): array {
        $payload = [
            'success' => $success,
            'code' => $code,
            'title' => $title,
            'message' => $message,
        ];

        if ($action) {
            $payload['action'] = $action;
        }

        if ($time) {
            $payload['time'] = $time;
        }

        if ($employee) {
            $payload['employee'] = [
                'id' => $employee->id,
                'name' => $employee->displayName(),
                'employee_id' => $employee->employee_id,
                'department' => $employee->department,
                'position' => $employee->position,
                'status' => $employee->status,
                'qr_code' => $employee->activeQrCode?->code,
            ];
        }

        if ($record) {
            $payload['record'] = [
                'id' => $record->id,
                'date' => $record->attendance_date?->toDateString(),
                'time_in' => $record->time_in ? ph_datetime($record->time_in, 'h:i:s A') : null,
                'time_out' => $record->time_out ? ph_datetime($record->time_out, 'h:i:s A') : null,
                'status' => $record->statusLabel(),
                'late_minutes' => $record->late_minutes,
            ];
        }

        return $payload;
    }
}
