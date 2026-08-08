<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function dashboard(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $date = now('Asia/Manila')->toDateString();
        $counts = $this->attendance->dashboardCounts($date);
        $currentlyIn = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderBy('time_in')
            ->get();

        $recent = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->whereNotNull('time_in')
            ->latest('updated_at')
            ->limit(15)
            ->get();

        return view('attendance.dashboard', compact('counts', 'currentlyIn', 'recent', 'date'));
    }

    public function liveStats(Request $request): JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $date = $request->input('date', now('Asia/Manila')->toDateString());
        $counts = $this->attendance->dashboardCounts($date);

        $currentlyIn = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderBy('time_in')
            ->get()
            ->map(function (AttendanceRecord $record) {
                $minutes = $record->time_in
                    ? $record->time_in->diffInMinutes(now('Asia/Manila'))
                    : 0;

                return [
                    'id' => $record->id,
                    'employee' => $record->user?->displayName(),
                    'employee_id' => $record->user?->employee_id,
                    'department' => $record->user?->department,
                    'time_in' => ph_datetime($record->time_in, 'h:i A'),
                    'duration' => sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60),
                    'status' => $record->statusLabel(),
                    'duration_minutes' => $minutes,
                ];
            });

        return $this->jsonSuccess([
            'counts' => $counts,
            'currently_in' => $currentlyIn,
            'server_time' => now('Asia/Manila')->format('h:i:s A'),
        ]);
    }

    public function today(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $date = $request->input('date', now('Asia/Manila')->toDateString());
        $query = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date);

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($dept = $request->input('department')) {
            $query->whereHas('user', fn ($q) => $q->where('department', $dept));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $records = $query->orderByDesc('time_in')->paginate(50);
        $counts = $this->attendance->dashboardCounts($date);
        $departments = User::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');

        return view('attendance.today', compact('records', 'counts', 'date', 'departments'));
    }

    public function currentlyIn(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $date = now('Asia/Manila')->toDateString();
        $records = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderBy('time_in')
            ->get();

        return view('attendance.currently-in', compact('records', 'date'));
    }

    public function records(Request $request): View|JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $query = AttendanceRecord::query()->with('user');

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($dept = $request->input('department')) {
            $query->whereHas('user', fn ($q) => $q->where('department', $dept));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('attendance_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('attendance_date', '<=', $to);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('attendance_date', $date);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('user_id', $employeeId);
        }

        $query->orderByDesc('attendance_date')->orderByDesc('time_in');

        if ($request->wantsJson() || $request->boolean('ajax')) {
            $records = $query->limit(200)->get()->map(fn (AttendanceRecord $r) => $this->mapRecordRow($r));

            return $this->jsonSuccess(['records' => $records]);
        }

        $records = $query->paginate(40);
        $departments = User::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');
        $employees = User::query()->where('status', 'active')->whereNotNull('employee_id')->orderBy('full_name')->get(['id', 'full_name', 'name', 'employee_id']);

        return view('attendance.records', compact('records', 'departments', 'employees'));
    }

    public function show(AttendanceRecord $record): View
    {
        $this->authorizeRoles(['admin', 'staff']);
        $record->load(['user', 'adjustments.corrector', 'logs.performer', 'timeInBy', 'timeOutBy']);

        return view('attendance.show', compact('record'));
    }

    public function monthly(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $employeeId = $request->input('employee_id');
        $month = $request->input('month', now('Asia/Manila')->format('Y-m'));

        $employees = User::query()
            ->where('status', 'active')
            ->whereNotNull('employee_id')
            ->orderBy('full_name')
            ->get();

        $employee = $employeeId
            ? User::query()->find($employeeId)
            : $employees->first();

        $records = collect();
        $totals = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'undertime' => 0,
            'overtime' => 0,
            'hours' => 0,
        ];

        if ($employee) {
            $start = $month.'-01';
            $end = date('Y-m-t', strtotime($start));

            $byDate = AttendanceRecord::query()
                ->where('user_id', $employee->id)
                ->whereBetween('attendance_date', [$start, $end])
                ->get()
                ->keyBy(fn ($r) => $r->attendance_date->toDateString());

            $cursor = \Carbon\Carbon::parse($start, 'Asia/Manila');
            $last = \Carbon\Carbon::parse($end, 'Asia/Manila');

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
                    ]);
                    if ($status === 'absent') {
                        $totals['absent']++;
                    }
                } else {
                    $records->push((object) [
                        'date' => $cursor->copy(),
                        'record' => $record,
                        'status' => $record->status,
                    ]);
                    if (in_array($record->status, ['present', 'late', 'undertime'], true)) {
                        $totals['present']++;
                    }
                    if ($record->status === 'absent') {
                        $totals['absent']++;
                    }
                    if ($record->late_minutes > 0) {
                        $totals['late'] += $record->late_minutes;
                    }
                    $totals['undertime'] += (int) $record->undertime_minutes;
                    $totals['overtime'] += (int) $record->overtime_minutes;
                    $totals['hours'] += (int) ($record->total_minutes ?? 0);
                }

                $cursor->addDay();
            }
        }

        return view('attendance.monthly', compact('employees', 'employee', 'month', 'records', 'totals'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapRecordRow(AttendanceRecord $r): array
    {
        return [
            'id' => $r->id,
            'date' => $r->attendance_date?->format('M d, Y'),
            'employee_id' => $r->user?->employee_id,
            'employee_name' => $r->user?->displayName(),
            'department' => $r->user?->department,
            'schedule' => $r->scheduleLabel(),
            'time_in' => $r->time_in ? ph_datetime($r->time_in, 'h:i:s A') : '—',
            'time_out' => $r->time_out ? ph_datetime($r->time_out, 'h:i:s A') : '—',
            'total_hours' => $r->totalHoursLabel(),
            'late' => $r->minutesLabel($r->late_minutes),
            'undertime' => $r->minutesLabel($r->undertime_minutes),
            'overtime' => $r->minutesLabel($r->overtime_minutes),
            'status' => $r->statusLabel(),
            'remarks' => $r->remarks ?: '—',
            'url' => route('attendance.records.show', $r),
        ];
    }
}
