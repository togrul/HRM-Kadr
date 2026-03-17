<?php

namespace App\Modules\Notifications\Livewire;

use App\Modules\Notifications\Support\NotificationCountCache;
use App\Modules\Notifications\Support\DispatchesNotificationRefresh;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function render()
    {
        $user = auth()->user();

        if (! $user) {
            $notifications = new LengthAwarePaginator(
                collect([]),
                0,
                self::NOTIFICATION_THRESHOLD,
                1,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );

            return view('notification::livewire.notification.notification-list', [
                'notifications' => $notifications,
                'groupedNotifications' => collect([]),
            ]);
        }

        $notifications = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'notifiable_id', 'notifiable_type'])
            ->orderBy('read_at')
            ->latest()
            ->paginate(self::NOTIFICATION_THRESHOLD);

        return view('notification::livewire.notification.notification-list', [
            'notifications' => $notifications,
            'groupedNotifications' => $this->groupedNotifications($notifications->getCollection()),
        ]);
    }
}
