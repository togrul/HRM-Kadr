<?php

namespace App\Modules\Notifications\Support;

trait DispatchesNotificationRefresh
{
    protected function dispatchNotificationRefresh(): void
    {
        $this->dispatch('notifications-refresh-count');
    }
}
