<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Modules\Notifications\Support\DispatchesNotificationRefresh;
use App\Modules\Notifications\Support\NotificationCountCache;
use App\Modules\Personnel\Livewire\MyHr\Concerns\ResolvesOwnPersonnel;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use App\Notifications\NewLeaveRequested;
use App\Notifications\NewPersonnelAdded;
use App\Notifications\PersonnelWasDeleted;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyHrNotifications extends Component
{
    use DispatchesNotificationRefresh;
    use ResolvesOwnPersonnel;
    use WithPagination;

    public const PER_PAGE = 12;

    /**
     * HR-operator alerts belong to the main notification centre, not the employee's
     * cabinet — the cabinet neither lists nor clears them.
     */
    public const OPERATOR_TYPES = [
        NewLeaveRequested::class,
        NewPersonnelAdded::class,
        PersonnelWasDeleted::class,
    ];

    public function mount(MyHrAccess $access, ?int $personnelId = null): void
    {
        $access->authorize(Auth::user());

        $this->bindOwnPersonnel($personnelId ?: null, requireLink: false);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->cabinetNotifications($user)->whereNull('read_at')->update(['read_at' => now()]);
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->dispatchNotificationRefresh();
    }

    public function clearNotifications(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->cabinetNotifications($user)->delete();
        app(NotificationCountCache::class)->forgetUser((int) $user->id);
        $this->dispatchNotificationRefresh();
        $this->resetPage();
    }

    public function render(): View
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

        $notifications = $this->cabinetNotifications($user)
            ->select(['id', 'type', 'data', 'read_at', 'created_at', 'notifiable_id', 'notifiable_type'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('personnel::livewire.personnel.my-hr.notifications', [
            'notifications' => $notifications,
            'groupedNotifications' => $this->groupedNotifications($notifications->getCollection()),
            'summary' => $this->summary($user),
        ]);
    }

    protected function cabinetNotifications($user): MorphMany
    {
        return $user->notifications()->whereNotIn('type', self::OPERATOR_TYPES);
    }

    protected function summary($user): array
    {
        $baseQuery = $this->cabinetNotifications($user);

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
