<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Application\Services\RetroService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Payslips of the run focused in the shell, with one payslip's line detail. The shell
 * re-mounts this tab whenever the focused run changes.
 *
 * @property-read \App\Models\Payslip|null $selectedPayslip
 */
class PayslipsTab extends PayrollTab
{
    #[Locked]
    public ?int $runId = null;

    #[Locked]
    public ?int $selectedPayslipId = null;

    protected function viewName(): string
    {
        return 'payslips';
    }

    #[Computed]
    public function selectedRun(): ?PayrollRun
    {
        if (! $this->runId) {
            return null;
        }

        return PayrollRun::query()->with(['period', 'regime'])->find($this->runId);
    }

    #[Computed]
    public function runPayslips(): Collection
    {
        if (! $this->runId) {
            return collect();
        }

        return $this->scopeByPersonnel(Payslip::query())
            ->where('payroll_run_id', $this->runId)
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

        return $this->scopeByPersonnel(Payslip::query())->with(['lines', 'personnel:tabel_no,surname,name'])->find($this->selectedPayslipId);
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

        return app(RetroService::class)->pendingRetro($payslip->tabel_no);
    }

    public function viewPayslip(int $payslipId): void
    {
        abort_unless($this->scopeByPersonnel(Payslip::query())->whereKey($payslipId)->exists(), 403);

        $this->selectedPayslipId = $payslipId;
    }

    public function closePayslip(): void
    {
        $this->selectedPayslipId = null;
    }

    public function deletePayslip(int $payslipId, PayrollRunService $service): void
    {
        abort_unless($this->canManage(), 403);

        if (! $this->attempt(fn () => $service->deletePayslip($this->scopeByPersonnel(Payslip::query())->with('run')->findOrFail($payslipId)))) {
            return;
        }

        if ($this->selectedPayslipId === $payslipId) {
            $this->selectedPayslipId = null;
        }

        $this->announce('deleted');
    }
}
