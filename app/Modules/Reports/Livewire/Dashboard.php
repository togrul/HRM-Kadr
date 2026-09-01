<?php

namespace App\Modules\Reports\Livewire;

use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Exports\ReportsTableExport;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public bool $canExport = false;

    /**
     * @var array<int,array{id:int,label:string}>
     */
    public array $structureOptions = [];

    public function mount(ReportsAccessService $access, ReportsStructureScopeService $structures): void
    {
        $access->authorizeView();
        $this->canExport = (bool) auth()->user()?->can('export-reports');
        $this->structureOptions = $structures->filterOptions()->all();

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

    public function updatedMonth(): void
    {
        $this->month = max(1, min(12, $this->month));
    }

    public function updatedStructureId(): void
    {
        $this->structureId = $this->structureId ?: null;
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

    /**
     * Print and export follow the panel's period, so the header acts on exactly what the
     * page is showing. The overview has no table of its own — it prints and exports the
     * headcount report, which is the standard report its tiles summarise.
     */
    public function printUrl(): string
    {
        $filters = [
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];

        if ($this->activeTab === 'dynamic') {
            return route('reports.print-dynamic', $filters + [
                'source' => $this->source,
                'group_by' => $this->groupBy,
                'metric' => $this->metric,
            ]);
        }

        return route('reports.print-standard', $filters + [
            'report' => $this->activeTab === 'standard' ? $this->report : 'headcount',
        ]);
    }

    public function exportExcel(): BinaryFileResponse
    {
        app(ReportsAccessService::class)->authorizeExport();

        $filters = [
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];

        $payload = $this->activeTab === 'dynamic'
            ? app(DynamicReportBuilderService::class)->build($this->source, $this->groupBy, $this->metric, $filters)
            : app(StandardReportService::class)->build($this->activeTab === 'standard' ? $this->report : 'headcount', $filters);

        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];

        return Excel::download(
            new ReportsTableExport(collect($rows), $columns),
            "reports-{$this->activeTab}-{$this->year}-{$this->month}.xlsx"
        );
    }

    public function render()
    {
        return view('reports::livewire.reports.dashboard');
    }
}
