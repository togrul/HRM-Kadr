<?php

namespace App\Modules\Notifications\Observers;

use App\Modules\Notifications\Support\NotificationCountCache;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationObserver
{
    public function created(DatabaseNotification $notification): void
    {
        $this->forget($notification);
    }

    public function updated(DatabaseNotification $notification): void
    {
        $this->forget($notification);
    }

    public function deleted(DatabaseNotification $notification): void
    {
        $this->forget($notification);
    }

    protected function forget(DatabaseNotification $notification): void
    {
        app(NotificationCountCache::class)->forgetByNotifiable(
            $notification->notifiable_type,
            $notification->notifiable_id
        );
    }
}

