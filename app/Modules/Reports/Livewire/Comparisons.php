<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\ComparativeReportService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Comparisons extends Component
{
    use WithRuntimeMemo;

    public int $year;

    public int $month;

    public ?int $structureId = null;

    /**
     * The period comes from the dashboard's panel, the one period control every tab shares.
     */
    public function mount(ReportsAccessService $access, ?int $year = null, ?int $month = null, ?int $structureId = null): void
    {
        $access->authorizeView();

        $this->year = $year ?: (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, $month ?: (int) request()->integer('month', now()->month)));
        $this->structureId = $structureId ?: request()->integer('structure_id') ?: null;
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

    public function render(): View
    {
        return view('reports::livewire.reports.comparisons');
    }

    public function placeholder(): View
    {
        return view('reports::livewire.reports.placeholder');
    }
}
