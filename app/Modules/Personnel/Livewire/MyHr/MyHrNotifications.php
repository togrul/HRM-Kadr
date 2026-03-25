<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Modules\Notifications\Support\DispatchesNotificationRefresh;
use App\Modules\Notifications\Support\NotificationCountCache;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyHrNotifications extends Component
{
    use DispatchesNotificationRefresh;
    use WithPagination;

    public const PER_PAGE = 12;

    public ?int $personnelId = null;

    public function mount(MyHrAccess $access, ?int $personnelId = null): void
    {
        $access->authorize(Auth::user());

        $this->personnelId = $personnelId ?: $access->resolvePersonnelId(Auth::user());

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->dispatchNotificationRefresh();
    }

    public function clearNotifications(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $user->notifications()->delete();
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->dispatchNotificationRefresh();
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        if (! $user) {
            return view('personnel::livewire.personnel.my-hr.notifications', [
                'notifications' => collect(),
                'groupedNotifications' => collect(),
                'summary' => [
                    'total' => 0,
                    'today' => 0,
                    'this_week' => 0,
                    'older' => 0,
                ],
            ]);
        }

        $notifications = $user->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'notifiable_id', 'notifiable_type'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('personnel::livewire.personnel.my-hr.notifications', [
            'notifications' => $notifications,
            'groupedNotifications' => $this->groupedNotifications($notifications->getCollection()),
            'summary' => $this->summary($user),
        ]);
    }

    protected function summary($user): array
    {
        $baseQuery = $user->notifications();

        return [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'older' => (clone $baseQuery)->where('created_at', '<', now()->startOfWeek())->count(),
        ];
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

    protected function groupLabel(string $key): string
    {
        return match ($key) {
            'today' => __('notifications::common.groups.today'),
            'yesterday' => __('notifications::common.groups.yesterday'),
            'this_week' => __('notifications::common.groups.this_week'),
            default => __('notifications::common.groups.older'),
        };
    }
}
