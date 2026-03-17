<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class SettingsHub extends Component
{
    use InteractsWithNotificationAuthorization;

    #[Url(as: 'notifications_tab', keep: true)]
    public string $activeTab = 'overview';

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
        $this->activeTab = $this->normalizeTab($this->activeTab);
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $this->normalizeTab($tab);
    }

    #[On('notification-settings-open-tab')]
    public function openTab(string $tab): void
    {
        $this->selectTab($tab);
    }

    public function render()
    {
        $pendingApprovalCount = $this->canApproveCampaigns()
            ? NotificationCampaign::query()->where('approval_status', 'pending')->count()
            : 0;

        $tabs = [
            'overview' => ['label' => __('notifications::common.tabs.overview')],
            'analytics' => ['label' => __('notifications::common.tabs.analytics')],
            'history' => ['label' => __('notifications::common.tabs.history')],
        ];

        if ($this->canApproveCampaigns()) {
            $tabs['approval'] = [
                'label' => __('notifications::common.tabs.approval'),
                'count' => $pendingApprovalCount,
            ];
        }

        if ($this->canManageCampaigns()) {
            $tabs['announcements'] = ['label' => __('notifications::common.tabs.announcements')];
            $tabs['campaigns'] = ['label' => __('notifications::common.tabs.campaigns')];
        }

        if ($this->canManageTemplates()) {
            $tabs['templates'] = ['label' => __('notifications::common.tabs.templates')];
        }

        if ($this->canManageRules()) {
            $tabs['rules'] = ['label' => __('notifications::common.tabs.rules')];
        }

        return view('notification::livewire.notification.settings-hub', [
            'tabs' => $tabs,
            'pendingApprovalCount' => $pendingApprovalCount,
        ]);
    }

    protected function normalizeTab(string $tab): string
    {
        $allowedTabs = ['overview', 'analytics', 'history'];

        if ($this->canApproveCampaigns()) {
            $allowedTabs[] = 'approval';
        }

        if ($this->canManageCampaigns()) {
            $allowedTabs[] = 'announcements';
            $allowedTabs[] = 'campaigns';
        }

        if ($this->canManageTemplates()) {
            $allowedTabs[] = 'templates';
        }

        if ($this->canManageRules()) {
            $allowedTabs[] = 'rules';
        }

        return in_array($tab, $allowedTabs, true)
            ? $tab
            : 'overview';
    }
}
