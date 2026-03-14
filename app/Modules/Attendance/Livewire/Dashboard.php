<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    private const ALLOWED_TABS = ['overview', 'manager-summary', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual', 'history', 'settings', 'shifts', 'calendar-regimes'];

    public int $year;

    public int $month;

    public string $activeTab = 'overview';

    public ?int $selectedStructureId = null;

    public string $historyType = 'all';

    public ?int $historySubjectId = null;

    /**
     * @var array<int,string>
     */
    public array $availableTabs = [];

    public array $overview = [];

    public function mount(
        AttendanceOverviewService $overviewService,
        AttendanceAuthorizationService $authorization,
        AttendanceStructureScopeReadService $structureScopeRead
    ): void
    {
        $authorization->authorize('attendance.view');

        $now = Carbon::now();

        $this->year = is_numeric(request()->query('year'))
            ? (int) request()->query('year')
            : (int) $now->year;
        $this->month = is_numeric(request()->query('month'))
            ? max(1, min(12, (int) request()->query('month')))
            : (int) $now->month;
        $this->availableTabs = $this->resolveAvailableTabs($authorization);
        $this->selectedStructureId = is_numeric(request()->query('structure_id'))
            ? (int) request()->query('structure_id')
            : null;
        $this->historyType = $this->resolveRequestedHistoryType((string) request()->query('history_type', 'all'));
        $this->historySubjectId = is_numeric(request()->query('history_subject_id'))
            ? (int) request()->query('history_subject_id')
            : null;

        $requestedTab = (string) request()->query('tab', 'overview');
        if (in_array($requestedTab, $this->availableTabs, true)) {
            $this->activeTab = $requestedTab;
        } else {
            $this->activeTab = $this->availableTabs[0] ?? 'overview';
        }

        $this->overview = $overviewService->build(
            $this->year,
            $this->month,
            $this->selectedStructureId,
            true,
            $structureScopeRead->resolveIds($this->selectedStructureId)
        );
    }

    public function updatedYear(AttendanceOverviewService $overviewService): void
    {
        $this->refreshOverview($overviewService);
    }

    public function updatedMonth(AttendanceOverviewService $overviewService): void
    {
        $this->refreshOverview($overviewService);
    }

    private function refreshOverview(AttendanceOverviewService $overviewService): void
    {
        $this->overview = $overviewService->build(
            $this->year,
            $this->month,
            $this->selectedStructureId,
            true,
            app(AttendanceStructureScopeReadService::class)->resolveIds($this->selectedStructureId)
        );
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, $this->availableTabs, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    #[On('selectStructure')]
    public function selectStructure(mixed $payload = null): void
    {
        if (is_array($payload)) {
            $payload = $payload['id'] ?? (array_is_list($payload) ? ($payload[0] ?? null) : null);
        }

        $this->selectedStructureId = is_numeric($payload) ? (int) $payload : null;
        $this->refreshOverview(app(AttendanceOverviewService::class));
    }

    #[On('filterSelected')]
    public function clearSelectedStructure(): void
    {
        $this->selectedStructureId = null;
        $this->refreshOverview(app(AttendanceOverviewService::class));
    }

    /**
     * @return array<int,string>
     */
    private function resolveAvailableTabs(AttendanceAuthorizationService $authorization): array
    {
        $tabs = ['overview'];

        if ($authorization->can('attendance.daily.view')) {
            $tabs[] = 'daily-monitor';
        }

        if ($authorization->can('attendance.manager.summary.view')) {
            $tabs[] = 'manager-summary';
        }

        if ($authorization->can('attendance.puantaj.view')) {
            $tabs[] = 'puantaj';
        }

        if ($authorization->can('attendance.exceptions.view')) {
            $tabs[] = 'exceptions';
        }

        if ($authorization->can('attendance.overtime.view')) {
            $tabs[] = 'overtime';
        }

        if ($authorization->can('attendance.month.view')) {
            $tabs[] = 'month-close';
        }

        if ($authorization->can('attendance.manual.view')) {
            $tabs[] = 'manual';
        }

        if ($authorization->can('attendance.history.view')) {
            $tabs[] = 'history';
        }

        if ($authorization->can('attendance.settings.manage')) {
            $tabs[] = 'settings';
        }

        if ($authorization->can('attendance.shifts.manage')) {
            $tabs[] = 'shifts';
        }

        if ($authorization->can('attendance.calendars.manage')) {
            $tabs[] = 'calendar-regimes';
        }

        return array_values(array_intersect(self::ALLOWED_TABS, $tabs));
    }

    public function render()
    {
        return view('attendance::livewire.attendance.dashboard');
    }

    private function resolveRequestedHistoryType(string $type): string
    {
        return in_array($type, ['all', 'calendar', 'shift', 'assignment', 'settings', 'manual', 'overtime', 'exceptions', 'month'], true)
            ? $type
            : 'all';
    }
}
