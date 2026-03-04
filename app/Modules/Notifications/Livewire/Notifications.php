<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use App\Modules\Notifications\Support\DispatchesNotificationRefresh;
use Illuminate\Http\Response;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class Notifications extends Component
{
    use DispatchesNotificationRefresh;

    private const NOTIFICATION_THRESHOLD = 10;

    public $notifications;

    public bool $isLoading;

    public bool $hasLoaded;

    public int|string|null $notificationCount = null;

    public function mount()
    {
        $this->notifications = collect([]);
        $this->isLoading = true;
        $this->hasLoaded = false;
        $this->refreshCount();
    }

    public function getNotifications(): void
    {
        $this->isLoading = true;

        $user = auth()->user();
        if (! $user) {
            $this->notifications = collect([]);
            $this->isLoading = false;
            $this->hasLoaded = true;

            return;
        }

        $this->notifications = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'notifiable_id', 'notifiable_type'])
            ->orderBy('read_at')
            ->latest()
            ->take(self::NOTIFICATION_THRESHOLD)
            ->get();

        $this->isLoading = false;
        $this->hasLoaded = true;
        $this->refreshCount();
        $this->dispatchNotificationRefresh();
    }

    public function markAllAsRead()
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $user = auth()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        app(NotificationCountCache::class)->forgetUser((int) $user->id);

        $this->refreshCount();
        $this->dispatchNotificationRefresh();
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
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->refreshCount();
        $this->dispatchNotificationRefresh();

        return $this->redirectRoute($route);
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

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.notifications-nav');
    }

    public function render()
    {
        return view('notification::livewire.notification.notifications');
    }
}
