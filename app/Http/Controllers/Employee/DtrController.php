<?php

namespace App\Http\Controllers\Employee;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\EmployeePortalService;
use App\Support\EmployeeAttendancePresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DtrController extends Controller
{
    public function __construct(protected EmployeePortalService $portal) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $month = $request->input('month', now('Asia/Manila')->format('Y-m'));
        $data = $this->portal->monthlyDtr($user, $month);
        $schedule = $user->activeSchedule;

        return view('employee.dtr', [
            'user' => $user,
            'month' => $month,
            'records' => $data['records'],
            'totals' => $data['totals'],
            'schedule' => $schedule,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|Response|View
    {
        $user = $request->user();
        $month = $request->input('month', now('Asia/Manila')->format('Y-m'));
        $format = $request->input('format', 'pdf');
        $data = $this->portal->monthlyDtr($user, $month);

        $headers = ['Date', 'Day', 'Time In', 'Time Out', 'Late', 'Undertime', 'Overtime', 'Status'];
        $rows = $data['records']->map(function ($row) {
            /** @var AttendanceRecord|null $record */
            $record = $row->record;

            return [
                $row->date->format('M d, Y'),
                $row->date->format('D'),
                $record?->time_in ? ph_datetime($record->time_in, 'h:i A') : '—',
                $record?->time_out ? ph_datetime($record->time_out, 'h:i A') : '—',
                $record ? $record->minutesLabel($record->late_minutes) : '—',
                $record ? $record->minutesLabel($record->undertime_minutes) : '—',
                $record ? $record->minutesLabel($record->overtime_minutes) : '—',
                $record
                    ? EmployeeAttendancePresenter::displayStatus($record, $row->date)
                    : ucfirst(str_replace('_', ' ', (string) $row->status)),
            ];
        });

        $title = 'My DTR — '.$user->displayName().' — '.$month;

        return match ($format) {
            'excel' => Excel::download(new AttendanceExport($headers, $rows), 'my-dtr-'.$month.'.xlsx'),
            'print' => view('employee.dtr-print', [
                'user' => $user,
                'month' => $month,
                'headers' => $headers,
                'rows' => $rows,
                'totals' => $data['totals'],
                'title' => $title,
            ]),
            default => Pdf::loadView('employee.dtr-pdf', [
                'user' => $user,
                'month' => $month,
                'headers' => $headers,
                'rows' => $rows,
                'totals' => $data['totals'],
                'title' => $title,
            ])->download('my-dtr-'.$month.'.pdf'),
        };
    }
}
