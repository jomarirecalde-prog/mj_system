<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->input('filter', 'this_month');
        $from = $request->input('from');
        $to = $request->input('to');

        [$start, $end] = $this->resolveRange($filter, $from, $to);

        $records = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->orderByDesc('attendance_date')
            ->paginate(31)
            ->withQueryString();

        return view('employee.attendance', compact('records', 'filter', 'from', 'to', 'start', 'end'));
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveRange(string $filter, ?string $from, ?string $to): array
    {
        $now = now('Asia/Manila');

        return match ($filter) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'this_week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ],
            'custom' => [
                $from ?: $now->copy()->startOfMonth()->toDateString(),
                $to ?: $now->toDateString(),
            ],
            default => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        };
    }
}
