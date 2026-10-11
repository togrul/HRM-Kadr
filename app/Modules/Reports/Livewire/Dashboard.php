<?php

namespace App\Modules\Reports\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Models\User;
use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportPresetService;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\ReportSelectionService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Application\Services\StandardReportCatalogService;
use App\Modules\Reports\Application\Services\StandardReportService;
use App\Modules\Reports\Domain\Models\ReportPreset;
use App\Modules\Reports\Exports\ReportsTableExport;
use App\Modules\Reports\Livewire\Concerns\DownloadsReportPdf;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Dashboard extends Component
{
    use ConfirmsDestructiveActions;
    use DownloadsReportPdf;

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
     * İstifadəçinin hesabat qruplarına görə açıq olan tablar.
     *
     * @var list<string>
     */
    public array $availableTabs = ['overview'];

    /**
     * @var array<int,array{id:int,label:string}>
     */
    public array $structureOptions = [];

    public string $presetName = '';

    public bool $presetShared = false;

    public bool $canSharePresets = false;

    /**
     * Şablon tətbiq olunanda artır: tab komponentləri yeni seçimlə yenidən qurulsun.
     */
    public int $selectionVersion = 0;

    public function mount(ReportsAccessService $access, ReportsStructureScopeService $structures, StandardReportCatalogService $catalog, DynamicReportBuilderService $builder): void
    {
        $access->authorizeView();
        $this->canExport = $access->canExport();
        $this->canSharePresets = $access->canManageSharedPresets();
        $this->structureOptions = $structures->filterOptions()->all();

        $standardKeys = array_column($access->filterStandardReports($catalog->all()), 'key');
        $sourceKeys = array_column($access->filterDynamicSources($builder->sourceOptions()), 'key');
        $this->availableTabs = array_values(array_filter([
            'overview',
            $standardKeys !== [] ? 'standard' : null,
            $sourceKeys !== [] ? 'dynamic' : null,
            $access->visibleComparisonCards() !== [] ? 'comparisons' : null,
            $access->canSchedule() ? 'schedules' : null,
        ]));

        $requestedTab = (string) request()->string('tab', 'overview');
        $this->activeTab = in_array($requestedTab, $this->availableTabs, true) ? $requestedTab : 'overview';
        $requestedReport = request()->filled('report') ? (string) request()->string('report') : 'headcount';
        $this->report = in_array($requestedReport, $standardKeys, true) ? $requestedReport : (string) ($standardKeys[0] ?? 'headcount');
        $requestedSource = request()->filled('source') ? (string) request()->string('source') : 'personnel';
        $this->source = in_array($requestedSource, $sourceKeys, true) ? $requestedSource : (string) ($sourceKeys[0] ?? 'personnel');
        $this->groupBy = request()->filled('group_by') ? (string) request()->string('group_by') : 'structure';
        $this->metric = request()->filled('metric') ? (string) request()->string('metric') : 'count';
        $this->year = (int) request()->integer('year', now()->year);
        $this->month = max(1, min(12, (int) request()->integer('month', now()->month)));
        $this->structureId = request()->integer('structure_id') ?: null;

        app(ReportsAuditLogger::class)->opened($this->auditContext());
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
     * The standard and dynamic tabs report their own selection here, so the header's Excel
     * and print act on what the tab shows now rather than on the page's initial settings.
     */
    #[On('reports-selection')]
    public function syncSelection(?string $report = null, ?string $source = null, ?string $groupBy = null, ?string $metric = null): void
    {
        $this->report = $report ?? $this->report;
        $this->source = $source ?? $this->source;
        $this->groupBy = $groupBy ?? $this->groupBy;
        $this->metric = $metric ?? $this->metric;
    }

    /**
     * @return array<int,string>
     */
    public function tabs(): array
    {
        return ['overview', 'standard', 'dynamic', 'comparisons', 'schedules'];
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
        $payload = $this->authorizedHeaderPayload();
        app(ReportsAuditLogger::class)->exported('xlsx', $this->auditContext());

        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];

        return Excel::download(
            new ReportsTableExport(collect($rows), $columns),
            "reports-{$this->activeTab}-{$this->year}-{$this->month}.xlsx"
        );
    }

    public function exportPdf(): ?StreamedResponse
    {
        $payload = $this->authorizedHeaderPayload();
        $download = $this->downloadReportPdf($payload, $this->periodFilters(), "reports-{$this->activeTab}-{$this->year}-{$this->month}");

        if ($download !== null) {
            app(ReportsAuditLogger::class)->exported('pdf', $this->auditContext());
        }

        return $download;
    }

    public function render(): View
    {
        return view('reports::livewire.reports.dashboard');
    }

    /**
     * Aktiv tabın (standart/dinamik) şablonları: öz və paylaşılan.
     *
     * @return list<array{id:int,name:string,shared:bool,can_delete:bool}>
     */
    public function presets(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || ! in_array($this->activeTab, ReportSelectionService::TABS, true)) {
            return [];
        }

        return app(ReportPresetService::class)->availableFor($user, $this->activeTab)
            ->map(fn (ReportPreset $preset): array => [
                'id' => (int) $preset->id,
                'name' => (string) $preset->name,
                'shared' => (bool) $preset->is_shared,
                'can_delete' => $user->can('delete', $preset),
            ])
            ->values()
            ->all();
    }

    public function savePreset(ReportPresetService $presets): void
    {
        $user = $this->currentUser();

        if (! in_array($this->activeTab, ReportSelectionService::TABS, true)) {
            $this->addError('presetName', __('reports::presets.errors.tab_not_supported'));

            return;
        }

        try {
            $presets->save($user, $this->activeTab, $this->presetName, $this->currentSelection(), $this->presetShared && $this->canSharePresets);
        } catch (ValidationException $exception) {
            $this->addError('presetName', (string) collect($exception->errors())->flatten()->first());

            return;
        }

        $this->resetErrorBag('presetName');
        $this->presetName = '';
        $this->presetShared = false;
        $this->dispatch('notify', type: 'success', message: __('reports::presets.messages.saved'));
    }

    public function applyPreset(int $presetId, ReportPresetService $presets): void
    {
        $user = $this->currentUser();
        $preset = ReportPreset::query()->findOrFail($presetId);

        try {
            $resolved = $presets->apply($user, $preset);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: (string) collect($exception->errors())->flatten()->first());

            return;
        }

        $filters = $resolved['filters'];
        $this->activeTab = in_array($resolved['tab'], $this->availableTabs, true) ? $resolved['tab'] : $this->activeTab;

        if ($resolved['tab'] === ReportSelectionService::TAB_STANDARD) {
            $this->report = (string) $filters['report'];
        } else {
            $this->source = (string) $filters['source'];
            $this->groupBy = (string) $filters['group_by'];
            $this->metric = (string) $filters['metric'];
        }

        $this->year = (int) ($filters['year'] ?? $this->year);
        $this->month = (int) ($filters['month'] ?? $this->month);
        $this->structureId = $filters['structure_id'] ?? null;
        $this->selectionVersion++;

        $this->dispatch(
            'notify',
            type: $resolved['structure_dropped'] ? 'warning' : 'success',
            message: $resolved['structure_dropped'] ? __('reports::presets.messages.structure_dropped') : __('reports::presets.messages.applied'),
        );
    }

    public function confirmPresetDeletion(int $presetId): void
    {
        $preset = ReportPreset::query()->findOrFail($presetId);
        $this->authorize('delete', $preset);

        $this->confirmDeletion(
            action: 'deletePreset',
            parameters: [$presetId],
            message: __('reports::presets.confirmations.delete'),
            description: (string) $preset->name,
        );
    }

    public function deletePreset(int $presetId, ReportPresetService $presets): void
    {
        $presets->delete($this->currentUser(), ReportPreset::query()->findOrFail($presetId));

        $this->dispatch('notify', type: 'success', message: __('reports::presets.messages.deleted'));
    }

    /**
     * Başlıqdakı Excel/çap aktiv tabın hesabatına aiddir; həmin hesabatın qrupu istifadəçiyə
     * bağlıdırsa düymələr göstərilmir.
     */
    public function headerExportAllowed(): bool
    {
        if (! $this->canExport || $this->activeTab === 'schedules') {
            return false;
        }

        $access = app(ReportsAccessService::class);

        return $this->activeTab === 'dynamic'
            ? $access->canViewDynamicSource($this->source)
            : $access->canViewStandardReport($this->activeTab === 'standard' ? $this->report : 'headcount');
    }

    /**
     * Başlıq ixracının hesabatı: ixrac və hesabat qrupu icazəsi yoxlanılır.
     *
     * @return array<string,mixed>
     */
    protected function authorizedHeaderPayload(): array
    {
        $access = app(ReportsAccessService::class);
        $access->authorizeExport();
        abort_if($this->activeTab === 'schedules', 403);

        if ($this->activeTab === 'dynamic') {
            $access->authorizeDynamicSource($this->source);

            return app(DynamicReportBuilderService::class)->build($this->source, $this->groupBy, $this->metric, $this->periodFilters());
        }

        $report = $this->activeTab === 'standard' ? $this->report : 'headcount';
        $access->authorizeStandardReport($report);

        return app(StandardReportService::class)->build($report, $this->periodFilters());
    }

    /**
     * @return array{year:int,month:int,structure_id:int|null}
     */
    protected function periodFilters(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];
    }

    /**
     * Aktiv tabın cari seçimi və filtrləri (şablon üçün).
     *
     * @return array<string,mixed>
     */
    protected function currentSelection(): array
    {
        $selection = $this->activeTab === ReportSelectionService::TAB_DYNAMIC
            ? ['source' => $this->source, 'group_by' => $this->groupBy, 'metric' => $this->metric]
            : ['report' => $this->report];

        return $selection + [
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];
    }

    protected function currentUser(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array<string,mixed>
     */
    protected function auditContext(): array
    {
        return [
            'tab' => $this->activeTab,
            'report' => $this->activeTab === 'standard' ? $this->report : null,
            'source' => $this->activeTab === 'dynamic' ? $this->source : null,
            'group_by' => $this->activeTab === 'dynamic' ? $this->groupBy : null,
            'metric' => $this->activeTab === 'dynamic' ? $this->metric : null,
            'year' => $this->year,
            'month' => $this->month,
            'structure_id' => $this->structureId,
        ];
    }
}
