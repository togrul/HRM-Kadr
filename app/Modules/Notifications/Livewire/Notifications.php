<?php

namespace App\Modules\Notifications\Livewire;

use Illuminate\Http\Response;
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

        if ($this->notificationCount > self::NOTIFICATION_TRESHOLD) {
            $this->notificationCount = self::NOTIFICATION_TRESHOLD.'+';
        }
    }

    #[On('getNotifications')]
    public function getNotifications(): void
    {
        $user = auth()->user();
        $cacheKey = $this->cacheKey('list', $user->id);

        $this->notifications = Cache::remember($cacheKey, 60, fn () => $user->notifications()
            ->with('notifiable')
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
        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->flushCache($user->id);

        $this->getNotificationCount();
        $this->getNotifications();
    }

    public function markAsRead($notificationId)
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $user = auth()->user();
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $type = $notification->data['type'] ?? 'default';

        $route = match ($type) {
            'Personnel', 'Birthday' => 'home',
            'Leave', 'leave' => 'leaves',
            default => 'notifications',
        };

        $notification->markAsRead();
        $this->flushCache($user->id);

        return $this->redirectRoute($route);
    }

    public function render()
    {
        return view('notification::livewire.notification.notifications');
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
