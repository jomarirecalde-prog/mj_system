<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\OfficialTimeRequest;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\OfficialTimeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficialTimeRequestController extends Controller
{
    public function __construct(
        protected OfficialTimeRequestService $service,
        protected NotificationService $notifications,
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $user->load('activeSchedule.shift');

        $current = $this->service->currentScheduleSnapshot($user);
        $requests = OfficialTimeRequest::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        $pendingCount = OfficialTimeRequestService::pendingCountForUser($user->id);

        return view('employee.official-time.index', compact('user', 'current', 'requests', 'pendingCount'));
    }

    public function show(Request $request, OfficialTimeRequest $officialTimeRequest): View
    {
        $this->authorizeOwnership($request, $officialTimeRequest);

        $officialTimeRequest->load(['reviewer', 'employeeSchedule']);

        return view('employee.official-time.show', [
            'request' => $officialTimeRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $current = $this->service->currentScheduleSnapshot($user);

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_permanent' => ['nullable', 'boolean'],
            'requested_time_in' => ['required', 'date_format:H:i'],
            'requested_time_out' => ['required', 'date_format:H:i'],
            'requested_break_start' => ['nullable', 'date_format:H:i'],
            'requested_break_end' => ['nullable', 'date_format:H:i', 'required_with:requested_break_start'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->boolean('is_permanent')) {
            $data['effective_to'] = null;
            $data['request_type'] = 'permanent';
        } else {
            if (empty($data['effective_to'])) {
                return back()->withInput()->withErrors([
                    'effective_to' => 'Effective To is required unless the request is permanent.',
                ]);
            }
            $data['request_type'] = 'temporary';
        }

        if (! empty($data['requested_break_start']) && empty($data['requested_break_end'])) {
            return back()->withInput()->withErrors([
                'requested_break_end' => 'Break end is required when break start is provided.',
            ]);
        }

        $pendingExists = OfficialTimeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->where(function ($q) use ($data) {
                $end = $data['effective_to'] ?? '9999-12-31';
                $q->whereDate('effective_from', '<=', $end)
                    ->where(function ($q2) use ($data) {
                        $q2->whereNull('effective_to')
                            ->orWhereDate('effective_to', '>=', $data['effective_from']);
                    });
            })
            ->exists();

        if ($pendingExists) {
            return back()
                ->withInput()
                ->with('error', 'You already have a pending official time request for an overlapping period.');
        }

        $officialTimeRequest = OfficialTimeRequest::query()->create([
            'user_id' => $user->id,
            'request_type' => $data['request_type'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'current_time_in' => $this->toTimeValue($current['time_in']),
            'current_time_out' => $this->toTimeValue($current['time_out']),
            'current_break_start' => $current['break_start'] ? $this->toTimeValue($current['break_start']) : null,
            'current_break_end' => $current['break_end'] ? $this->toTimeValue($current['break_end']) : null,
            'current_schedule_type' => $current['schedule_type'],
            'requested_time_in' => $data['requested_time_in'].':00',
            'requested_time_out' => $data['requested_time_out'].':00',
            'requested_break_start' => ! empty($data['requested_break_start']) ? $data['requested_break_start'].':00' : null,
            'requested_break_end' => ! empty($data['requested_break_end']) ? $data['requested_break_end'].':00' : null,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        $this->audit->log(
            'submit_official_time_request',
            'attendance',
            OfficialTimeRequest::class,
            $officialTimeRequest->id,
            null,
            $officialTimeRequest->toArray()
        );

        $this->notifications->notifyAdmins(
            'official_time_request',
            'New Official Time Request',
            'New Official Time Request from '.$user->displayName().'.',
            route('attendance.official-time.show', $officialTimeRequest)
        );

        return redirect()
            ->route('employee.official-time.index')
            ->with('success', 'Official Time Request submitted. Your request has been sent to the Super Admin for approval.');
    }

    public function cancel(Request $request, OfficialTimeRequest $officialTimeRequest): RedirectResponse
    {
        $this->authorizeOwnership($request, $officialTimeRequest);

        try {
            $this->service->cancel($officialTimeRequest, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('employee.official-time.show', $officialTimeRequest)
            ->with('success', 'Official time request cancelled.');
    }

    protected function authorizeOwnership(Request $request, OfficialTimeRequest $officialTimeRequest): void
    {
        if ($officialTimeRequest->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    protected function toTimeValue(?string $time): string
    {
        $normalized = substr((string) $time, 0, 5);

        return strlen($normalized) === 5 ? $normalized.':00' : '00:00:00';
    }
}
