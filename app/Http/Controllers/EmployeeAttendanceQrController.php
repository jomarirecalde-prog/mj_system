<?php

namespace App\Http\Controllers;

use App\Models\EmployeeQrCode;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AuditService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeAttendanceQrController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected QrCodeService $qrCodeService,
        protected AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin']);

        $employees = User::query()
            ->with('activeQrCode')
            ->whereNotNull('employee_id')
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(30);

        return view('attendance.qr.index', compact('employees'));
    }

    public function show(User $user): View
    {
        $this->authorizeRoles(['admin']);

        $user->load(['activeQrCode', 'qrCodes' => fn ($q) => $q->latest()]);
        $qr = $user->activeQrCode;
        $qrImage = null;
        $qrMime = null;

        if ($qr) {
            $format = extension_loaded('imagick') ? 'png' : 'svg';
            $qrImage = base64_encode($this->qrCodeService->generateImage($qr->code, $format, 280));
            $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';
        }

        return view('attendance.qr.show', compact('user', 'qr', 'qrImage', 'qrMime'));
    }

    public function generate(User $user): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $this->attendance->generateQrCode($user, auth()->user());

        return redirect()->route('attendance.qr.show', $user)->with('success', 'Employee QR code generated.');
    }

    public function regenerate(User $user): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $this->attendance->generateQrCode($user, auth()->user());

        return redirect()->route('attendance.qr.show', $user)->with('success', 'Employee QR code regenerated. Old code disabled.');
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        $this->authorizeRoles(['admin']);

        $qr = $user->activeQrCode;
        if ($qr) {
            $qr->update([
                'status' => 'disabled',
                'disabled_at' => now('Asia/Manila'),
                'disabled_by' => auth()->id(),
                'disable_reason' => $request->input('reason', 'Disabled by administrator'),
            ]);
            $this->audit->log('disable_employee_qr', 'attendance', EmployeeQrCode::class, $qr->id, null, $qr->toArray());
        }

        return redirect()->route('attendance.qr.show', $user)->with('success', 'QR code disabled.');
    }

    public function download(User $user, Request $request): StreamedResponse
    {
        $this->authorizeRoles(['admin']);

        $qr = $user->activeQrCode;
        abort_if($qr === null, 404, 'No active QR code.');

        $format = $request->input('format', 'svg');

        return $this->qrCodeService->download(
            $qr->code,
            ($user->employee_id ?: 'employee').'-qr.'.$format,
            $format,
        );
    }

    public function print(User $user): View
    {
        $this->authorizeRoles(['admin']);

        $qr = $user->activeQrCode;
        abort_if($qr === null, 404, 'No active QR code.');

        $format = extension_loaded('imagick') ? 'png' : 'svg';
        $qrImage = base64_encode($this->qrCodeService->generateImage($qr->code, $format, 220));
        $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';

        return view('attendance.qr.print', compact('user', 'qr', 'qrImage', 'qrMime'));
    }
}
