<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSchedule;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $user->load('activeSchedule.shift');

        $schedule = $user->activeSchedule;
        $resolved = $this->attendance->resolveSchedule($user, now('Asia/Manila')->toDateString());

        $upcoming = EmployeeSchedule::query()
            ->with('shift')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $today = now('Asia/Manila')->toDateString();
                $q->whereDate('effective_from', '>', $today);
            })
            ->orderBy('effective_from')
            ->get();

        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $workDays = collect($resolved['work_days'] ?? [])
            ->map(fn ($d) => $dayNames[(int) $d] ?? $d)
            ->implode(', ');
        $restDays = collect($resolved['rest_days'] ?? [])
            ->map(fn ($d) => $dayNames[(int) $d] ?? $d)
            ->implode(', ');

        return view('employee.schedule', compact(
            'user',
            'schedule',
            'resolved',
            'upcoming',
            'workDays',
            'restDays'
        ));
    }
}
