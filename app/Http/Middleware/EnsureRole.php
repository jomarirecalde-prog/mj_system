<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Authentication required.');
        }

        if ($roles === []) {
            return $next($request);
        }

        $normalized = [];
        foreach ($roles as $role) {
            foreach (preg_split('/[|,]/', $role, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $normalized[] = trim($part);
            }
        }

        if (! $user->hasRole($normalized)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
