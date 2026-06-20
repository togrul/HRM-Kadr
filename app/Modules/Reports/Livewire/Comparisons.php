<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\ComparativeReportService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Comparisons extends Component
{
    use WithRuntimeMemo;

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public array $structureOptions = [];

    public function mount(ReportsAccessService $access, ReportsStructureScopeService $structures): void
    {
        $access->authorizeView();

        $this->year = (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, (int) request()->integer('month', now()->month)));
        $this->structureId = request()->integer('structure_id') ?: null;
        $this->structureOptions = $structures->filterOptions()->all();
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
            "reports.comparisons.{$this->year}.{$this->month}.".($this->structureId ?: 'all'),
            fn () => app(ComparativeReportService::class)->build($this->year, $this->month, $this->structureId)
        );
    }

    public function render()
    {
        return view('reports::livewire.reports.comparisons');
    }

    public function placeholder()
    {
        return view('reports::livewire.reports.placeholder');
    }
}
