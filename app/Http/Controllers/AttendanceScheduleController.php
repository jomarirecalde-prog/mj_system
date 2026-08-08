<?php

namespace App\Http\Controllers;

use App\Models\AttendanceShift;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceScheduleController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $schedules = EmployeeSchedule::query()
            ->with(['user', 'shift'])
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30);

        $shifts = AttendanceShift::query()->where('is_active', true)->orderBy('name')->get();

        return view('attendance.schedules.index', compact('schedules', 'shifts'));
    }

    public function create(): View
    {
        $this->authorizeRoles(['admin']);

        $employees = User::query()->where('status', 'active')->whereNotNull('employee_id')->orderBy('full_name')->get();
        $shifts = AttendanceShift::query()->where('is_active', true)->orderBy('name')->get();

        return view('attendance.schedules.create', compact('employees', 'shifts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $this->validated($request);

        if ($request->filled('shift_id')) {
            $shift = AttendanceShift::query()->findOrFail($request->input('shift_id'));
            $data['time_in'] = $shift->time_in;
            $data['time_out'] = $shift->time_out;
            $data['break_start'] = $shift->break_start;
            $data['break_end'] = $shift->break_end;
            $data['schedule_type'] = 'shift';
        }

        $data['work_days'] = $request->input('work_days', [1, 2, 3, 4, 5]);
        $data['rest_days'] = $request->input('rest_days', [0, 6]);
        $data['work_days'] = array_map('intval', (array) $data['work_days']);
        $data['rest_days'] = array_map('intval', (array) $data['rest_days']);

        EmployeeSchedule::query()
            ->where('user_id', $data['user_id'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $schedule = EmployeeSchedule::query()->create($data);
        $this->audit->log('create_schedule', 'attendance', EmployeeSchedule::class, $schedule->id, null, $schedule->toArray());

        $employee = User::query()->find($data['user_id']);
        if ($employee && $employee->isEmployee()) {
            app(\App\Services\NotificationService::class)->notify(
                $employee->id,
                'schedule_assigned',
                'New schedule assigned',
                'A new work schedule has been assigned to your account.',
                route('employee.schedule')
            );
        }

        return redirect()->route('attendance.schedules.index')->with('success', 'Employee schedule saved.');
    }

    public function edit(EmployeeSchedule $schedule): View
    {
        $this->authorizeRoles(['admin']);

        $employees = User::query()->where('status', 'active')->whereNotNull('employee_id')->orderBy('full_name')->get();
        $shifts = AttendanceShift::query()->where('is_active', true)->orderBy('name')->get();

        return view('attendance.schedules.edit', compact('schedule', 'employees', 'shifts'));
    }

    public function update(Request $request, EmployeeSchedule $schedule): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $this->validated($request);
        $previous = $schedule->toArray();

        if ($request->filled('shift_id')) {
            $shift = AttendanceShift::query()->findOrFail($request->input('shift_id'));
            $data['time_in'] = $shift->time_in;
            $data['time_out'] = $shift->time_out;
            $data['break_start'] = $shift->break_start;
            $data['break_end'] = $shift->break_end;
            $data['schedule_type'] = 'shift';
        }

        $data['work_days'] = array_map('intval', (array) $request->input('work_days', [1, 2, 3, 4, 5]));
        $data['rest_days'] = array_map('intval', (array) $request->input('rest_days', [0, 6]));

        $schedule->update($data);
        $this->audit->log('update_schedule', 'attendance', EmployeeSchedule::class, $schedule->id, $previous, $schedule->fresh()->toArray());

        return redirect()->route('attendance.schedules.index')->with('success', 'Schedule updated.');
    }

    public function shifts(): View
    {
        $this->authorizeRoles(['admin']);
        $shifts = AttendanceShift::query()->orderBy('name')->get();

        return view('attendance.schedules.shifts', compact('shifts'));
    }

    public function storeShift(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', 'unique:attendance_shifts,code'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['time_in'] .= ':00';
        $data['time_out'] .= ':00';
        if (! empty($data['break_start'])) {
            $data['break_start'] .= ':00';
        }
        if (! empty($data['break_end'])) {
            $data['break_end'] .= ':00';
        }

        AttendanceShift::query()->create($data);

        return redirect()->route('attendance.shifts.index')->with('success', 'Shift created.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'shift_id' => ['nullable', 'exists:attendance_shifts,id'],
            'schedule_type' => ['nullable', 'in:regular,shift'],
            'time_in' => ['required_without:shift_id', 'nullable', 'date_format:H:i'],
            'time_out' => ['required_without:shift_id', 'nullable', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        foreach (['time_in', 'time_out', 'break_start', 'break_end'] as $field) {
            if (! empty($data[$field]) && preg_match('/^\d{2}:\d{2}$/', $data[$field])) {
                $data[$field] .= ':00';
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['schedule_type'] = $data['schedule_type'] ?? 'regular';

        return $data;
    }
}
