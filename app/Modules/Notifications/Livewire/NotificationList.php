<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use App\Modules\Notifications\Support\DispatchesNotificationRefresh;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;
    use DispatchesNotificationRefresh;

    const NOTIFICATION_THRESHOLD = 20;

    public function mount(): void
    {
        $user = auth()->user();
        $user
            ?->unreadNotifications()
            ->update(['read_at' => now()]);

        if ($user) {
            app(NotificationCountCache::class)->forgetUser((int) $user->id);
            $this->dispatchNotificationRefresh();
        }
    }

    public function clearNotifications(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->notifications()->delete();
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->dispatchNotificationRefresh();
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->with('notifiable')
            ->orderBy('read_at')
            ->latest()
            ->paginate(self::NOTIFICATION_THRESHOLD);

        return view('notification::livewire.notification.notification-list', compact('notifications'));
    }
}
