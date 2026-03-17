<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AnalyticsPanel extends Component
{
    use InteractsWithNotificationAuthorization;

    public string $range = '30d';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    #[On('notification-campaign-changed')]
    public function refreshAnalytics(): void {}

    public function updatedRange(): void
    {
        if ($this->range !== 'custom') {
            $this->dateFrom = '';
            $this->dateTo = '';
        }
    }

    protected function dispatchQuery()
    {
        return $this->applyDateRange(NotificationDispatch::query(), 'created_at');
    }

    protected function campaignQuery()
    {
        return $this->applyDateRange(NotificationCampaign::query(), 'created_at');
    }

    protected function applyDateRange($query, string $column)
    {
        if ($this->range === 'all') {
            return $query;
        }

        if ($this->range === 'custom') {
            return $query
                ->when($this->dateFrom !== '', fn ($builder) => $builder->whereDate($column, '>=', $this->dateFrom))
                ->when($this->dateTo !== '', fn ($builder) => $builder->whereDate($column, '<=', $this->dateTo));
        }

        $days = (int) str_replace('d', '', $this->range);

        return $query->where($column, '>=', now()->subDays($days));
    }

    #[Computed]
    public function stats(): array
    {
        $sentDispatches = (clone $this->dispatchQuery())->where('status', 'sent')->count();
        $failedDispatches = (clone $this->dispatchQuery())->where('status', 'failed')->count();

        $approvalTurnaround = $this->campaignQuery()
            ->whereNotNull('approved_at')
            ->get(['created_at', 'approved_at'])
            ->map(fn (NotificationCampaign $campaign) => $campaign->created_at?->diffInMinutes($campaign->approved_at))
            ->filter(fn ($minutes) => is_int($minutes))
            ->values();

        return [
            'sent' => $sentDispatches,
            'failed' => $failedDispatches,
            'approval_turnaround_minutes' => $approvalTurnaround->isNotEmpty()
                ? round($approvalTurnaround->avg(), 1)
                : null,
            'scheduled' => $this->campaignQuery()
                ->whereNotNull('scheduled_at')
                ->whereIn('status', ['draft', 'queued'])
                ->count(),
        ];
    }

    #[Computed]
    public function statusByChannel(): Collection
    {
        return $this->dispatchQuery()
            ->select('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function (Collection $items, string $channel): array {
                return [
                    'channel' => $channel,
                    'sent' => $items->where('status', 'sent')->count(),
                    'failed' => $items->where('status', 'failed')->count(),
                    'pending' => $items->where('status', 'pending')->count(),
                ];
            })
            ->values();
    }

    protected function mailDispatches(): Collection
    {
        return $this->dispatchQuery()
            ->where('channel', 'mail')
            ->latest('id')
            ->get(['id', 'status', 'error_message', 'meta', 'attempt_count', 'failed_at', 'sent_at', 'provider_message_id']);
    }

    protected function mapFailureReasons(Collection $dispatches): Collection
    {
        return $dispatches
            ->where('status', 'failed')
            ->groupBy(fn (NotificationDispatch $dispatch) => $dispatch->error_message ?: __('notifications::common.helpers.unknown_failure'))
            ->map(fn (Collection $items, string $reason) => [
                'reason' => $reason,
                'count' => $items->count(),
                'latest_failed_at' => optional($items->first()->failed_at)?->format('d.m.Y H:i'),
                'latest_recipient' => data_get($items->first()->meta, 'recipient_email'),
            ])
            ->values()
            ->take(6);
    }

    protected function mapProviderStats(Collection $dispatches): Collection
    {
        return $dispatches
            ->groupBy(fn (NotificationDispatch $dispatch) => data_get($dispatch->meta, 'driver', __('notifications::common.providers.unknown')))
            ->map(fn (Collection $items, string $driver) => [
                'driver' => $driver,
                'sent' => $items->where('status', 'sent')->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'attempts' => (int) $items->sum('attempt_count'),
                'latest_provider_message_id' => $items->pluck('provider_message_id')->filter()->first(),
                'latest_error' => $items->pluck('error_message')->filter()->first(),
            ])
            ->values();
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    public function render()
    {
        $mailDispatches = $this->mailDispatches();

        return view('notification::livewire.notification.analytics-panel', [
            'stats' => $this->stats,
            'failureReasons' => $this->mapFailureReasons($mailDispatches),
            'providerStats' => $this->mapProviderStats($mailDispatches),
            'statusByChannel' => $this->statusByChannel,
            'rangeOptions' => [
                '7d' => __('notifications::common.range_options.last_7_days'),
                '30d' => __('notifications::common.range_options.last_30_days'),
                '90d' => __('notifications::common.range_options.last_90_days'),
                'all' => __('notifications::common.range_options.all_time'),
                'custom' => __('notifications::common.range_options.custom'),
            ],
        ]);
    }
}
