<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use Illuminate\Http\Response;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
#[Isolate]
class Notifications extends Component
{
    private const NOTIFICATION_THRESHOLD = 10;

    public $notifications;

    public bool $isLoading;

    public function mount()
    {
        $this->notifications = collect([]);
        $this->isLoading = true;
    }

    public function getNotifications(): void
    {
        $user = auth()->user();
        $this->notifications = $user->notifications()
            ->with('notifiable')
            ->orderBy('read_at')
            ->latest()
            ->take(self::NOTIFICATION_THRESHOLD)
            ->get();

        $this->isLoading = false;
        $this->dispatch('notifications-refresh-count');
    }

    public function markAllAsRead()
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $user = auth()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        app(NotificationCountCache::class)->forgetUser((int) $user->id);

        $this->dispatch('notifications-refresh-count');
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
        $this->dispatch('notifications-refresh-count');

        return $this->redirectRoute($route);
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
