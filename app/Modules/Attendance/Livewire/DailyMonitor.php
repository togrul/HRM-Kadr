<?php

namespace App\Modules\Attendance\Livewire;

use App\Services\StructurePathService;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceDailyMonitorReadService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Livewire\Component;
use Livewire\WithPagination;

class DailyMonitor extends Component
{
    use WithPagination;
    use NestedStructureTrait;

    public string $date = '';

    public string $search = '';

    public string $statusFilter = 'all';

    public int $perPage = 20;

    public ?int $selectedStructureId = null;

    public function mount(AttendanceAuthorizationService $authorization): void
    {
        if (! $authorization->can('attendance.daily.view')) {
            abort(403);
        }

        $this->date = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStructureId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];

        /** @var AttendanceDailyMonitorReadService $readService */
        $readService = app(AttendanceDailyMonitorReadService::class);
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        $rows = $readService->paginateRows(
            date: $this->date,
            search: trim($this->search),
            statusFilter: $this->statusFilter,
            perPage: $this->perPage,
            structureIds: $structureIds
        );

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) use ($structurePathService) {
                $row->structure_path = $structurePathService->resolve((int) ($row->structure_id ?? 0));

                return $row;
            })
        );

        $totals = $readService->totals(
            date: $this->date,
            search: trim($this->search),
            statusFilter: $this->statusFilter,
            structureIds: $structureIds
        );

        return view('attendance::livewire.attendance.daily-monitor', [
            'rows' => $rows,
            'totals' => $totals,
            'selectedStructureLabel' => $structureScopeRead->label($this->selectedStructureId),
        ]);
    }
}
