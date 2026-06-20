<?php

namespace App\Modules\Notifications\Livewire\Concerns;

trait InteractsWithNotificationAuthorization
{
    protected function authorizeNotificationSettingsView(): void
    {
        $this->authorize('access-settings');
    }

    protected function authorizeTemplateManagement(): void
    {
        $this->authorize('manage-notification-templates');
    }

    protected function authorizeRuleManagement(): void
    {
        $this->authorize('manage-notification-rules');
    }

    protected function authorizeCampaignManagement(): void
    {
        $this->authorize('manage-notification-campaigns');
    }

    protected function authorizeCampaignApprovals(): void
    {
        $this->authorize('approve-notification-campaigns');
    }

    protected function canManageTemplates(): bool
    {
        return (bool) auth()->user()?->can('manage-notification-templates');
    }

    protected function canManageRules(): bool
    {
        return (bool) auth()->user()?->can('manage-notification-rules');
    }

    protected function canManageCampaigns(): bool
    {
        return (bool) auth()->user()?->can('manage-notification-campaigns');
    }

    protected function canApproveCampaigns(): bool
    {
        return (bool) auth()->user()?->can('approve-notification-campaigns');
    }
}
