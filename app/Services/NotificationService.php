<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notify(
        ?int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): AppNotification {
        return AppNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        User::query()
            ->where('role', 'admin')
            ->where('status', 'active')
            ->pluck('id')
            ->each(fn (int $id) => $this->notify($id, $type, $title, $message, $link));
    }

    public function notifyRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        User::query()
            ->where('role', $role)
            ->where('status', 'active')
            ->pluck('id')
            ->each(fn (int $id) => $this->notify($id, $type, $title, $message, $link));
    }
}
