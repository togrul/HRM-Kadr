<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceDailyMonitorReadService;
use Livewire\Component;
use Livewire\WithPagination;

class DailyMonitor extends Component
{
    use WithPagination;

    public string $date = '';

    public string $search = '';

    public string $statusFilter = 'all';

    public int $perPage = 20;

    public function mount(AttendanceAuthorizationService $authorization): void
    {
        if (! $authorization->can('attendance.view')) {
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

    public function render()
    {
        /** @var AttendanceDailyMonitorReadService $readService */
        $readService = app(AttendanceDailyMonitorReadService::class);

        $rows = $readService->paginateRows(
            date: $this->date,
            search: trim($this->search),
            statusFilter: $this->statusFilter,
            perPage: $this->perPage
        );

        $totals = $readService->totals(
            date: $this->date,
            search: trim($this->search),
            statusFilter: $this->statusFilter
        );

        return view('attendance::livewire.attendance.daily-monitor', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}
