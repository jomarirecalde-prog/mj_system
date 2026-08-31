<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\QrStation;
use App\Models\QrStationActivityLog;
use App\Services\StationDeviceAuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class QrStationController extends Controller
{
    public function __construct(protected StationDeviceAuthorizationService $deviceAuth)
    {
        $this->middleware(['auth', 'active', 'role:admin']);
    }

    public function index(Request $request): View
    {
        $stations = QrStation::query()
            ->with('authorizedDevice')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('station_name', 'like', $term)
                        ->orWhere('station_code', 'like', $term)
                        ->orWhere('location', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::query()->where('is_active', true)->orderBy('name')->pluck('name');

        $stats = QrStation::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN authorized_device_id IS NOT NULL THEN 1 ELSE 0 END) as authorized
            ")
            ->first();

        return view('admin.qr-stations.index', compact('stations', 'departments', 'stats'));
    }

    public function show(QrStation $qrStation): View
    {
        $qrStation->load(['authorizedDevice', 'creator', 'activityLogs' => fn ($q) => $q->with(['device', 'performer'])->latest()->limit(50)]);

        return view('admin.qr-stations.show', ['station' => $qrStation]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $this->validatedStationData($request);

            $station = DB::transaction(function () use ($data, $request) {
                $station = QrStation::query()->create([
                    ...$data,
                    'station_code' => Str::upper($data['station_code']),
                    'created_by' => $request->user()?->id,
                ]);

                $this->deviceAuth->logActivity(
                    $station,
                    null,
                    'station_created',
                    'QR station created.',
                    $request,
                    $request->user()
                );

                return $station;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return $this->jsonError('Unable to create station. Please try again.', 500);
            }

            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Unable to create station. Please try again.');
        }

        if ($request->expectsJson()) {
            return $this->jsonSuccess([
                'message' => 'Station created successfully.',
                'station' => $station->only(['id', 'station_name', 'station_code']),
            ]);
        }

        return redirect()
            ->route('admin.qr-stations.index')
            ->with('success', 'QR station created successfully.');
    }

    public function update(Request $request, QrStation $qrStation): RedirectResponse|JsonResponse
    {
        $data = $this->validatedStationData($request, $qrStation);

        $qrStation->update([
            ...$data,
            'station_code' => Str::upper($data['station_code']),
        ]);

        $this->deviceAuth->logActivity(
            $qrStation,
            null,
            'station_updated',
            'QR station details updated.',
            $request,
            $request->user()
        );

        if ($request->expectsJson()) {
            return $this->jsonSuccess(['message' => 'Station updated successfully.']);
        }

        return redirect()
            ->route('admin.qr-stations.show', $qrStation)
            ->with('success', 'Station updated successfully.');
    }

    public function activate(QrStation $qrStation, Request $request): RedirectResponse
    {
        $qrStation->update(['status' => 'active']);

        $this->deviceAuth->logActivity($qrStation, null, 'station_activated', 'Station activated.', $request, $request->user());

        return back()->with('success', 'Station activated.');
    }

    public function deactivate(QrStation $qrStation, Request $request): RedirectResponse
    {
        $qrStation->update(['status' => 'inactive']);

        $this->deviceAuth->logActivity($qrStation, null, 'station_deactivated', 'Station deactivated.', $request, $request->user());

        return back()->with('success', 'Station deactivated. Active devices will lose scanner access.');
    }

    public function resetDevice(QrStation $qrStation, Request $request): RedirectResponse
    {
        $this->deviceAuth->resetDevice($qrStation, $request->user(), $request);

        return back()->with('success', 'Device authorization reset. The station is available for a new device.');
    }

    public function revokeDevice(QrStation $qrStation, Request $request): RedirectResponse
    {
        $this->deviceAuth->revokeDevice($qrStation, $request->user(), $request);

        return back()->with('success', 'Device access revoked.');
    }

    public function regeneratePassword(QrStation $qrStation, Request $request): RedirectResponse|JsonResponse
    {
        $plain = StationDeviceAuthorizationService::generatePassword();

        $qrStation->update(['password' => $plain]);

        $this->deviceAuth->logActivity(
            $qrStation,
            null,
            'password_changed',
            'Station password changed by administrator.',
            $request,
            $request->user()
        );

        if ($request->expectsJson()) {
            return $this->jsonSuccess([
                'message' => 'New password generated.',
                'password' => $plain,
            ]);
        }

        return back()->with([
            'success' => 'New station password generated. Copy it now — it will not be shown again.',
            'generated_password' => $plain,
        ]);
    }

    public function destroy(QrStation $qrStation, Request $request): RedirectResponse
    {
        $name = $qrStation->station_name;

        QrStationActivityLog::query()->where('station_id', $qrStation->id)->delete();
        $qrStation->devices()->delete();
        $qrStation->delete();

        return redirect()
            ->route('admin.qr-stations.index')
            ->with('success', "Station \"{$name}\" deleted.");
    }

    public function generatePassword(): JsonResponse
    {
        return $this->jsonSuccess([
            'password' => StationDeviceAuthorizationService::generatePassword(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedStationData(Request $request, ?QrStation $station = null): array
    {
        $stationId = $station?->id;

        $rules = [
            'station_name' => ['required', 'string', 'max:255'],
            'station_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('qr_stations', 'station_code')->ignore($stationId),
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'building' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'floor_area' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'in:active,inactive'],
        ];

        if ($station === null) {
            $rules['password'] = ['required', 'string', 'min:8', 'max:128'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'max:128'];
        }

        $data = $request->validate($rules);

        $payload = [
            'station_name' => $data['station_name'],
            'station_code' => $data['station_code'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'building' => $data['building'] ?? null,
            'department' => $data['department'] ?? null,
            'floor_area' => $data['floor_area'] ?? null,
            'timezone' => $data['timezone'] ?? 'Asia/Manila',
            'status' => $data['status'] ?? ($station?->status ?? 'active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        return $payload;
    }
}
