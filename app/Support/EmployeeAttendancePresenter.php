<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class EmployeeAttendancePresenter
{
    /**
     * Human-readable attendance status for the employee portal.
     */
    public static function displayStatus(?AttendanceRecord $record, ?Carbon $forDate = null): string
    {
        $today = ($forDate ?? now('Asia/Manila'))->toDateString();

        if ($record === null) {
            return 'Not Yet Timed In';
        }

        $date = $record->attendance_date?->toDateString();

        if (in_array($record->status, ['on_leave', 'official_business', 'rest_day', 'absent'], true)
            && $record->time_in === null) {
            return match ($record->status) {
                'on_leave' => 'On Leave',
                'official_business' => 'Official Business',
                'rest_day' => 'Rest Day',
                'absent' => 'Absent',
                default => $record->statusLabel(),
            };
        }

        if ($record->time_in === null) {
            if ($date === $today) {
                return 'Not Yet Timed In';
            }

            return match ($record->status) {
                'incomplete' => 'Incomplete DTR',
                'absent' => 'Absent',
                default => $record->statusLabel(),
            };
        }

        if ($record->time_out === null) {
            if ($date === $today) {
                return 'Currently Time In';
            }

            return 'Incomplete DTR';
        }

        if ($record->status === 'late') {
            return 'Late';
        }
        if ($record->status === 'undertime') {
            return 'Undertime';
        }
        if ($record->status === 'overtime' || ((int) $record->overtime_minutes > 0 && $record->status === 'present')) {
            return ((int) $record->overtime_minutes > 0 && $record->status === 'present')
                ? 'Overtime'
                : $record->statusLabel();
        }

        if ($record->status === 'present' || $record->status === 'late') {
            return $record->status === 'late' ? 'Late' : 'Present';
        }

        if ($record->time_in && $record->time_out) {
            if ($record->status === 'incomplete') {
                return 'Incomplete DTR';
            }

            return match ($record->status) {
                'present' => 'Present',
                'late' => 'Late',
                'undertime' => 'Undertime',
                default => $record->statusLabel(),
            };
        }

        return $record->statusLabel();
    }

    /**
     * Compact status for calendar cells.
     */
    public static function calendarStatus(?AttendanceRecord $record, string $fallbackStatus): string
    {
        if ($record === null) {
            return $fallbackStatus;
        }

        if ($record->isCurrentlyIn()) {
            return 'incomplete';
        }

        return $record->status;
    }

    /**
     * @return array{label: string, time_in: string, time_out: string, hours: string, schedule: string, late: string, undertime: string, overtime: string, status: string, remarks: string}
     */
    public static function todayPayload(?AttendanceRecord $record, array $schedule): array
    {
        $status = self::displayStatus($record);

        return [
            'label' => $status,
            'time_in' => $record?->time_in ? ph_datetime($record->time_in, 'h:i A') : '—',
            'time_out' => $record?->time_out
                ? ph_datetime($record->time_out, 'h:i A')
                : ($record?->time_in ? 'Not yet recorded' : '—'),
            'hours' => $record?->time_out
                ? $record->totalHoursLabel()
                : ($record?->time_in
                    ? self::runningHours($record)
                    : '—'),
            'schedule' => self::scheduleRange($schedule),
            'late' => $record ? $record->minutesLabel($record->late_minutes) : '0',
            'undertime' => $record ? $record->minutesLabel($record->undertime_minutes) : '0',
            'overtime' => $record ? $record->minutesLabel($record->overtime_minutes) : '0',
            'status' => $status,
            'remarks' => $record?->remarks ?: '—',
            'record_status' => $record?->status,
            'has_time_in' => (bool) $record?->time_in,
            'has_time_out' => (bool) $record?->time_out,
        ];
    }

    public static function scheduleRange(array $schedule): string
    {
        $in = isset($schedule['time_in']) ? substr((string) $schedule['time_in'], 0, 5) : null;
        $out = isset($schedule['time_out']) ? substr((string) $schedule['time_out'], 0, 5) : null;

        if (! $in || ! $out) {
            return '—';
        }

        $inLabel = Carbon::createFromFormat('H:i', $in, 'Asia/Manila')->format('g:i A');
        $outLabel = Carbon::createFromFormat('H:i', $out, 'Asia/Manila')->format('g:i A');

        return $inLabel.' – '.$outLabel;
    }

    public static function runningHours(AttendanceRecord $record): string
    {
        if (! $record->time_in) {
            return '—';
        }

        $minutes = $record->time_in->diffInMinutes(now('Asia/Manila'));
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%dh %02dm', $h, $m);
    }

    public static function minutesToLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0';
        }

        if ($minutes < 60) {
            return $minutes.' minutes';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($m === 0) {
            return $h.' '.($h === 1 ? 'hour' : 'hours');
        }

        return $h.'h '.$m.'m';
    }
}
