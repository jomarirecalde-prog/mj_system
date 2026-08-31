<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePortalService;
use App\Services\OfficialTimeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected EmployeePortalService $portal) {}

    public function index(): View
    {
        $user = auth()->user();
        $user->load(['activeSchedule.shift', 'activeQrCode']);

        $today = $this->portal->todayCard($user);
        $summary = $this->portal->monthSummary($user);
        $schedule = $user->activeSchedule;
        $officialTimePending = OfficialTimeRequestService::pendingCountForUser($user->id);
        $latestOfficialTime = $user->officialTimeRequests()->latest()->first();

        return view('employee.dashboard', compact('user', 'today', 'summary', 'schedule', 'officialTimePending', 'latestOfficialTime'));
    }

    public function live(): JsonResponse
    {
        $user = auth()->user();
        $today = $this->portal->todayCard($user);
        $summary = $this->portal->monthSummary($user);

        return $this->jsonSuccess([
            'today' => $today,
            'summary' => [
                'present' => $summary['present'],
                'late_days' => $summary['late_days'],
                'absent' => $summary['absent'],
                'undertime' => \App\Support\EmployeeAttendancePresenter::minutesToLabel($summary['undertime_minutes']),
                'overtime' => \App\Support\EmployeeAttendancePresenter::minutesToLabel($summary['overtime_minutes']),
                'total_hours' => \App\Support\EmployeeAttendancePresenter::minutesToLabel($summary['total_minutes']),
            ],
            'server_time' => now('Asia/Manila')->format('h:i:s A'),
        ]);
    }
}
