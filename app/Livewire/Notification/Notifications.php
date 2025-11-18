<?php

namespace App\Livewire\Notification;

use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
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
        $user = auth()->user();
        $cacheKey = $this->cacheKey('count', $user->id);

        $this->notificationCount = Cache::remember($cacheKey, 60, fn () => $user->unreadNotifications()->count());

        if($this->notificationCount > self::NOTIFICATION_TRESHOLD) {
            $this->notificationCount = self::NOTIFICATION_TRESHOLD.'+';
        }
    }

    #[On('getNotifications')]
    public function getNotifications(): void
    {
        $user = auth()->user();
        $cacheKey = $this->cacheKey('list', $user->id);

        $this->notifications = Cache::remember($cacheKey, 60, fn () => $user->notifications()
            ->orderBy('read_at')
            ->latest()
            ->take(self::NOTIFICATION_TRESHOLD)
            ->get());

        $this->isLoading = false;
    }

    public function markAllAsRead()
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        $this->flushCache($user->id);

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
        $this->flushCache($notification->notifiable_id);
        return redirect()->route($route);
    }

    public function render()
    {
        return view('livewire.notification.notifications');
    }

    private function cacheKey(string $type, int $userId): string
    {
        return "notifications:{$type}:{$userId}";
    }

    private function flushCache(int $userId): void
    {
        Cache::forget($this->cacheKey('count', $userId));
        Cache::forget($this->cacheKey('list', $userId));
    }
}
