<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsOverviewService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Overview extends Component
{
    use WithRuntimeMemo;

    public int $trendWindow = 6;

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public array $structureOptions = [];

    public function mount(ReportsAccessService $access, ReportsStructureScopeService $structures): void
    {
        $access->authorizeView();

        $this->trendWindow = in_array((int) request()->integer('trend_window', 6), [6, 12], true) ? (int) request()->integer('trend_window', 6) : 6;
        $this->year = (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, (int) request()->integer('month', now()->month)));
        $this->structureId = request()->integer('structure_id') ?: null;
        $this->structureOptions = $structures->filterOptions()->all();
    }

    public function setTrendWindow(int $window): void
    {
        $this->trendWindow = in_array($window, [6, 12], true) ? $window : 6;
        $this->resetRuntimeMemo();
    }

    public function updatedYear(): void
    {
        $this->resetRuntimeMemo();
    }

    public function updatedMonth(): void
    {
        $this->month = max(1, min(12, $this->month));
        $this->resetRuntimeMemo();
    }

    public function updatedStructureId(): void
    {
        $this->structureId = $this->structureId ?: null;
        $this->resetRuntimeMemo();
    }

    public function getPayloadProperty(): array
    {
        return $this->rememberRuntime(
            "reports.overview.{$this->year}.{$this->month}.{$this->trendWindow}.".($this->structureId ?: 'all'),
            fn () => app(ReportsOverviewService::class)->build($this->year, $this->month, $this->structureId, $this->trendWindow)
        );
    }

    public function render()
    {
        return view('reports::livewire.reports.overview');
    }

    public function placeholder()
    {
        return view('reports::livewire.reports.placeholder');
    }
}
