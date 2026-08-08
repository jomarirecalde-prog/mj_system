<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * @param  array<string, mixed>|object|null  $previous
     * @param  array<string, mixed>|object|null  $new
     */
    public function log(
        string $action,
        string $module,
        ?string $itemType = null,
        ?int $itemId = null,
        mixed $previous = null,
        mixed $new = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'previous_value' => $this->encodeValue($previous),
            'new_value' => $this->encodeValue($new),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    private function encodeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
