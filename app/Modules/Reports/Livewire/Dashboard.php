<?php

namespace App\Modules\Reports\Livewire;

use App\Modules\Reports\Application\Services\ReportsAccessService;
use Livewire\Component;

class Dashboard extends Component
{
    public string $activeTab = 'overview';

    public string $report = 'headcount';

    public string $source = 'personnel';

    public string $groupBy = 'structure';

    public string $metric = 'count';

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public function mount(ReportsAccessService $access): void
    {
        $access->authorizeView();

        $requestedTab = (string) request()->string('tab', 'overview');
        $this->activeTab = in_array($requestedTab, $this->tabs(), true) ? $requestedTab : 'overview';
        $this->report = request()->filled('report') ? (string) request()->string('report') : 'headcount';
        $this->source = request()->filled('source') ? (string) request()->string('source') : 'personnel';
        $this->groupBy = request()->filled('group_by') ? (string) request()->string('group_by') : 'structure';
        $this->metric = request()->filled('metric') ? (string) request()->string('metric') : 'count';
        $this->year = (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, (int) request()->integer('month', now()->month)));
        $this->structureId = request()->integer('structure_id') ?: null;
    }

    /**
     * @return array<int,string>
     */
    public function tabs(): array
    {
        return ['overview', 'standard', 'dynamic', 'comparisons'];
    }

    public function tabRoute(string $tab): string
    {
        return route('reports', array_filter([
            'tab' => $tab,
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function render()
    {
        return view('reports::livewire.reports.dashboard');
    }
}
