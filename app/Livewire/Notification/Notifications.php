<?php

namespace App\Livewire\Notification;

use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;
use Livewire\Component;

class Notifications extends Component
{
    const NOTIFICATION_TRESHOLD = 10;
    public $notifications;
    public $notificationCount;
    public bool $isLoading;

    public function mount()
    {
        $this->notifications = collect([]);
        $this->isLoading = true;
        $this->getNotificationCount();
    }

    public function getNotificationCount(): void
    {
        $this->notificationCount = auth()->user()->unreadNotifications()->count();

        if($this->notificationCount > self::NOTIFICATION_TRESHOLD) {
            $this->notificationCount = self::NOTIFICATION_TRESHOLD.'+';
        }
    }

    #[On('getNotifications')]
    public function getNotifications(): void
    {
        $this->notifications = auth()->user()
            ->notifications()
            ->orderBy('read_at')
            ->latest()
            ->take(self::NOTIFICATION_TRESHOLD)
            ->get();

        $this->isLoading = false;
    }

    public function markAllAsRead()
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        auth()->user()->unreadNotifications->markAsRead();

        $this->getNotificationCount();
        $this->getNotifications();
    }

    public function markAsRead($notificationId)
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $notification = DatabaseNotification::findOrFail($notificationId);
        $route = match($notification->data['type']) {
            'Personnel', 'Birthday' => 'home',
            'default' => 'notifications',
        };

        $notification->markAsRead();
        return redirect()->route($route);
    }

    public function render()
    {
        return view('livewire.notification.notifications');
    }
}
