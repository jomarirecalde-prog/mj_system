<?php

namespace App\Services;

use App\Models\QrStation;
use App\Models\QrStationActivityLog;
use App\Models\QrStationDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StationDeviceAuthorizationService
{
    public const COOKIE_DEVICE_ID = 'station_device_identifier';

    public const COOKIE_DEVICE_TOKEN = 'station_device_token';

    public const SESSION_STATION_ID = 'station_auth.station_id';

    public const SESSION_DEVICE_ID = 'station_auth.device_id';

    /**
     * @return array{success: bool, message: string, station?: QrStation, device?: QrStationDevice, token?: string}
     */
    public function attemptLogin(string $stationCode, string $password, Request $request): array
    {
        $code = trim($stationCode);
        $station = QrStation::query()
            ->where('station_code', Str::upper($code))
            ->first();

        if ($station === null || ! Hash::check($password, $station->password)) {
            return [
                'success' => false,
                'message' => 'Invalid Station ID or password.',
            ];
        }

        if (! $station->isActive()) {
            return [
                'success' => false,
                'message' => 'This station is currently inactive. Please contact the administrator.',
            ];
        }

        $cookieIdentifier = (string) $request->cookie(self::COOKIE_DEVICE_ID, '');
        $cookieToken = (string) $request->cookie(self::COOKIE_DEVICE_TOKEN, '');

        if ($station->hasAuthorizedDevice()) {
            $authorized = QrStationDevice::query()->find($station->authorized_device_id);

            if ($authorized !== null && $authorized->isAuthorized()) {
                if ($this->validateDeviceCredentials($authorized, $cookieIdentifier, $cookieToken)) {
                    $this->establishSession($request, $station, $authorized);

                    $this->touchDevice($authorized, $request);
                    $this->logActivity($station, $authorized, 'device_login', 'Authorized device re-authenticated.', $request);

                    return [
                        'success' => true,
                        'message' => 'Welcome back.',
                        'station' => $station,
                        'device' => $authorized,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'This QR Station is already authorized on another device. Please contact the Superadmin to reset or transfer the station.',
                ];
            }
        }

        return DB::transaction(function () use ($station, $request) {
            $station->refresh();

            if ($station->hasAuthorizedDevice()) {
                $authorized = QrStationDevice::query()->find($station->authorized_device_id);
                if ($authorized !== null && $authorized->isAuthorized()) {
                    return [
                        'success' => false,
                        'message' => 'This QR Station is already authorized on another device. Please contact the Superadmin to reset or transfer the station.',
                    ];
                }
            }

            [$device, $plainToken] = $this->bindDevice($station, $request);
            $this->establishSession($request, $station, $device, $plainToken);
            $this->logActivity($station, $device, 'device_authorized', 'Station bound to new device.', $request);

            return [
                'success' => true,
                'message' => 'Device authorized successfully.',
                'station' => $station,
                'device' => $device,
                'token' => $plainToken,
            ];
        });
    }

    /**
     * @return array{station: QrStation, device: QrStationDevice}|null
     */
    public function validateRequest(Request $request): ?array
    {
        $stationId = $request->session()->get(self::SESSION_STATION_ID);
        $deviceId = $request->session()->get(self::SESSION_DEVICE_ID);

        if (! $stationId || ! $deviceId) {
            return null;
        }

        $station = QrStation::query()->find($stationId);
        $device = QrStationDevice::query()->find($deviceId);

        if ($station === null || $device === null) {
            $this->clearStationAuth($request);

            return null;
        }

        if (! $station->isActive()) {
            $this->clearStationAuth($request);

            return null;
        }

        if ((int) $station->authorized_device_id !== (int) $device->id || ! $device->isAuthorized()) {
            $this->clearStationAuth($request);

            return null;
        }

        $cookieIdentifier = (string) $request->cookie(self::COOKIE_DEVICE_ID, '');
        $cookieToken = (string) $request->cookie(self::COOKIE_DEVICE_TOKEN, '');

        if (! $this->validateDeviceCredentials($device, $cookieIdentifier, $cookieToken)) {
            $this->clearStationAuth($request);

            return null;
        }

        $this->touchDevice($device, $request);
        $station->forceFill(['last_activity_at' => now()])->save();

        return [
            'station' => $station,
            'device' => $device,
        ];
    }

    public function logout(Request $request): void
    {
        $context = $this->validateRequest($request);

        if ($context !== null) {
            $this->logActivity(
                $context['station'],
                $context['device'],
                'device_logout',
                'Station signed out from device.',
                $request
            );
        }

        $this->clearSession($request);
    }

    public function resetDevice(QrStation $station, User $admin, Request $request): void
    {
        DB::transaction(function () use ($station, $admin, $request) {
            $previousDevice = $station->authorizedDevice;
            $previousLabel = $previousDevice?->displayName() ?? 'None';

            if ($previousDevice !== null) {
                $previousDevice->forceFill([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                ])->save();
            }

            $station->forceFill([
                'authorized_device_id' => null,
                'authorized_at' => null,
            ])->save();

            $this->logActivity(
                $station,
                $previousDevice,
                'device_reset',
                'Station device reset by administrator. Previous device: '.$previousLabel,
                $request,
                $admin
            );
        });
    }

    public function revokeDevice(QrStation $station, User $admin, Request $request): void
    {
        $this->resetDevice($station, $admin, $request);

        $this->logActivity(
            $station,
            null,
            'device_revoked',
            'Device access revoked by administrator.',
            $request,
            $admin
        );
    }

    /**
     * @return array{0: QrStationDevice, 1: string}
     */
    protected function bindDevice(QrStation $station, Request $request): array
    {
        $plainToken = Str::random(64);
        $identifier = (string) Str::uuid();
        $parsed = $this->parseUserAgent($request);

        $device = QrStationDevice::query()->create([
            'station_id' => $station->id,
            'device_identifier' => $identifier,
            'device_token_hash' => $this->hashToken($plainToken),
            'device_name' => $parsed['device_name'],
            'browser' => $parsed['browser'],
            'operating_system' => $parsed['os'],
            'ip_address' => $request->ip(),
            'status' => 'authorized',
            'authorized_at' => now(),
            'last_activity_at' => now(),
        ]);

        $station->forceFill([
            'authorized_device_id' => $device->id,
            'authorized_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        return [$device, $plainToken];
    }

    protected function establishSession(Request $request, QrStation $station, QrStationDevice $device, ?string $plainToken = null): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_STATION_ID, $station->id);
        $request->session()->put(self::SESSION_DEVICE_ID, $device->id);

        if ($plainToken !== null) {
            foreach ($this->makeDeviceCookies($device->device_identifier, $plainToken) as $cookie) {
                Cookie::queue($cookie);
            }
        }
    }

    public function makeDeviceCookies(string $identifier, string $plainToken): array
    {
        $minutes = 60 * 24 * 365; // 1 year
        $secure = request()->isSecure();

        return [
            cookie(self::COOKIE_DEVICE_ID, $identifier, $minutes, '/', null, $secure, true, false, 'Lax'),
            cookie(self::COOKIE_DEVICE_TOKEN, $plainToken, $minutes, '/', null, $secure, true, false, 'Lax'),
        ];
    }

    public function clearStationAuth(Request $request): void
    {
        $request->session()->forget([self::SESSION_STATION_ID, self::SESSION_DEVICE_ID]);
        Cookie::queue(Cookie::forget(self::COOKIE_DEVICE_ID));
        Cookie::queue(Cookie::forget(self::COOKIE_DEVICE_TOKEN));
    }

    public function clearSession(Request $request): void
    {
        $this->clearStationAuth($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    protected function validateDeviceCredentials(QrStationDevice $device, string $identifier, string $token): bool
    {
        if ($identifier === '' || $token === '') {
            return false;
        }

        if (! hash_equals($device->device_identifier, $identifier)) {
            return false;
        }

        return hash_equals($device->device_token_hash, $this->hashToken($token));
    }

    protected function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    protected function touchDevice(QrStationDevice $device, Request $request): void
    {
        $device->forceFill([
            'last_activity_at' => now(),
            'ip_address' => $request->ip(),
        ])->save();
    }

    public function logActivity(
        QrStation $station,
        ?QrStationDevice $device,
        string $action,
        ?string $description,
        Request $request,
        ?User $performer = null,
    ): void {
        QrStationActivityLog::query()->create([
            'station_id' => $station->id,
            'device_id' => $device?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'performed_by' => $performer?->id,
        ]);
    }

    /**
     * @return array{device_name: string, browser: string, os: string}
     */
    public function parseUserAgent(Request $request): array
    {
        $ua = (string) $request->userAgent();
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        if (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        if (preg_match('/Edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua)) {
            $browser = 'Safari';
        }

        $deviceName = $browser.' on '.$os;

        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            $deviceName = 'Mobile · '.$deviceName;
        }

        return [
            'device_name' => $deviceName,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    public static function generatePassword(int $length = 16): string
    {
        return Str::password($length, letters: true, numbers: true, symbols: true);
    }
}
