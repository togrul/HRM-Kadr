<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
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
    }

    public function reject(int $campaignId): void
    {
        $this->authorizeCampaignApprovals();
        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->rejectCampaign($campaign, $this->notes[$campaignId] ?? null);
        unset($this->notes[$campaignId]);
        $this->dispatch('notification-campaign-changed');
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
