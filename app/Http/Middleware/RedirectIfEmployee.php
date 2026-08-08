<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfEmployee
{
    /**
     * Block employee-role users from admin/staff/inventory routes.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isEmployee()) {
            if ($request->expectsJson() || $request->ajax()) {
                abort(403, 'Employees may only access the Employee Portal.');
            }

            return redirect()->route('employee.dashboard');
        }

        return $next($request);
    }
}
