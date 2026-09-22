<?php

namespace App\Modules\Payroll\Livewire;

use App\Models\CompensationRegime;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Modules\Payroll\Application\Services\PayrollExportService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Support\Livewire\DownloadsReportsTable;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use App\Support\Livewire\LabelsValidationFields;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Workspace shell: tabs, period / regime filters, counters, period + run creation and the
 * export toolbar. Runs, payslips and loans are their own components under Livewire\Tabs.
 *
 * @property-read \App\Models\PayrollPeriod|null $activePeriod
 * @property-read \Illuminate\Support\Collection<int, \App\Models\PayrollPeriod> $periods
 * @property-read \Illuminate\Support\Collection<int, int> $runIds
 */
class Dashboard extends Component
{
    use DownloadsReportsTable;
    use InteractsWithTabbedWorkspace;
    use LabelsValidationFields;

    public string $activeTab = 'runs';

    public ?int $periodFilter = null;

    public ?int $regimeFilter = null;

    public ?string $panel = null;

    public array $periodForm = [
        'year' => null,
        'month' => null,
    ];

    public array $runForm = [
        'payroll_period_id' => null,
        'regime_id' => null,
        'run_type' => 'regular',
    ];

    #[Locked]
    public ?int $selectedRunId = null;

    /** Employee the loans tab opens on when reached from the runs overview. */
    #[Locked]
    public ?string $loanTabelNo = null;

    #[Locked]
    public ?string $loanPersonnelLabel = null;

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
        $this->periodForm['year'] = (int) now()->format('Y');
        $this->periodForm['month'] = (int) now()->format('m');
        $this->periodFilter = PayrollPeriod::query()->orderByDesc('year')->orderByDesc('month')->value('id');
    }

    public function openPanel(string $panel): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetValidation();
        $this->panel = $panel;

        if ($panel === 'run') {
            $this->runForm['payroll_period_id'] = $this->runForm['payroll_period_id'] ?: $this->periodFilter;
        }
    }

    public function closePanel(): void
    {
        $this->panel = null;
        $this->resetValidation();
    }

    protected function allowedTabs(): array
    {
        return ['runs', 'payslips', 'loans'];
    }

    #[Computed]
    public function allowedTabsList(): array
    {
        return $this->allowedTabs();
    }

    public function canView(): bool
    {
        return auth()->user()?->can('show-payroll') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-payroll') ?? false;
    }

    public function canExport(): bool
    {
        return auth()->user()?->can('export-payroll') ?? false;
    }

    protected function fieldLabelPrefix(): string
    {
        return 'payroll::dashboard.fields.';
    }

    // ----------------------------------------------------------------
    // Exports (sidebar, header and the payslips tab all call these)
    // ----------------------------------------------------------------

    public function exportBankFile(int $runId, PayrollExportService $service): BinaryFileResponse
    {
        abort_unless($this->canExport(), 403);

        return $this->downloadReportTable(
            $service->bankRows(PayrollRun::findOrFail($runId)),
            [
                ['key' => 'tabel_no', 'label' => __('payroll::dashboard.export.cols.tabel_no')],
                ['key' => 'full_name', 'label' => __('payroll::dashboard.export.cols.full_name')],
                ['key' => 'iban', 'label' => __('payroll::dashboard.export.cols.iban')],
                ['key' => 'bank_name', 'label' => __('payroll::dashboard.export.cols.bank_name')],
                ['key' => 'amount', 'label' => __('payroll::dashboard.export.cols.amount')],
                ['key' => 'currency', 'label' => __('payroll::dashboard.export.cols.currency')],
            ],
            'payroll-bank-file.xlsx',
        );
    }

    public function exportBankCsv(int $runId, PayrollExportService $service): StreamedResponse
    {
        abort_unless($this->canExport(), 403);

        $rows = $service->bankRows(PayrollRun::findOrFail($runId));
        $headers = ['tabel_no', 'full_name', 'iban', 'bank_name', 'amount', 'currency'];

        return response()->streamDownload(function () use ($rows, $headers): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, [$row['tabel_no'], $row['full_name'], $row['iban'], $row['bank_name'], $row['amount'], $row['currency']]);
            }
            fclose($out);
        }, 'payroll-bank-file.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportGl(int $runId, PayrollExportService $service): BinaryFileResponse
    {
        abort_unless($this->canExport(), 403);

        return $this->downloadReportTable(
            $service->glRows(PayrollRun::findOrFail($runId)),
            [
                ['key' => 'gl_code', 'label' => __('payroll::dashboard.export.cols.gl_code')],
                ['key' => 'code', 'label' => __('payroll::dashboard.export.cols.code')],
                ['key' => 'name', 'label' => __('payroll::dashboard.export.cols.name')],
                ['key' => 'kind', 'label' => __('payroll::dashboard.export.cols.kind')],
                ['key' => 'amount', 'label' => __('payroll::dashboard.export.cols.amount')],
            ],
            'payroll-gl.xlsx',
        );
    }

    public function exportStateReport(int $runId, PayrollExportService $service): BinaryFileResponse
    {
        abort_unless($this->canExport(), 403);

        return $this->downloadReportTable(
            $service->stateRows(PayrollRun::findOrFail($runId)),
            [
                ['key' => 'tabel_no', 'label' => __('payroll::dashboard.export.cols.tabel_no')],
                ['key' => 'full_name', 'label' => __('payroll::dashboard.export.cols.full_name')],
                ['key' => 'pin', 'label' => __('payroll::dashboard.export.cols.pin')],
                ['key' => 'gross', 'label' => __('payroll::dashboard.fields.gross')],
                ['key' => 'income_tax', 'label' => __('payroll::dashboard.statutory.income_tax')],
                ['key' => 'dsmf_ee', 'label' => __('payroll::dashboard.statutory.dsmf').' (ee)'],
                ['key' => 'dsmf_er', 'label' => __('payroll::dashboard.statutory.dsmf').' (er)'],
                ['key' => 'unemployment_ee', 'label' => __('payroll::dashboard.statutory.unemployment').' (ee)'],
                ['key' => 'unemployment_er', 'label' => __('payroll::dashboard.statutory.unemployment').' (er)'],
                ['key' => 'medical_ee', 'label' => __('payroll::dashboard.statutory.medical').' (ee)'],
                ['key' => 'medical_er', 'label' => __('payroll::dashboard.statutory.medical').' (er)'],
                ['key' => 'net', 'label' => __('payroll::dashboard.fields.net')],
            ],
            'payroll-state-report.xlsx',
        );
    }

    // ----------------------------------------------------------------
    // Read models
    // ----------------------------------------------------------------

    #[Computed]
    public function summaryStats(): array
    {
        return [
            ['key' => 'periods', 'value' => PayrollPeriod::query()->count(), 'accent' => 'bg-sky-500'],
            ['key' => 'runs', 'value' => PayrollRun::query()->count(), 'accent' => 'bg-violet-500'],
            ['key' => 'locked', 'value' => PayrollRun::query()->where('status', 'locked')->count(), 'accent' => 'bg-emerald-500'],
            ['key' => 'payslips', 'value' => Payslip::query()->count(), 'accent' => 'bg-amber-400'],
        ];
    }

    /**
     * @return array<int,array{id:int,label:string}>
     */
    #[Computed]
    public function periodOptions(): array
    {
        return $this->periods
            ->map(fn (PayrollPeriod $period): array => ['id' => $period->id, 'label' => $this->periodLabel($period)])
            ->all();
    }

    #[Computed]
    public function activePeriod(): ?PayrollPeriod
    {
        return $this->periods->firstWhere('id', $this->periodFilter) ?? $this->periods->first();
    }

    public function periodLabel(?PayrollPeriod $period): string
    {
        return $period?->starts_on?->translatedFormat('F Y') ?? ($period->code ?? '—');
    }

    /**
     * Ids of the runs the runs tab lists (same filters and cap) — enough for the tab
     * counter and the export fallback without loading the rows twice.
     */
    #[Computed]
    public function runIds(): Collection
    {
        return PayrollRun::query()
            ->when($this->periodFilter, fn ($query) => $query->where('payroll_period_id', $this->periodFilter))
            ->when($this->regimeFilter, fn ($query) => $query->where('regime_id', $this->regimeFilter))
            ->orderByDesc('id')
            ->limit(40)
            ->pluck('id');
    }

    /**
     * @return array<string,int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        return [
            'runs' => $this->runIds->count(),
            'payslips' => Payslip::query()->count(),
            'loans' => EmployeeLoan::query()->where('status', 'active')->count(),
        ];
    }

    /**
     * Exports always target the run being looked at, falling back to the newest run of the
     * selected period so the toolbar is usable straight from the overview.
     */
    #[Computed]
    public function exportRunId(): ?int
    {
        return $this->selectedRunId ?? $this->runIds->first();
    }

    #[Computed]
    public function regimeOptions(): array
    {
        return CompensationRegime::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name'])
            ->map(fn (CompensationRegime $r): array => ['id' => $r->id, 'label' => $r->name])
            ->all();
    }

    #[Computed]
    public function periods(): Collection
    {
        return PayrollPeriod::query()->orderByDesc('year')->orderByDesc('month')->limit(24)->get();
    }

    // ----------------------------------------------------------------
    // Periods and runs (header actions)
    // ----------------------------------------------------------------

    public function createPeriod(PayrollPeriodService $service): void
    {
        abort_unless($this->canManage(), 403);

        $data = $this->validate([
            'periodForm.year' => 'required|integer|min:2000|max:2100',
            'periodForm.month' => 'required|integer|min:1|max:12',
        ], attributes: $this->fieldLabels([
            'periodForm.year' => 'year',
            'periodForm.month' => 'month',
        ]))['periodForm'];

        $period = $service->createPeriod((int) $data['year'], (int) $data['month']);

        $this->periodFilter = $period->id;
        $this->closePanel();
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.period_created'));
    }

    public function createRun(PayrollRunService $service): void
    {
        abort_unless($this->canManage(), 403);

        $data = $this->validate([
            'runForm.payroll_period_id' => 'required|exists:payroll_periods,id',
            'runForm.regime_id' => 'nullable|exists:compensation_regimes,id',
            'runForm.run_type' => 'required|in:regular,off_cycle',
        ], attributes: $this->fieldLabels([
            'runForm.payroll_period_id' => 'period',
            'runForm.regime_id' => 'regime',
        ]))['runForm'];

        $run = $service->createRun(
            PayrollPeriod::findOrFail($data['payroll_period_id']),
            $data['regime_id'] ? (int) $data['regime_id'] : null,
            auth()->id(),
            $data['run_type'],
        );

        $this->selectedRunId = $run->id;
        $this->periodFilter = $run->payroll_period_id;
        $this->closePanel();
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.run_created'));
    }

    public function deletePeriod(int $periodId): void
    {
        abort_unless($this->canManage(), 403);

        PayrollPeriod::whereKey($periodId)->delete();
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.deleted'));
    }

    // ----------------------------------------------------------------
    // Navigation and tab events
    // ----------------------------------------------------------------

    public function selectRun(int $runId): void
    {
        $this->selectedRunId = $runId;
        $this->activeTab = 'payslips';
    }

    public function manageLoans(string $tabelNo, string $label): void
    {
        $this->loanTabelNo = $tabelNo;
        $this->loanPersonnelLabel = $label;
        $this->activeTab = 'loans';
    }

    /** A tab changed data — re-render so the counters follow. */
    #[On('payroll-updated')]
    public function refreshSummary(): void {}

    #[On('payroll-run-focused')]
    public function focusRun(int $runId): void
    {
        $this->selectedRunId = $runId;
    }

    #[On('payroll-run-deleted')]
    public function forgetRun(int $runId): void
    {
        if ($this->selectedRunId === $runId) {
            $this->selectedRunId = null;
        }
    }

    public function render(): View
    {
        return view('payroll::livewire.dashboard');
    }
}
