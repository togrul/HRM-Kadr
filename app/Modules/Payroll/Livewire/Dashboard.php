<?php

namespace App\Modules\Payroll\Livewire;

use App\Models\CompensationRegime;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Modules\Payroll\Application\Services\LoanService;
use App\Modules\Payroll\Application\Services\PayrollExportService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Support\Livewire\DownloadsReportsTable;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Dashboard extends Component
{
    use DownloadsReportsTable;
    use InteractsWithTabbedWorkspace;

    public string $activeTab = 'runs';

    public array $periodForm = [
        'year' => null,
        'month' => null,
    ];

    public array $runForm = [
        'payroll_period_id' => null,
        'regime_id' => null,
        'run_type' => 'regular',
    ];

    public ?int $selectedRunId = null;

    public ?int $selectedPayslipId = null;

    // --- Loans tab ---
    public string $personnelSearch = '';

    public ?string $selectedTabelNo = null;

    public ?string $selectedPersonnelLabel = null;

    public array $loanForm = [
        'type' => 'loan',
        'principal' => '',
        'monthly_installment' => '',
        'currency' => 'AZN',
        'start_on' => '',
        'note' => '',
    ];

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
        $this->periodForm['year'] = (int) now()->format('Y');
        $this->periodForm['month'] = (int) now()->format('m');
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

    public function canApprove(): bool
    {
        return auth()->user()?->can('approve-payroll') ?? false;
    }

    public function canLock(): bool
    {
        return auth()->user()?->can('lock-payroll') ?? false;
    }

    public function canViewAmounts(): bool
    {
        return auth()->user()?->can('view-compensation-amounts') ?? false;
    }

    public function canExport(): bool
    {
        return auth()->user()?->can('export-payroll') ?? false;
    }

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

    /**
     * @param  array<string,string>  $map
     * @return array<string,string>
     */
    protected function fieldLabels(array $map): array
    {
        $labels = [];

        foreach ($map as $path => $key) {
            $labels[$path] = __('payroll::dashboard.fields.'.$key);
        }

        return $labels;
    }

    #[Computed]
    public function forecastBaseTotal(): float
    {
        return (float) \App\Models\EmployeeCompensation::query()->where('status', 'active')->sum('base_amount');
    }

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

    #[Computed]
    public function runs(): Collection
    {
        return PayrollRun::query()
            ->with(['period', 'regime'])
            ->orderByDesc('id')
            ->limit(40)
            ->get();
    }

    #[Computed]
    public function selectedRun(): ?PayrollRun
    {
        if (! $this->selectedRunId) {
            return null;
        }

        return PayrollRun::query()->with(['period', 'regime'])->find($this->selectedRunId);
    }

    #[Computed]
    public function runPayslips(): Collection
    {
        if (! $this->selectedRunId) {
            return collect();
        }

        return Payslip::query()
            ->where('payroll_run_id', $this->selectedRunId)
            ->with('personnel:tabel_no,surname,name')
            ->orderBy('tabel_no')
            ->get();
    }

    #[Computed]
    public function selectedPayslip(): ?Payslip
    {
        if (! $this->selectedPayslipId) {
            return null;
        }

        return Payslip::query()->with(['lines', 'personnel:tabel_no,surname,name'])->find($this->selectedPayslipId);
    }

    /**
     * @return array{lines:array<int,array<string,mixed>>,total:float}
     */
    #[Computed]
    public function retro(): array
    {
        $payslip = $this->selectedPayslip;

        if (! $payslip) {
            return ['lines' => [], 'total' => 0.0];
        }

        return app(\App\Modules\Payroll\Application\Services\RetroService::class)->pendingRetro($payslip->tabel_no);
    }

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

        $service->createPeriod((int) $data['year'], (int) $data['month']);

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
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.run_created'));
    }

    public function calculateRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canManage(), 403);

        $service->calculate(PayrollRun::findOrFail($runId));
        $this->selectedRunId = $runId;
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.calculated'));
    }

    public function approveRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canApprove(), 403);

        $service->approve(PayrollRun::findOrFail($runId));
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.approved'));
    }

    public function lockRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canLock(), 403);

        $service->lock(PayrollRun::findOrFail($runId));
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.locked'));
    }

    public function reopenRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canLock(), 403);

        $service->reopen(PayrollRun::findOrFail($runId));
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.reopened'));
    }

    public function deletePeriod(int $periodId): void
    {
        abort_unless($this->canManage(), 403);

        PayrollPeriod::whereKey($periodId)->delete();
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.deleted'));
    }

    public function deleteRun(int $runId): void
    {
        abort_unless($this->canManage(), 403);

        PayrollRun::whereKey($runId)->delete();

        if ($this->selectedRunId === $runId) {
            $this->selectedRunId = null;
            $this->selectedPayslipId = null;
        }

        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.deleted'));
    }

    public function deletePayslip(int $payslipId): void
    {
        abort_unless($this->canManage(), 403);

        $payslip = Payslip::with('run')->findOrFail($payslipId);
        abort_if($payslip->run?->isLocked(), 422);

        $payslip->delete();

        if ($this->selectedPayslipId === $payslipId) {
            $this->selectedPayslipId = null;
        }

        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.deleted'));
    }

    public function selectRun(int $runId): void
    {
        $this->selectedRunId = $runId;
        $this->selectedPayslipId = null;
        $this->activeTab = 'payslips';
    }

    public function viewPayslip(int $payslipId): void
    {
        $this->selectedPayslipId = $payslipId;
    }

    public function closePayslip(): void
    {
        $this->selectedPayslipId = null;
    }

    // ----------------------------------------------------------------
    // Loans / advances
    // ----------------------------------------------------------------

    #[Computed]
    public function personnelResults(): array
    {
        $term = trim($this->personnelSearch);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Personnel::query()
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('tabel_no', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['tabel_no', 'surname', 'name'])
            ->map(fn (Personnel $p): array => [
                'tabel_no' => $p->tabel_no,
                'label' => trim("{$p->tabel_no} — {$p->surname} {$p->name}"),
            ])
            ->all();
    }

    public function selectPersonnel(string $tabelNo, string $label): void
    {
        $this->selectedTabelNo = $tabelNo;
        $this->selectedPersonnelLabel = $label;
        $this->personnelSearch = '';
    }

    public function clearPersonnel(): void
    {
        $this->selectedTabelNo = null;
        $this->selectedPersonnelLabel = null;
    }

    #[Computed]
    public function loans(): Collection
    {
        if (! $this->selectedTabelNo) {
            return collect();
        }

        return EmployeeLoan::query()
            ->where('tabel_no', $this->selectedTabelNo)
            ->orderByDesc('id')
            ->get();
    }

    public function saveLoan(LoanService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->selectedTabelNo !== null, 422);

        $data = $this->validate([
            'loanForm.type' => 'required|in:loan,advance',
            'loanForm.principal' => 'required|numeric|min:0.01',
            'loanForm.monthly_installment' => 'required|numeric|min:0.01',
            'loanForm.currency' => 'required|string|size:3',
            'loanForm.start_on' => 'required|date',
            'loanForm.note' => 'nullable|string|max:2000',
        ], attributes: $this->fieldLabels([
            'loanForm.type' => 'loan_type',
            'loanForm.principal' => 'principal',
            'loanForm.monthly_installment' => 'monthly_installment',
            'loanForm.currency' => 'currency',
            'loanForm.start_on' => 'start_on',
        ]))['loanForm'];

        $service->createLoan($this->selectedTabelNo, $data);

        $this->loanForm = ['type' => 'loan', 'principal' => '', 'monthly_installment' => '', 'currency' => 'AZN', 'start_on' => '', 'note' => ''];
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.saved'));
    }

    public function deleteLoan(int $loanId): void
    {
        abort_unless($this->canManage(), 403);
        EmployeeLoan::whereKey($loanId)->delete();
        $this->dispatch('notify', type: 'success', message: __('payroll::dashboard.messages.deleted'));
    }

    public function render(): View
    {
        return view('payroll::livewire.dashboard');
    }
}
