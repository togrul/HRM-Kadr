<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\Personnel;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfessionalPortfolio extends Component
{
    public int $personnelId;

    public string $activeTab = 'events';

    public array $summary = [];

    public function mount($personnelModel): void
    {
        abort_unless(ProfessionalPortfolioPermissionMatrix::canViewPortfolio(auth()->user()), 403);

        $this->personnelId = (int) $personnelModel;
        abort_if($this->personnelId <= 0, 404);

        $this->refreshSummary();
    }

    #[On('portfolioRecordSaved')]
    public function refreshSummary(): void
    {
        $personnel = Personnel::query()
            ->select(['id'])
            ->withCount([
                'eventRecords as verified_events_count' => fn ($query) => $query->where('verification_status', 'verified'),
                'mediaMentions as verified_media_count' => fn ($query) => $query->where('verification_status', 'verified'),
                'projectRecords as verified_projects_count' => fn ($query) => $query->where('verification_status', 'verified'),
                'eventRecords as speaker_events_count' => fn ($query) => $query
                    ->where('verification_status', 'verified')
                    ->whereIn('participation_role', ['speaker', 'moderator', 'panelist', 'presenter']),
            ])
            ->findOrFail($this->personnelId);

        $this->summary = [
            'verified_events' => (int) $personnel->verified_events_count,
            'verified_media' => (int) $personnel->verified_media_count,
            'verified_projects' => (int) $personnel->verified_projects_count,
            'speaker_events' => (int) $personnel->speaker_events_count,
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $allowedTabs = ['events', 'media', 'projects'];
        if ($this->canViewAnalytics()) {
            $allowedTabs[] = 'analytics';
        }

        if (! in_array($tab, $allowedTabs, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    #[Computed]
    public function personnel(): Personnel
    {
        return Personnel::query()
            ->select(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
            ->findOrFail($this->personnelId);
    }

    public function canManageEvents(): bool
    {
        return (bool) auth()->user()?->can('manage-personnel-event-records');
    }

    public function canManageMedia(): bool
    {
        return (bool) auth()->user()?->can('manage-personnel-media-records');
    }

    public function canManageProjects(): bool
    {
        return (bool) auth()->user()?->can('manage-personnel-project-records');
    }

    public function canViewAnalytics(): bool
    {
        return ProfessionalPortfolioPermissionMatrix::canViewAnalytics(auth()->user());
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.shell');
    }
}
