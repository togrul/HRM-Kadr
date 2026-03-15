<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Application\Services\StandardReportCatalogService;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Exports\ReportsTableExport;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

#[Isolate]
class StandardReports extends Component
{
    use WithRuntimeMemo;

    public bool $canExport = false;

    public string $report = 'headcount';

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public array $reportOptions = [];

    public array $structureOptions = [];

    public function mount(
        ReportsAccessService $access,
        StandardReportCatalogService $catalog,
        ReportsStructureScopeService $structures,
        ?string $report = null,
        ?int $year = null,
        ?int $month = null,
        ?int $structureId = null,
    ): void {
        $access->authorizeView();
        $this->canExport = (bool) auth()->user()?->can('export-reports');

        $this->report = $report ?: (string) request()->string('report', 'headcount');
        $this->year = $year ?: (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, $month ?: (int) request()->integer('month', now()->month)));
        $this->structureId = $structureId ?: request()->integer('structure_id') ?: null;
        $this->reportOptions = $catalog->all();
        $this->structureOptions = $structures->filterOptions()->all();
    }

    public function updatedReport(): void
    {
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
            "reports.standard.{$this->report}.{$this->year}.{$this->month}.".($this->structureId ?: 'all'),
            fn () => app(StandardReportService::class)->build($this->report, $this->filters())
        );
    }

    public function exportExcel()
    {
        app(ReportsAccessService::class)->authorizeExport();

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "reports-{$this->report}-{$this->year}-{$this->month}.xlsx"
        );
    }

    public function exportCsv()
    {
        app(ReportsAccessService::class)->authorizeExport();

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "reports-{$this->report}-{$this->year}-{$this->month}.csv",
            ExcelWriter::CSV
        );
    }

    public function printUrl(): string
    {
        return route('reports.print-standard', [
            'report' => $this->report,
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ]);
    }

    public function render()
    {
        return view('reports::livewire.reports.standard-reports');
    }

    public function placeholder()
    {
        return view('reports::livewire.reports.placeholder');
    }

    /**
     * @return array<string,mixed>
     */
    protected function filters(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];
    }
}
