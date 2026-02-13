<?php

namespace App\Modules\Notifications\Support;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;

class NotificationCountCache
{
    public function unreadCount(int $userId): int
    {
        return (int) Cache::remember(
            $this->key($userId),
            now()->addMinutes(2),
            fn () => DatabaseNotification::query()
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $userId)
                ->whereNull('read_at')
                ->count()
        );
    }

    public function forgetByNotifiable(?string $notifiableType, $notifiableId): void
    {
        if ($notifiableType !== User::class || empty($notifiableId)) {
            return;
        }

        Cache::forget($this->key((int) $notifiableId));
    }

    public function forgetUser(int $userId): void
    {
        Cache::forget($this->key($userId));
    }

    protected function key(int $userId): string
    {
        return "notifications:count:user:{$userId}";
    }
}
