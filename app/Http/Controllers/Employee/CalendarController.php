<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Support\EmployeeAttendancePresenter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $month = $request->input('month', now('Asia/Manila')->format('Y-m'));
        $start = Carbon::parse($month.'-01', 'Asia/Manila')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $byDate = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->attendance_date->toDateString());

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $record = $byDate->get($key);
            $schedule = $this->attendance->resolveSchedule($user, $key);
            $fallback = $this->attendance->precomputeDayStatus($user, $key, $schedule);
            if ($fallback === 'incomplete' && $cursor->isPast() && ! $cursor->isToday()) {
                $fallback = 'absent';
            }

            $days[$key] = [
                'date' => $key,
                'day' => $cursor->day,
                'status' => EmployeeAttendancePresenter::calendarStatus($record, $fallback),
                'label' => $record
                    ? EmployeeAttendancePresenter::displayStatus($record, $cursor)
                    : ucfirst(str_replace('_', ' ', $fallback)),
            ];

            $cursor->addDay();
        }

        return view('employee.calendar', compact('user', 'month', 'start', 'days'));
    }

    public function day(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->validate(['date' => ['required', 'date']])['date'];

        $record = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $schedule = $this->attendance->resolveSchedule($user, $date);
        $fallback = $this->attendance->precomputeDayStatus($user, $date, $schedule);

        return $this->jsonSuccess([
            'date' => Carbon::parse($date, 'Asia/Manila')->format('M d, Y'),
            'schedule' => EmployeeAttendancePresenter::scheduleRange($schedule),
            'time_in' => $record?->time_in ? ph_datetime($record->time_in, 'h:i A') : '—',
            'time_out' => $record?->time_out ? ph_datetime($record->time_out, 'h:i A') : '—',
            'hours' => $record?->totalHoursLabel() ?? '—',
            'late' => $record ? $record->minutesLabel($record->late_minutes) : '—',
            'undertime' => $record ? $record->minutesLabel($record->undertime_minutes) : '—',
            'overtime' => $record ? $record->minutesLabel($record->overtime_minutes) : '—',
            'status' => $record
                ? EmployeeAttendancePresenter::displayStatus($record, Carbon::parse($date, 'Asia/Manila'))
                : ucfirst(str_replace('_', ' ', $fallback)),
            'remarks' => $record?->remarks ?: '—',
        ]);
    }
}
