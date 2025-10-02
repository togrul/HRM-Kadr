<?php

namespace App\Livewire\Notification;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    const NOTIFICATION_THRESHOLD = 20;

    public function mount()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function clearNotifications(): void
    {
        auth()->user()->notifications()->delete();
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('read_at')
            ->latest()
            ->paginate(self::NOTIFICATION_THRESHOLD);

        return view('livewire.notification.notification-list', compact('notifications'));
    }
}
