<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Exports\ReportsTableExport;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

#[Isolate]
class DynamicBuilder extends Component
{
    use WithRuntimeMemo;

    public bool $canExport = false;

    public string $source = 'personnel';

    public string $groupBy = 'structure';

    public string $metric = 'count';

    public int $year;

    public int $month;

    public ?int $structureId = null;

    public array $sourceOptions = [];

    public array $groupOptions = [];

    public array $metricOptions = [];

    public array $structureOptions = [];

    public function mount(
        ReportsAccessService $access,
        DynamicReportBuilderService $builder,
        ReportsStructureScopeService $structures,
        ?string $source = null,
        ?string $groupBy = null,
        ?string $metric = null,
        ?int $year = null,
        ?int $month = null,
        ?int $structureId = null,
    ): void {
        $access->authorizeView();
        $this->canExport = (bool) auth()->user()?->can('export-reports');

        $this->source = $source ?: (string) request()->string('source', 'personnel');
        $this->groupBy = $groupBy ?: (string) request()->string('group_by', 'structure');
        $this->metric = $metric ?: (string) request()->string('metric', 'count');
        $this->year = $year ?: (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, $month ?: (int) request()->integer('month', now()->month)));
        $this->structureId = $structureId ?: request()->integer('structure_id') ?: null;
        $this->sourceOptions = $builder->sourceOptions();
        $this->structureOptions = $structures->filterOptions()->all();
        $this->syncDependentOptions($builder);
    }

    public function updatedSource(): void
    {
        $this->syncDependentOptions(app(DynamicReportBuilderService::class));
        $this->resetRuntimeMemo();
    }

    public function updatedGroupBy(): void
    {
        $this->resetRuntimeMemo();
    }

    public function updatedMetric(): void
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
            "reports.dynamic.{$this->source}.{$this->groupBy}.{$this->metric}.{$this->year}.{$this->month}.".($this->structureId ?: 'all'),
            fn () => app(DynamicReportBuilderService::class)->build($this->source, $this->groupBy, $this->metric, $this->filters())
        );
    }

    public function exportExcel()
    {
        app(ReportsAccessService::class)->authorizeExport();

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "dynamic-report-{$this->source}-{$this->groupBy}-{$this->metric}.xlsx"
        );
    }

    public function exportCsv()
    {
        app(ReportsAccessService::class)->authorizeExport();

        return Excel::download(
            new ReportsTableExport(collect($this->payload['rows']), $this->payload['columns']),
            "dynamic-report-{$this->source}-{$this->groupBy}-{$this->metric}.csv",
            ExcelWriter::CSV
        );
    }

    public function printUrl(): string
    {
        return route('reports.print-dynamic', [
            'source' => $this->source,
            'group_by' => $this->groupBy,
            'metric' => $this->metric,
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ]);
    }

    public function render()
    {
        return view('reports::livewire.reports.dynamic-builder');
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

    protected function syncDependentOptions(DynamicReportBuilderService $builder): void
    {
        $this->groupOptions = $builder->groupOptions($this->source);
        $this->metricOptions = $builder->metricOptions($this->source);

        $groupKeys = collect($this->groupOptions)->pluck('key')->all();
        if (! in_array($this->groupBy, $groupKeys, true)) {
            $this->groupBy = $groupKeys[0] ?? 'structure';
        }

        $metricKeys = collect($this->metricOptions)->pluck('key')->all();
        if (! in_array($this->metric, $metricKeys, true)) {
            $this->metric = $metricKeys[0] ?? 'count';
        }
    }
}
