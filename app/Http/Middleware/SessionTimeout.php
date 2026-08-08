<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) SystemSetting::get('session_timeout', 120);

        if ($timeoutMinutes <= 0) {
            $request->session()->put('last_activity_at', now('Asia/Manila')->timestamp);

            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity_at');
        $now = now('Asia/Manila')->timestamp;

        if ($lastActivity !== null && ($now - (int) $lastActivity) > ($timeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401, 'Session expired due to inactivity.');
            }

            return redirect()->route('login')->with('error', 'Your session expired due to inactivity. Please sign in again.');
        }

        $request->session()->put('last_activity_at', $now);

        return $next($request);
    }
}
