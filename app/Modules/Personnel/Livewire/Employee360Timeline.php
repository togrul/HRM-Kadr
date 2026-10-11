<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\Personnel360ExportService;
use App\Modules\Personnel\Application\Services\Personnel360TimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Employee360Timeline extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $personnelId;

    public string $search = '';

    public string $type = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(int $personnelId): void
    {
        $personnel = Personnel::query()->findOrFail($personnelId);

        $this->authorize('view', $personnel);

        $this->personnelId = $personnel->id;
    }

    public function getTimelineItemsProperty(): Collection
    {
        return app(Personnel360TimelineService::class)->build(
            Personnel::query()->findOrFail($this->personnelId),
            $this->search,
            100,
            [
                'type' => $this->type,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'viewer' => auth()->user(),
            ],
        );
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    public function typeOptions(): array
    {
        return Personnel360TimelineService::TYPES;
    }

    /**
     * Cari süzgəclərlə ixrac keçidləri; icazə (export-personnel-360) yoxdursa boş.
     * PDF yalnız serverdə çevirici (LibreOffice) olanda təklif olunur.
     *
     * @return array<string, string>
     */
    public function exportLinks(): array
    {
        if (auth()->user()?->can('export-personnel-360') !== true) {
            return [];
        }

        $query = array_filter([
            'type' => $this->type,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'search' => $this->search,
        ], fn (string $value): bool => $value !== '');

        $links = ['xlsx' => route('personnel.360.export', ['personnel' => $this->personnelId, 'format' => 'xlsx', ...$query])];

        if (app(Personnel360ExportService::class)->pdfAvailable()) {
            $links['pdf'] = route('personnel.360.export', ['personnel' => $this->personnelId, 'format' => 'pdf', ...$query]);
        }

        return $links;
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.professional-portfolio.timeline-panel', [
            'panelTitle' => __('personnel::information.tabs.employee_360'),
            'exportLinks' => $this->exportLinks(),
        ]);
    }
}
