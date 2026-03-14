<?php

namespace App\Modules\Attendance\Livewire;

use App\Services\StructurePathService;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceManagerSummaryReadService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceManagerSummary extends Component
{
    use NestedStructureTrait;
    use WithPagination;

    public int $year;

    public int $month;

    public string $search = '';

    public int $perPage = 15;

    public bool $onlyProblematic = false;

    public ?int $selectedStructureId = null;

    /**
     * @var array<string,int|float>
     */
    public array $totals = [];

    public function mount(
        int $year,
        int $month,
        AttendanceAuthorizationService $authorization,
        AttendanceManagerSummaryReadService $readService
    ): void
    {
        $authorization->authorize('attendance.manager.summary.view');

        $this->year = $year;
        $this->month = $month;
        $this->refreshTotals($readService);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyProblematic(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStructureId(AttendanceManagerSummaryReadService $readService): void
    {
        $this->resetPage();
        $this->refreshTotals($readService);
    }

    public function render()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];

        /** @var AttendanceManagerSummaryReadService $readService */
        $readService = app(AttendanceManagerSummaryReadService::class);
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        $rows = $readService->paginateRows(
            year: $this->year,
            month: $this->month,
            search: trim($this->search),
            perPage: $this->perPage,
            structureIds: $structureIds,
            onlyProblematic: $this->onlyProblematic
        );

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) use ($structurePathService) {
                $row->structure_path = $structurePathService->resolve((int) ($row->structure_id ?? 0));

                return $row;
            })
        );

        return view('attendance::livewire.attendance.manager-summary', [
            'rows' => $rows,
            'totals' => $this->totals,
            'selectedStructureLabel' => $structureScopeRead->label($this->selectedStructureId),
        ]);
    }

    private function refreshTotals(AttendanceManagerSummaryReadService $readService): void
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];

        $this->totals = $readService->totals($this->year, $this->month, $structureIds);
    }
}
