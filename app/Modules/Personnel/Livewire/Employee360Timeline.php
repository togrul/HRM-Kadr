<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\Personnel360TimelineService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Employee360Timeline extends Component
{
    use AuthorizesRequests;

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

    public function getTimelineItemsProperty()
    {
        return app(Personnel360TimelineService::class)->build(
            Personnel::query()->findOrFail($this->personnelId),
            $this->search,
            100,
            [
                'type' => $this->type,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
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
        return [
            'audit',
            'order',
            'leave',
            'vacation',
            'business_trip',
            'training_need',
            'training_delivery',
            'performance',
            'event',
            'media',
            'project',
        ];
    }

    public function render()
    {
        return view('personnel::livewire.personnel.professional-portfolio.timeline-panel', [
            'panelTitle' => __('personnel::information.tabs.employee_360'),
        ]);
    }
}
