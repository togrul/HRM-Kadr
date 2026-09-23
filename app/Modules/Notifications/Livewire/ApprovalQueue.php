<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use App\Modules\Notifications\Support\NotificationTriggerRegistry;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ApprovalQueue extends Component
{
    use InteractsWithNotificationAuthorization;

    public array $notes = [];

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    #[On('notification-campaign-changed')]
    public function refreshQueue(): void {}

    public function approve(int $campaignId): void
    {
        $this->authorizeCampaignApprovals();
        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->approveCampaign($campaign, $this->notes[$campaignId] ?? null);
        unset($this->notes[$campaignId]);
        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_approved'));
    }

    public function reject(int $campaignId): void
    {
        $this->authorizeCampaignApprovals();
        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->rejectCampaign($campaign, $this->notes[$campaignId] ?? null);
        unset($this->notes[$campaignId]);
        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_rejected'));
    }

    #[Computed]
    public function campaigns()
    {
        return NotificationCampaign::query()
            ->where('approval_status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'title', 'category', 'channel', 'scheduled_at', 'created_at']);
    }

    public function placeholder()
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    public function render()
    {
        return view('notification::livewire.notification.approval-queue', [
            'campaigns' => $this->campaigns,
            'canApproveCampaigns' => $this->canApproveCampaigns(),
            'categoryLabels' => NotificationTriggerRegistry::campaignCategoryLabels(),
        ]);
    }
}
