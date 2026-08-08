<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * @param  array<int, string>|string  $roles
     */
    protected function authorizeRoles(array|string $roles): void
    {
        $user = auth()->user();

        if ($user === null || ! $user->hasRole($roles)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function jsonSuccess(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $data), $status);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function jsonError(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }
}
