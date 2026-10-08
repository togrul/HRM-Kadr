<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class NotificationsCounter extends Component
{
    private const NOTIFICATION_THRESHOLD = 10;

    public int|string|null $notificationCount = null;

    /** The exact unread total, for the badge's label (the badge itself caps at "9+"). */
    public int $unreadTotal = 0;

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
            $this->unreadTotal = 0;

            return;
        }

        $count = app(NotificationCountCache::class)->unreadCount((int) $user->id);
        $this->unreadTotal = $count;

        $this->notificationCount = $count > self::NOTIFICATION_THRESHOLD
            ? self::NOTIFICATION_THRESHOLD.'+'
            : $count;
    }

    public function render(): View
    {
        return view('notification::livewire.notification.notifications-counter');
    }
}
