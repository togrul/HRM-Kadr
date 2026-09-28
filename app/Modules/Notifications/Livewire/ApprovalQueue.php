<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use App\Modules\Notifications\Support\NotificationTriggerRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ApprovalQueue extends Component
{
    use InteractsWithNotificationAuthorization;

    public const PREVIEW_LIMIT = 8;

    public array $notes = [];

    /** The queue shows the newest few; "show all" lists every pending campaign. */
    public bool $showAll = false;

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
        // A rejection goes back to the author, so it has to say why.
        $this->validate(
            ["notes.{$campaignId}" => ['required', 'string', 'min:3']],
            [],
            ["notes.{$campaignId}" => __('notifications::common.fields.note')]
        );
        $campaign = NotificationCampaign::query()->findOrFail($campaignId);
        app(NotificationCampaignDispatcher::class)->rejectCampaign($campaign, $this->notes[$campaignId] ?? null);
        unset($this->notes[$campaignId]);
        $this->dispatch('notification-campaign-changed');
        $this->dispatch('notify', type: 'success', message: __('notifications::common.messages.campaign_rejected'));
    }

    #[Computed]
    public function campaigns(): Collection
    {
        return NotificationCampaign::query()
            ->where('approval_status', 'pending')
            ->latest('id')
            ->with('creator:id,name')
            ->when(! $this->showAll, fn ($query) => $query->limit(self::PREVIEW_LIMIT))
            ->get(['id', 'title', 'category', 'channel', 'scheduled_at', 'created_by', 'created_at']);
    }

    public function placeholder(): View
    {
        return view('notification::livewire.notification.placeholders.settings-panel');
    }

    public function render(): View
    {
        return view('notification::livewire.notification.approval-queue', [
            'campaigns' => $this->campaigns,
            // A short page already is the whole queue; only a full one needs counting.
            'pendingTotal' => $this->showAll || $this->campaigns->count() < self::PREVIEW_LIMIT
                ? $this->campaigns->count()
                : NotificationCampaign::query()->where('approval_status', 'pending')->count(),
            'canApproveCampaigns' => $this->canApproveCampaigns(),
            'categoryLabels' => NotificationTriggerRegistry::campaignCategoryLabels(),
        ]);
    }
}
