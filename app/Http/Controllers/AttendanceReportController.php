<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    /**
     * @var array<string, string>
     */
    protected array $reportTitles = [
        'daily' => 'Daily Attendance Report',
        'monthly' => 'Monthly DTR Report',
        'late' => 'Late Report',
        'absence' => 'Absence Report',
        'undertime' => 'Undertime Report',
        'overtime' => 'Overtime Report',
        'department' => 'Department Attendance Report',
    ];

    public function index(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('attendance.reports.index', ['reports' => $this->reportTitles]);
    }

    public function show(string $type, Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);
        $this->assertType($type);

        [$headers, $rows, $title] = $this->build($type, $request);

        return view('attendance.reports.show', compact('type', 'headers', 'rows', 'title'));
    }

    public function export(string $type, Request $request): BinaryFileResponse|StreamedResponse|Response|View
    {
        $this->authorizeRoles(['admin', 'staff']);
        $this->assertType($type);

        $format = $request->input('format', 'pdf');
        [$headers, $rows, $title] = $this->build($type, $request);

        return match ($format) {
            'excel' => Excel::download(new AttendanceExport($headers, $rows), $this->slug($title).'.xlsx'),
            'csv' => $this->exportCsv($title, $headers, $rows),
            'print' => view('attendance.reports.print', compact('type', 'headers', 'rows', 'title')),
            default => Pdf::loadView('attendance.reports.pdf', compact('headers', 'rows', 'title'))
                ->download($this->slug($title).'.pdf'),
        };
    }

    protected function assertType(string $type): void
    {
        if (! array_key_exists($type, $this->reportTitles)) {
            abort(404, 'Unknown attendance report.');
        }
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function build(string $type, Request $request): array
    {
        $title = $this->reportTitles[$type];

        return match ($type) {
            'daily' => $this->daily($title, $request),
            'monthly' => $this->monthly($title, $request),
            'late' => $this->statusReport($title, $request, 'late'),
            'absence' => $this->statusReport($title, $request, 'absent'),
            'undertime' => $this->statusReport($title, $request, 'undertime'),
            'overtime' => $this->overtime($title, $request),
            'department' => $this->department($title, $request),
            default => [[], collect(), $title],
        };
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function daily(string $title, Request $request): array
    {
        $date = $request->input('date', now('Asia/Manila')->toDateString());
        $headers = ['Employee ID', 'Name', 'Department', 'Time In', 'Time Out', 'Status', 'Late', 'Undertime'];

        $rows = AttendanceRecord::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->orderBy('time_in')
            ->get()
            ->map(fn (AttendanceRecord $r) => [
                $r->user?->employee_id,
                $r->user?->displayName(),
                $r->user?->department,
                $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—',
                $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—',
                $r->statusLabel(),
                $r->minutesLabel($r->late_minutes),
                $r->minutesLabel($r->undertime_minutes),
            ]);

        return [$headers, $rows, $title.' — '.$date];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function monthly(string $title, Request $request): array
    {
        $month = $request->input('month', now('Asia/Manila')->format('Y-m'));
        $employeeId = $request->input('employee_id');
        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $headers = ['Date', 'Employee ID', 'Name', 'Time In', 'Time Out', 'Hours', 'Late', 'Undertime', 'Overtime', 'Status'];

        $query = AttendanceRecord::query()
            ->with('user')
            ->whereBetween('attendance_date', [$start, $end]);

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        }

        $rows = $query->orderBy('attendance_date')->orderBy('user_id')->get()->map(fn (AttendanceRecord $r) => [
            $r->attendance_date?->format('Y-m-d'),
            $r->user?->employee_id,
            $r->user?->displayName(),
            $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—',
            $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—',
            $r->totalHoursLabel(),
            $r->minutesLabel($r->late_minutes),
            $r->minutesLabel($r->undertime_minutes),
            $r->minutesLabel($r->overtime_minutes),
            $r->statusLabel(),
        ]);

        return [$headers, $rows, $title.' — '.$month];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function statusReport(string $title, Request $request, string $status): array
    {
        $from = $request->input('date_from', now('Asia/Manila')->startOfMonth()->toDateString());
        $to = $request->input('date_to', now('Asia/Manila')->toDateString());
        $headers = ['Date', 'Employee ID', 'Name', 'Department', 'Time In', 'Time Out', 'Minutes', 'Status'];

        $rows = AttendanceRecord::query()
            ->with('user')
            ->where('status', $status)
            ->whereBetween('attendance_date', [$from, $to])
            ->orderByDesc('attendance_date')
            ->get()
            ->map(function (AttendanceRecord $r) use ($status) {
                $minutes = match ($status) {
                    'late' => $r->late_minutes,
                    'undertime' => $r->undertime_minutes,
                    default => $r->total_minutes,
                };

                return [
                    $r->attendance_date?->format('Y-m-d'),
                    $r->user?->employee_id,
                    $r->user?->displayName(),
                    $r->user?->department,
                    $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—',
                    $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—',
                    $r->minutesLabel((int) $minutes),
                    $r->statusLabel(),
                ];
            });

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function overtime(string $title, Request $request): array
    {
        $from = $request->input('date_from', now('Asia/Manila')->startOfMonth()->toDateString());
        $to = $request->input('date_to', now('Asia/Manila')->toDateString());
        $headers = ['Date', 'Employee ID', 'Name', 'Department', 'Time Out', 'Overtime', 'Status'];

        $rows = AttendanceRecord::query()
            ->with('user')
            ->where('overtime_minutes', '>', 0)
            ->whereBetween('attendance_date', [$from, $to])
            ->orderByDesc('overtime_minutes')
            ->get()
            ->map(fn (AttendanceRecord $r) => [
                $r->attendance_date?->format('Y-m-d'),
                $r->user?->employee_id,
                $r->user?->displayName(),
                $r->user?->department,
                $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—',
                $r->minutesLabel($r->overtime_minutes),
                $r->statusLabel(),
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|int|float|null>>, 2: string}
     */
    protected function department(string $title, Request $request): array
    {
        $date = $request->input('date', now('Asia/Manila')->toDateString());
        $headers = ['Department', 'Present', 'Late', 'Absent', 'On Leave', 'Currently In', 'Timed Out'];

        $departments = User::query()
            ->where('status', 'active')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $rows = $departments->map(function (string $dept) use ($date) {
            $userIds = User::query()->where('department', $dept)->pluck('id');
            $records = AttendanceRecord::query()
                ->whereDate('attendance_date', $date)
                ->whereIn('user_id', $userIds)
                ->get();

            return [
                $dept,
                $records->where('status', 'present')->count(),
                $records->where('status', 'late')->count(),
                $records->where('status', 'absent')->count(),
                $records->whereIn('status', ['on_leave', 'official_business'])->count(),
                $records->filter(fn ($r) => $r->isCurrentlyIn())->count(),
                $records->filter(fn ($r) => $r->time_in && $r->time_out)->count(),
            ];
        });

        return [$headers, $rows, $title.' — '.$date];
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, list<string|int|float|null>>  $rows
     */
    protected function exportCsv(string $title, array $headers, Collection $rows): StreamedResponse
    {
        $filename = $this->slug($title).'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function slug(string $title): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $title) ?: 'attendance-report');
    }
}
