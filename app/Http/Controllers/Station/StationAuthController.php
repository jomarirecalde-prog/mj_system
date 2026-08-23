<?php

namespace App\Http\Controllers\Station;

use App\Http\Controllers\Controller;
use App\Services\StationDeviceAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class StationAuthController extends Controller
{
    public function __construct(protected StationDeviceAuthorizationService $auth) {}

    public function showLogin(Request $request): RedirectResponse
    {
        if ($this->auth->validateRequest($request) !== null) {
            return redirect()->route('station.scanner');
        }

        return redirect()->to($this->landingStationUrl());
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'station_code' => ['required', 'string', 'max:100'],
            'station_password' => ['required', 'string'],
        ]);

        $throttleKey = 'station-login|'.Str::lower(trim($data['station_code'])).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return redirect()->to($this->landingStationUrl())
                ->withInput($request->only('station_code'))
                ->withErrors([
                    'station_code' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]);
        }

        $result = $this->auth->attemptLogin($data['station_code'], $data['station_password'], $request);

        if (! $result['success']) {
            RateLimiter::hit($throttleKey, 60);

            return redirect()->to($this->landingStationUrl())
                ->withInput($request->only('station_code'))
                ->withErrors(['station_code' => $result['message']]);
        }

        RateLimiter::clear($throttleKey);

        return redirect()
            ->route('station.scanner')
            ->with('success', 'Station authorized. Ready to scan.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()
            ->to(route('login').'#home')
            ->with('success', 'Station signed out.');
    }

    protected function landingStationUrl(): string
    {
        return route('login').'#qr-station';
    }
}
