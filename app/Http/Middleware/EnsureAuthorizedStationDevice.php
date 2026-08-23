<?php

namespace App\Http\Middleware;

use App\Services\StationDeviceAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthorizedStationDevice
{
    public function __construct(protected StationDeviceAuthorizationService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->auth->validateRequest($request);

        if ($context === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'unauthorized',
                    'title' => 'SESSION EXPIRED',
                    'message' => 'This station has been disconnected by the administrator.',
                ], 401);
            }

            return redirect()
                ->to(route('login').'#qr-station')
                ->with('error', 'This station has been disconnected by the administrator.');
        }

        $request->attributes->set('station', $context['station']);
        $request->attributes->set('station_device', $context['device']);

        return $next($request);
    }
}
