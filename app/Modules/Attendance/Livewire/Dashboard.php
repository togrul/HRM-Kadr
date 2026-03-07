<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    private const ALLOWED_TABS = ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual', 'settings', 'shifts'];

    public int $year;

    public int $month;

    public string $activeTab = 'overview';

    public ?int $selectedStructureId = null;

    /**
     * @var array<int,string>
     */
    public array $availableTabs = [];

    public array $overview = [];

    public function mount(
        AttendanceOverviewService $overviewService,
        AttendanceAuthorizationService $authorization
    ): void
    {
        $authorization->authorize('attendance.view');

        $now = Carbon::now();

        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
        $this->availableTabs = $this->resolveAvailableTabs($authorization);

        $requestedTab = (string) request()->query('tab', 'overview');
        if (in_array($requestedTab, $this->availableTabs, true)) {
            $this->activeTab = $requestedTab;
        } else {
            $this->activeTab = $this->availableTabs[0] ?? 'overview';
        }

        $this->overview = $overviewService->build($this->year, $this->month);
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
        $this->overview = $overviewService->build($this->year, $this->month);
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
    }

    #[On('filterSelected')]
    public function clearSelectedStructure(): void
    {
        $this->selectedStructureId = null;
    }

    /**
     * @return array<int,string>
     */
    private function resolveAvailableTabs(AttendanceAuthorizationService $authorization): array
    {
        $tabs = ['overview'];

        if ($authorization->can('attendance.view')) {
            $tabs[] = 'daily-monitor';
            $tabs[] = 'puantaj';
            $tabs[] = 'exceptions';
            $tabs[] = 'overtime';
            $tabs[] = 'month-close';
            $tabs[] = 'manual';
        }

        if (! $authorization->can('attendance.exceptions.resolve')) {
            $tabs = array_values(array_diff($tabs, ['exceptions']));
        }

        if (! $authorization->can('attendance.overtime.approve')) {
            $tabs = array_values(array_diff($tabs, ['overtime']));
        }

        if (! $authorization->can('attendance.month.manage') && ! $authorization->can('attendance.export')) {
            $tabs = array_values(array_diff($tabs, ['month-close']));
        }

        if (! $authorization->can('attendance.manual.write') && ! $authorization->can('attendance.manual.approve')) {
            $tabs = array_values(array_diff($tabs, ['manual']));
        }

        if ($authorization->can('attendance.month.manage')) {
            $tabs[] = 'settings';
            $tabs[] = 'shifts';
        }

        return array_values(array_intersect(self::ALLOWED_TABS, $tabs));
    }

    public function render()
    {
        return view('attendance::livewire.attendance.dashboard');
    }
}
