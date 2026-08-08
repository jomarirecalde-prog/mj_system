<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $requests = AttendanceCorrectionRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('employee.corrections.index', compact('requests'));
    }

    public function create(Request $request): View
    {
        $date = $request->input('date', now('Asia/Manila')->toDateString());
        $record = AttendanceRecord::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $date)
            ->first();

        return view('employee.corrections.create', compact('date', 'record'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'issue_type' => ['required', Rule::in([
                'missing_time_in',
                'missing_time_out',
                'incorrect_time_in',
                'incorrect_time_out',
                'other',
            ])],
            'requested_time_in' => ['nullable', 'date_format:H:i'],
            'requested_time_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $record = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $data['attendance_date'])
            ->first();

        $pendingExists = AttendanceCorrectionRequest::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $data['attendance_date'])
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            return back()
                ->withInput()
                ->with('error', 'You already have a pending correction request for this date.');
        }

        $requestedIn = null;
        $requestedOut = null;
        if (! empty($data['requested_time_in'])) {
            $requestedIn = $data['attendance_date'].' '.$data['requested_time_in'].':00';
        }
        if (! empty($data['requested_time_out'])) {
            $requestedOut = $data['attendance_date'].' '.$data['requested_time_out'].':00';
        }

        $correction = AttendanceCorrectionRequest::query()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record?->id,
            'attendance_date' => $data['attendance_date'],
            'issue_type' => $data['issue_type'],
            'requested_time_in' => $requestedIn,
            'requested_time_out' => $requestedOut,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        $this->notifications->notifyAdmins(
            'dtr_correction_request',
            'DTR correction request',
            $user->displayName().' submitted a correction request for '.ph_datetime($correction->attendance_date, 'M d, Y').'.',
            route('attendance.correction-requests.index')
        );

        return redirect()
            ->route('employee.corrections.index')
            ->with('success', 'Correction request submitted for review.');
    }
}
