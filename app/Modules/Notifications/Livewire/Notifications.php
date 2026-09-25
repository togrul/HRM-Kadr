<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read EloquentCollection<int, DatabaseNotification> $dropdownNotifications
 */
#[Isolate]
class Notifications extends Component
{
    private const NOTIFICATION_THRESHOLD = 10;

    public bool $isLoading = true;

    public bool $hasLoaded = false;

    public int|string|null $notificationCount = null;

    public function mount(): void
    {
        $this->refreshCount();
    }

    /**
     * Opening the dropdown only flips the flag: the list itself is a computed read, so
     * the snapshot never carries an Eloquent collection that Livewire would re-query
     * (every column) on each later round trip.
     */
    public function getNotifications(): void
    {
        $this->isLoading = false;
        $this->hasLoaded = true;
        unset($this->dropdownNotifications);

        if (! auth()->user()) {
            return;
        }

        $this->refreshCount();
        $this->refreshOtherCounters();
    }

    /**
     * The newest notifications for the open dropdown, read with just the columns the
     * item view uses. Nothing is read until the dropdown has been opened once.
     *
     * @return EloquentCollection<int, DatabaseNotification>
     */
    #[Computed]
    public function dropdownNotifications(): EloquentCollection
    {
        $user = auth()->user();

        if (! $this->hasLoaded || ! $user) {
            return new EloquentCollection;
        }

        return $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'notifiable_id', 'notifiable_type'])
            ->orderBy('read_at')
            ->latest()
            ->take(self::NOTIFICATION_THRESHOLD)
            ->get();
    }

    public function markAllAsRead(): void
    {
        auth()->guest() && abort(Response::HTTP_FORBIDDEN);

        $user = auth()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        app(NotificationCountCache::class)->forgetUser((int) $user->id);

        // Refreshes the badge, the other counters and the list in this same response.
        $this->getNotifications();
    }

    public function markAsRead($notificationId): void
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
        $this->refreshOtherCounters();

        $this->redirectRoute($route);
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

    /**
     * This component has already refreshed its own badge in the same response, so the
     * refresh event goes to the standalone counter only; a page-wide dispatch would
     * bounce straight back here as a second round trip.
     */
    private function refreshOtherCounters(): void
    {
        $this->dispatch('notifications-refresh-count')->to(NotificationsCounter::class);
    }

    public function placeholder(): View
    {
        return view('notification::livewire.notification.placeholders.notifications-nav');
    }

    protected function groupLabel(string $key): string
    {
        return match ($key) {
            'today' => __('notifications::common.groups.today'),
            'yesterday' => __('notifications::common.groups.yesterday'),
            'this_week' => __('notifications::common.groups.this_week'),
            default => __('notifications::common.groups.older'),
        };
    }

    protected function groupedNotifications(Collection $notifications): Collection
    {
        return $notifications
            ->groupBy(function ($notification) {
                $createdAt = $notification->created_at;

                if ($createdAt?->isToday()) {
                    return 'today';
                }

                if ($createdAt?->isYesterday()) {
                    return 'yesterday';
                }

                if ($createdAt?->greaterThanOrEqualTo(now()->startOfWeek())) {
                    return 'this_week';
                }

                return 'older';
            })
            ->map(fn (Collection $items, string $key) => [
                'key' => $key,
                'label' => $this->groupLabel($key),
                'items' => $items,
            ])
            ->values();
    }

    public function render(): View
    {
        return view('notification::livewire.notification.notifications', [
            'groupedNotifications' => $this->groupedNotifications($this->dropdownNotifications),
        ]);
    }
}
