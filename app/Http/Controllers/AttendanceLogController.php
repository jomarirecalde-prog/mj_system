<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceQrScanLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceLogController extends Controller
{
    public function scanLogs(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $logs = AttendanceQrScanLog::query()
            ->with(['employee', 'scanner'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('qr_code', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($eq) use ($search) {
                            $eq->where('full_name', 'like', "%{$search}%")
                                ->orWhere('employee_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->input('result'), fn ($q, $result) => $q->where('result', $result))
            ->when($request->input('date'), fn ($q, $date) => $q->whereDate('scan_date', $date))
            ->latest('id')
            ->paginate(50);

        return view('attendance.scan-logs', compact('logs'));
    }

    public function auditLogs(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $logs = AttendanceLog::query()
            ->with(['employee', 'performer'])
            ->when($request->input('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->input('search'), function ($q, $search) {
                $q->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->latest('logged_at')
            ->paginate(50);

        return view('attendance.audit-logs', compact('logs'));
    }
}
