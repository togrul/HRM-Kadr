<?php

namespace App\Modules\Notifications\Livewire;

use App\Models\NotificationCampaign;
use App\Modules\Notifications\Livewire\Concerns\InteractsWithNotificationAuthorization;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class HistoryBoard extends Component
{
    use InteractsWithNotificationAuthorization;
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = 'all';

    public function mount(): void
    {
        $this->authorizeNotificationSettingsView();
    }

    #[On('notification-campaign-changed')]
    public function refreshHistory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function campaigns()
    {
        $campaigns = NotificationCampaign::query()
            ->with([
                'creator:id,name,email',
                'approver:id,name,email',
                'approvals' => fn ($query) => $query
                    ->latest('acted_at')
                    ->with('actor:id,name,email'),
                'dispatches' => fn ($query) => $query
                    ->latest('id')
                    ->with('user:id,name,email'),
            ])
            ->withCount([
                'dispatches',
                'dispatches as sent_dispatches_count' => fn ($query) => $query->where('status', 'sent'),
                'dispatches as failed_dispatches_count' => fn ($query) => $query->where('status', 'failed'),
            ])
            ->when($this->categoryFilter !== 'all', fn ($query) => $query->where('category', $this->categoryFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('id')
            ->paginate(6);

        $campaigns->getCollection()->transform(function (NotificationCampaign $campaign) {
            $copyCount = preg_match_all($this->duplicateTitlePattern(), (string) $campaign->title, $matches);
            $displayTitle = trim((string) preg_replace($this->duplicateTitlePattern(), '', (string) $campaign->title));

            $campaign->setAttribute('display_title', $displayTitle !== '' ? $displayTitle : $campaign->title);
            $campaign->setAttribute('display_copy_count', $copyCount);
            $campaign->setAttribute('display_sent_count', (int) $campaign->sent_dispatches_count);
            $campaign->setAttribute('display_failed_count', (int) $campaign->failed_dispatches_count);
            $campaign->setAttribute('display_total_count', (int) $campaign->dispatches_count);
            $campaign->setAttribute('latest_failed_event', $campaign->approvals->firstWhere('action', 'failed'));

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
        return view('notification::livewire.notification.history-board', [
            'campaigns' => $this->campaigns,
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
