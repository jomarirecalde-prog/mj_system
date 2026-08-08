<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAdjustment;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $adjustments = AttendanceAdjustment::query()
            ->with(['employee', 'corrector', 'attendanceRecord'])
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('employee', function ($uq) use ($search) {
                    $uq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->latest('corrected_at')
            ->paginate(40);

        $pendingCount = AttendanceCorrectionRequest::query()->where('status', 'pending')->count();

        return view('attendance.corrections.index', compact('adjustments', 'pendingCount'));
    }

    public function create(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $record = null;
        if ($request->filled('record_id')) {
            $record = AttendanceRecord::query()->with('user')->find($request->input('record_id'));
        }

        $employees = User::query()
            ->where('status', 'active')
            ->whereNotNull('employee_id')
            ->orderBy('full_name')
            ->get();

        return view('attendance.corrections.create', compact('record', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $data = $request->validate([
            'attendance_record_id' => ['nullable', 'exists:attendance_records,id'],
            'user_id' => ['required_without:attendance_record_id', 'nullable', 'exists:users,id'],
            'attendance_date' => ['required_without:attendance_record_id', 'nullable', 'date'],
            'time_in' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date', 'after_or_equal:time_in'],
            'status' => ['nullable', 'in:present,late,absent,on_leave,official_business,half_day,undertime,incomplete,rest_day'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! empty($data['attendance_record_id'])) {
            $record = AttendanceRecord::query()->findOrFail($data['attendance_record_id']);
        } else {
            $schedule = $this->attendance->resolveSchedule(
                User::query()->findOrFail($data['user_id']),
                $data['attendance_date']
            );

            $record = AttendanceRecord::query()->firstOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'attendance_date' => $data['attendance_date'],
                ],
                [
                    'schedule_time_in' => $schedule['time_in'],
                    'schedule_time_out' => $schedule['time_out'],
                    'shift_name' => $schedule['shift_name'],
                    'status' => 'incomplete',
                    'source' => 'manual',
                ]
            );
        }

        $this->attendance->correctRecord(
            $record,
            [
                'time_in' => $data['time_in'] ?? null,
                'time_out' => $data['time_out'] ?? null,
                'status' => $data['status'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ],
            $data['reason'],
            $request->user(),
            $request
        );

        return redirect()
            ->route('attendance.records.show', $record)
            ->with('success', 'DTR correction saved. Original values were preserved in the audit trail.');
    }

    public function requests(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $requests = AttendanceCorrectionRequest::query()
            ->with(['user', 'reviewer', 'attendanceRecord'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30);

        return view('attendance.corrections.requests', compact('requests'));
    }

    public function approveRequest(Request $request, AttendanceCorrectionRequest $correctionRequest): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        if (! $correctionRequest->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $data = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = $correctionRequest->user;
        $schedule = $this->attendance->resolveSchedule($employee, $correctionRequest->attendance_date->toDateString());

        $record = $correctionRequest->attendanceRecord
            ?? AttendanceRecord::query()->firstOrCreate(
                [
                    'user_id' => $correctionRequest->user_id,
                    'attendance_date' => $correctionRequest->attendance_date->toDateString(),
                ],
                [
                    'schedule_time_in' => $schedule['time_in'],
                    'schedule_time_out' => $schedule['time_out'],
                    'shift_name' => $schedule['shift_name'],
                    'status' => 'incomplete',
                    'source' => 'manual',
                ]
            );

        $changes = [];
        if ($correctionRequest->requested_time_in) {
            $changes['time_in'] = $correctionRequest->requested_time_in->timezone('Asia/Manila')->format('Y-m-d H:i:s');
        }
        if ($correctionRequest->requested_time_out) {
            $changes['time_out'] = $correctionRequest->requested_time_out->timezone('Asia/Manila')->format('Y-m-d H:i:s');
        }

        $this->attendance->correctRecord(
            $record,
            $changes,
            'Approved employee request #'.$correctionRequest->id.': '.$correctionRequest->reason,
            $request->user(),
            $request
        );

        $correctionRequest->update([
            'attendance_record_id' => $record->id,
            'status' => 'approved',
            'admin_remarks' => $data['admin_remarks'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now('Asia/Manila'),
        ]);

        $this->notifications->notify(
            $correctionRequest->user_id,
            'dtr_correction_approved',
            'DTR Correction Approved',
            'Your DTR correction request for '.ph_datetime($correctionRequest->attendance_date, 'F j, Y').' has been approved.',
            route('employee.corrections.index')
        );

        return redirect()
            ->route('attendance.correction-requests.index')
            ->with('success', 'Correction request approved and applied to DTR.');
    }

    public function rejectRequest(Request $request, AttendanceCorrectionRequest $correctionRequest): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        if (! $correctionRequest->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $data = $request->validate([
            'admin_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $correctionRequest->update([
            'status' => 'rejected',
            'admin_remarks' => $data['admin_remarks'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now('Asia/Manila'),
        ]);

        $this->notifications->notify(
            $correctionRequest->user_id,
            'dtr_correction_rejected',
            'DTR Correction Rejected',
            'Your DTR correction request for '.ph_datetime($correctionRequest->attendance_date, 'F j, Y').' was rejected. Reason: '.$data['admin_remarks'],
            route('employee.corrections.index')
        );

        return redirect()
            ->route('attendance.correction-requests.index')
            ->with('success', 'Correction request rejected.');
    }
}
