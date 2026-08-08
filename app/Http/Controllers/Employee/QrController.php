<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrController extends Controller
{
    public function __construct(protected QrCodeService $qrCodeService) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $qr = $user->activeQrCode;
        $qrImage = null;
        $qrMime = null;

        if ($qr) {
            $format = extension_loaded('imagick') ? 'png' : 'svg';
            $qrImage = base64_encode($this->qrCodeService->generateImage($qr->code, $format, 280));
            $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';
        }

        return view('employee.qr', compact('user', 'qr', 'qrImage', 'qrMime'));
    }

    public function download(Request $request): StreamedResponse
    {
        $user = $request->user();
        $qr = $user->activeQrCode;
        abort_if($qr === null, 404, 'No active QR code.');

        $format = $request->input('format', 'svg');

        return $this->qrCodeService->download(
            $qr->code,
            ($user->employee_id ?: 'employee').'-qr.'.$format,
            $format,
        );
    }

    public function print(Request $request): View
    {
        $user = $request->user();
        $qr = $user->activeQrCode;
        abort_if($qr === null, 404, 'No active QR code.');

        $format = extension_loaded('imagick') ? 'png' : 'svg';
        $qrImage = base64_encode($this->qrCodeService->generateImage($qr->code, $format, 320));
        $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';

        return view('employee.qr-print', compact('user', 'qr', 'qrImage', 'qrMime'));
    }
}
