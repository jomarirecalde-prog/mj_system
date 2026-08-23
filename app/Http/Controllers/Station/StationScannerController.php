<?php

namespace App\Http\Controllers\Station;

use App\Http\Controllers\Controller;
use App\Models\QrStation;
use App\Models\QrStationDevice;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StationScannerController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        /** @var QrStation $station */
        $station = $request->attributes->get('station');
        /** @var QrStationDevice $device */
        $device = $request->attributes->get('station_device');

        return view('station.scanner', compact('station', 'device'));
    }

    public function scan(Request $request): JsonResponse
    {
        /** @var QrStation $station */
        $station = $request->attributes->get('station');
        /** @var QrStationDevice $device */
        $device = $request->attributes->get('station_device');

        $request->validate([
            'qr_payload' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->attendance->processStationScan(
            (string) $request->input('qr_payload'),
            $station,
            $device,
            $request
        );

        $status = $result['success'] ? 200 : 422;

        return response()->json($result, $status);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        /** @var QrStation $station */
        $station = $request->attributes->get('station');

        return response()->json([
            'success' => true,
            'connected' => true,
            'station' => $station->station_name,
            'time' => now($station->timezone ?? 'Asia/Manila')->format('M j, Y g:i:s A'),
        ]);
    }
}
