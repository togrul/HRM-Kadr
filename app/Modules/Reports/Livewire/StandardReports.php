<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\StandardReportCatalogService;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Exports\ReportsTableExport;
use App\Modules\Reports\Livewire\Concerns\DownloadsReportPdf;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Isolate]
class StandardReports extends Component
{
    use DownloadsReportPdf;
    use WithRuntimeMemo;

    public bool $canExport = false;

    public string $report = 'headcount';

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public array $reportOptions = [];

    public function mount(
        ReportsAccessService $access,
        StandardReportCatalogService $catalog,
        ?string $report = null,
        ?int $year = null,
        ?int $month = null,
        ?int $structureId = null,
    ): void {
        $access->authorizeView();
        $this->canExport = $access->canExport();

        // Yalnız istifadəçinin hesabat qruplarına düşən hesabatlar seçilə bilər.
        $this->reportOptions = $access->filterStandardReports($catalog->all());
        abort_if($this->reportOptions === [], 403);

        $this->report = $this->allowedReport($report ?: (string) request()->string('report', 'headcount'));
        $this->year = $year ?: (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, $month ?: (int) request()->integer('month', now()->month)));
        $this->structureId = $structureId ?: request()->integer('structure_id') ?: null;
    }

    public function updatedReport(): void
    {
        $this->report = $this->allowedReport($this->report);
        $this->resetRuntimeMemo();
        $this->dispatch('reports-selection', report: $this->report)->to(Dashboard::class);

        app(ReportsAuditLogger::class)->opened($this->auditContext());
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
        app(ReportsAccessService::class)->authorizeStandardReport($this->report);

        return $this->rememberRuntime(
            "reports.standard.{$this->report}.{$this->year}.{$this->month}.".($this->structureId ?: 'all'),
            fn () => app(StandardReportService::class)->build($this->report, $this->filters())
        );
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorizeExport('xlsx');

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "reports-{$this->report}-{$this->year}-{$this->month}.xlsx"
        );
    }

    public function exportCsv(): BinaryFileResponse
    {
        $this->authorizeExport('csv');

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "reports-{$this->report}-{$this->year}-{$this->month}.csv",
            ExcelWriter::CSV
        );
    }

    /**
     * Server-side PDF (LibreOffice). Audit yalnız fayl hazır olanda yazılır.
     */
    public function exportPdf(): ?StreamedResponse
    {
        $this->authorizeExport('pdf', log: false);

        $download = $this->downloadReportPdf($this->getPayloadProperty(), $this->filters(), "reports-{$this->report}-{$this->year}-{$this->month}");

        if ($download !== null) {
            app(ReportsAuditLogger::class)->exported('pdf', $this->auditContext());
        }

        return $download;
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

    public function render(): View
    {
        return view('reports::livewire.reports.standard-reports');
    }

    public function placeholder(): View
    {
        return view('reports::livewire.reports.placeholder');
    }

    protected function authorizeExport(string $format, bool $log = true): void
    {
        $access = app(ReportsAccessService::class);
        $access->authorizeExport();
        $access->authorizeStandardReport($this->report);

        if ($log) {
            app(ReportsAuditLogger::class)->exported($format, $this->auditContext());
        }
    }

    protected function allowedReport(string $report): string
    {
        $keys = array_column($this->reportOptions, 'key');

        return in_array($report, $keys, true) ? $report : (string) ($keys[0] ?? 'headcount');
    }

    /**
     * @return array<string,mixed>
     */
    protected function auditContext(): array
    {
        return ['tab' => 'standard', 'report' => $this->report] + $this->filters();
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
