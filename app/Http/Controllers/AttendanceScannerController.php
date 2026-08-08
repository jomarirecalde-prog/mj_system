<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceScannerController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('attendance.scanner');
    }

    public function punch(Request $request): JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $request->validate([
            'qr_payload' => ['required', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->attendance->processScan(
            (string) $request->input('qr_payload'),
            $request->user(),
            $request
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }
}
