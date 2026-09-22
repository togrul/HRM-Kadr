<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Models\EmployeeCompensation;
use App\Models\EmployeeLoan;
use App\Models\PayrollRun;
use App\Models\PayslipLine;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

/**
 * Runs of the selected period with their lifecycle actions, plus the period's statutory
 * split and the active loans overview. Filters and the focused run belong to the shell.
 */
class RunsTab extends PayrollTab
{
    #[Reactive]
    public ?int $periodFilter = null;

    #[Reactive]
    public ?int $regimeFilter = null;

    #[Reactive]
    public ?int $selectedRunId = null;

    /** The period the shell resolved from the filter (falls back to the newest one). */
    #[Reactive]
    public ?int $periodId = null;

    #[Reactive]
    public string $activePeriodLabel = '—';

    #[Reactive]
    public ?string $periodCurrency = null;

    #[Reactive]
    public int $activeLoanCount = 0;

    protected function viewName(): string
    {
        return 'runs';
    }

    #[Computed]
    public function runs(): Collection
    {
        return PayrollRun::query()
            ->with(['period', 'regime'])
            ->when($this->periodFilter, fn ($query) => $query->where('payroll_period_id', $this->periodFilter))
            ->when($this->regimeFilter, fn ($query) => $query->where('regime_id', $this->regimeFilter))
            ->orderByDesc('id')
            ->limit(40)
            ->get();
    }

    #[Computed]
    public function forecastBaseTotal(): float
    {
        return (float) EmployeeCompensation::query()->where('status', 'active')->sum('base_amount');
    }

    /**
     * Statutory deductions of the selected period, biggest first — the legal split the
     * finance team reconciles before a run is locked.
     *
     * @return array<int,array{label:string,amount:float,pct:float}>
     */
    #[Computed]
    public function statutoryTotals(): array
    {
        if (! $this->periodId) {
            return [];
        }

        $rows = PayslipLine::query()
            ->join('payslips', 'payslips.id', '=', 'payslip_lines.payslip_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->where('payroll_runs.payroll_period_id', $this->periodId)
            ->where('payslip_lines.is_statutory', true)
            ->where('payslip_lines.kind', 'deduction')
            ->groupBy('payslip_lines.code')
            ->orderByDesc('total')
            ->limit(6)
            ->get([DB::raw('payslip_lines.code as code'), DB::raw('sum(payslip_lines.amount) as total')]);

        $max = (float) $rows->max('total');

        return $rows->map(fn (PayslipLine $row): array => [
            'label' => __('payroll::dashboard.statutory.'.preg_replace('/_(ee|er)$/', '', (string) $row->code)),
            'amount' => (float) $row->getAttribute('total'),
            'pct' => $max > 0 ? round((float) $row->getAttribute('total') / $max * 100, 1) : 0.0,
        ])->all();
    }

    #[Computed]
    public function activeLoans(): Collection
    {
        return EmployeeLoan::query()
            ->where('status', 'active')
            ->with('personnel:tabel_no,surname,name')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    public function calculateRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canManage(), 403);

        $service->calculate(PayrollRun::findOrFail($runId));
        $this->dispatch('payroll-run-focused', runId: $runId);
        $this->announce('calculated');
    }

    public function approveRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canApprove(), 403);

        $service->approve(PayrollRun::findOrFail($runId));
        $this->announce('approved');
    }

    public function lockRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canLock(), 403);

        try {
            $service->lock(PayrollRun::findOrFail($runId));
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first());

            return;
        }

        $this->announce('locked');
    }

    public function reopenRun(int $runId, PayrollRunService $service): void
    {
        abort_unless($this->canLock(), 403);

        $service->reopen(PayrollRun::findOrFail($runId));
        $this->announce('reopened');
    }

    public function deleteRun(int $runId): void
    {
        abort_unless($this->canManage(), 403);

        PayrollRun::whereKey($runId)->delete();

        $this->dispatch('payroll-run-deleted', runId: $runId);
        $this->announce('deleted');
    }
}
