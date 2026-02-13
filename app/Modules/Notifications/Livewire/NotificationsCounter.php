<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class NotificationsCounter extends Component
{
    private const NOTIFICATION_THRESHOLD = 10;

    public int|string|null $notificationCount = null;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('notifications-refresh-count')]
    public function refreshCount(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->notificationCount = null;

            return;
        }

        $count = app(NotificationCountCache::class)->unreadCount((int) $user->id);

        $this->notificationCount = $count > self::NOTIFICATION_THRESHOLD
            ? self::NOTIFICATION_THRESHOLD.'+'
            : $count;
    }

    public function render()
    {
        return view('notification::livewire.notification.notifications-counter');
    }
}
