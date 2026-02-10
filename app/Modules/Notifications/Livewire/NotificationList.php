<?php

namespace App\Modules\Notifications\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    const NOTIFICATION_THRESHOLD = 20;

    public function mount(): void
    {
        auth()->user()
            ?->unreadNotifications()
            ->update(['read_at' => now()]);
    }

    public function clearNotifications(): void
    {
        auth()->user()->notifications()->delete();
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
