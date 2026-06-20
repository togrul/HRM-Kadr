<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignBoard extends Component
{
    use WithPagination;
    use InteractsWithNotificationAuthorization;

    private const ALLOWED_STATUSES = [
        'draft',
        'queued',
        'sent',
        'failed',
        'cancelled',
    ];

    public string $statusFilter = 'all';

    public string $search = '';

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    #[On('notification-template-changed')]
    #[On('notification-rule-changed')]
    #[On('notification-campaign-changed')]
    public function refreshBoard(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $campaignId, string $status): void
    {
        $this->authorizeCampaignManagement();

        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            return;
        }

        NotificationCampaign::query()
            ->whereKey($campaignId)
            ->update(['status' => $status]);

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_status_updated'));
    }

    public function duplicate(int $campaignId): void
    {
        $this->authorizeCampaignManagement();

        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->duplicateCampaign($campaign);

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_duplicated'));
    }

    public function resend(int $campaignId): void
    {
        $this->authorizeCampaignManagement();

        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->duplicateCampaign($campaign, dispatchNow: true);

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_resent'));
    }

    public function retry(int $campaignId): void
    {
        $this->authorizeCampaignManagement();

        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->retryFailedDispatches($campaign);

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_retried'));
    }

    public function dispatchNow(int $campaignId): void
    {
        $this->authorizeCampaignManagement();

        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->dispatchCampaign($campaign, forceNow: true);

        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_dispatched'));
    }

    public function delete(int $campaignId): void
    {
        $this->authorizeCampaignManagement();
        NotificationCampaign::query()->whereKey($campaignId)->delete();
        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_deleted'));
    }

    #[Computed]
    public function campaigns()
    {
        $campaigns = NotificationCampaign::query()
            ->withCount([
                'dispatches',
                'dispatches as failed_dispatches_count' => fn ($query) => $query->where('status', 'failed'),
                'dispatches as sent_dispatches_count' => fn ($query) => $query->where('status', 'sent'),
            ])
            ->with([
                'creator:id,name,email',
                'approver:id,name,email',
                'approvals' => fn ($query) => $query
                    ->latest('id')
                    ->select(['id', 'campaign_id', 'action', 'note', 'acted_at']),
                'dispatches' => fn ($query) => $query
                    ->latest('id')
                    ->select(['id', 'campaign_id', 'channel', 'status', 'attempt_count', 'provider_message_id', 'error_message', 'meta', 'last_attempt_at', 'sent_at', 'failed_at']),
            ])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')
            ->paginate(8);

        $campaigns->getCollection()->transform(function (NotificationCampaign $campaign) {
            $copyCount = preg_match_all($this->duplicateTitlePattern(), (string) $campaign->title, $matches);
            $displayTitle = trim((string) preg_replace($this->duplicateTitlePattern(), '', (string) $campaign->title));

            $campaign->setAttribute('display_title', $displayTitle !== '' ? $displayTitle : $campaign->title);
            $campaign->setAttribute('display_copy_count', $copyCount);
            $campaign->setAttribute('display_sent_count', (int) $campaign->sent_dispatches_count);
            $campaign->setAttribute('display_failed_count', (int) $campaign->failed_dispatches_count);
            $campaign->setAttribute('display_total_count', (int) $campaign->dispatches_count);

            if ($campaign->status === 'failed' && (int) $campaign->dispatches_count === 0) {
                $campaign->setAttribute('display_failed_count', 1);
                $campaign->setAttribute('display_total_count', 1);
            }

            return $campaign;
        });

        return $campaigns;
    }

    protected function duplicateTitlePattern(): string
    {
        $copySuffix = trim((string) __('notifications::common.badges.copy_suffix'));
        $copyLabel = trim((string) __('notifications::common.badges.copy_label'));

        $parts = array_filter([
            preg_quote($copySuffix, '/'),
            '\('.preg_quote($copyLabel, '/').'\)',
            '\(surət\)',
            '\(copy\)',
        ]);

        return '/(?:\s*(?:'.implode('|', $parts).'))/iu';
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    public function render()
    {
        return view('notification::livewire.notification.campaign-board', [
            'campaigns' => $this->campaigns,
            'canManageCampaigns' => $this->canManageCampaigns(),
            'categoryLabels' => [
                'birthday' => __('notifications::common.categories.birthday'),
                'position_change' => __('notifications::common.categories.position_change'),
                'holiday' => __('notifications::common.categories.holiday'),
                'announcement' => __('notifications::common.categories.announcement'),
                'training_result' => __('notifications::common.categories.training_result'),
                'leave_status' => __('notifications::common.categories.leave_status'),
            ],
        ]);
    }
}
