<?php

namespace App\Modules\Personnel\Livewire\ProfessionalPortfolio;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioTimelineService;
use Livewire\Component;

class TimelinePanel extends Component
{
    public int $personnelId;

    public string $search = '';

    public function mount(int $personnelId): void
    {
        abort_unless(auth()->user()?->canAny([
            'view-professional-portfolio',
            'manage-personnel-event-records',
            'manage-personnel-media-records',
            'manage-personnel-project-records',
            'verify-professional-portfolio-records',
        ]), 403);

        $this->personnelId = $personnelId;
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.professional-portfolio-tab');
    }

    public function getTimelineItemsProperty()
    {
        return app(ProfessionalPortfolioTimelineService::class)->build(
            Personnel::query()->findOrFail($this->personnelId),
            $this->search,
        );
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.timeline-panel');
    }
}
