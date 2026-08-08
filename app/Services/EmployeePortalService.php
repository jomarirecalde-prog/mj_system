<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Support\EmployeeAttendancePresenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeePortalService
{
    public function __construct(protected AttendanceService $attendance) {}

    public function todayRecord(User $employee): ?AttendanceRecord
    {
        $date = now('Asia/Manila')->toDateString();

        return AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function todayCard(User $employee): array
    {
        $date = now('Asia/Manila')->toDateString();
        $schedule = $this->attendance->resolveSchedule($employee, $date);
        $record = $this->todayRecord($employee);

        return EmployeeAttendancePresenter::todayPayload($record, $schedule);
    }

    /**
     * @return array{present: int, late: int, absent: int, undertime_minutes: int, overtime_minutes: int, total_minutes: int, late_days: int, undertime_days: int, overtime_days: int}
     */
    public function monthSummary(User $employee, ?string $month = null): array
    {
        $month = $month ?: now('Asia/Manila')->format('Y-m');
        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $records = AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->attendance_date->toDateString());

        $summary = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'late_days' => 0,
            'undertime_days' => 0,
            'overtime_days' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'total_minutes' => 0,
        ];

        $cursor = Carbon::parse($start, 'Asia/Manila');
        $last = Carbon::parse($end, 'Asia/Manila');
        $today = now('Asia/Manila')->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $record = $records->get($key);
            $schedule = $this->attendance->resolveSchedule($employee, $key);

            if ($record) {
                if (in_array($record->status, ['present', 'late', 'undertime'], true) || ($record->time_in && $record->time_out)) {
                    $summary['present']++;
                }
                if ($record->status === 'absent') {
                    $summary['absent']++;
                }
                if ((int) $record->late_minutes > 0 || $record->status === 'late') {
                    $summary['late_days']++;
                    $summary['late'] += (int) $record->late_minutes;
                }
                if ((int) $record->undertime_minutes > 0) {
                    $summary['undertime_days']++;
                    $summary['undertime_minutes'] += (int) $record->undertime_minutes;
                }
                if ((int) $record->overtime_minutes > 0) {
                    $summary['overtime_days']++;
                    $summary['overtime_minutes'] += (int) $record->overtime_minutes;
                }
                $summary['total_minutes'] += (int) ($record->total_minutes ?? 0);
            } else {
                $status = $this->attendance->precomputeDayStatus($employee, $key, $schedule);
                if ($status === 'incomplete' && $cursor->lt($today)) {
                    $status = 'absent';
                }
                if ($status === 'absent') {
                    $summary['absent']++;
                }
            }

            $cursor->addDay();
        }

        return $summary;
    }

    /**
     * Build month DTR day list + totals (same logic as admin monthly view, locked to one employee).
     *
     * @return array{records: Collection<int, object>, totals: array<string, int>}
     */
    public function monthlyDtr(User $employee, string $month): array
    {
        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $byDate = AttendanceRecord::query()
            ->where('user_id', $employee->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->attendance_date->toDateString());

        $records = collect();
        $totals = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'undertime' => 0,
            'overtime' => 0,
            'hours' => 0,
        ];

        $cursor = Carbon::parse($start, 'Asia/Manila');
        $last = Carbon::parse($end, 'Asia/Manila');

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $record = $byDate->get($key);
            $schedule = $this->attendance->resolveSchedule($employee, $key);

            if (! $record) {
                $status = $this->attendance->precomputeDayStatus($employee, $key, $schedule);
                if ($status === 'incomplete' && $cursor->isPast() && ! $cursor->isToday()) {
                    $status = 'absent';
                }
                $records->push((object) [
                    'date' => $cursor->copy(),
                    'record' => null,
                    'status' => $status,
                    'schedule' => $schedule,
                ]);
                if ($status === 'absent') {
                    $totals['absent']++;
                }
            } else {
                $records->push((object) [
                    'date' => $cursor->copy(),
                    'record' => $record,
                    'status' => $record->status,
                    'schedule' => $schedule,
                ]);
                if (in_array($record->status, ['present', 'late', 'undertime'], true) || ($record->time_in && $record->time_out)) {
                    $totals['present']++;
                }
                if ($record->status === 'absent') {
                    $totals['absent']++;
                }
                if ((int) $record->late_minutes > 0) {
                    $totals['late'] += (int) $record->late_minutes;
                }
                $totals['undertime'] += (int) $record->undertime_minutes;
                $totals['overtime'] += (int) $record->overtime_minutes;
                $totals['hours'] += (int) ($record->total_minutes ?? 0);
            }

            $cursor->addDay();
        }

        return compact('records', 'totals');
    }
}
