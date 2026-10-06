<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use App\Models\PayrollOneOffEarning;
use App\Models\PayrollRun;
use App\Modules\Integration\Domain\Contracts\PayrollOwnership;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use App\Services\Modules\ModuleState;
use App\Support\Database\InstalledTables;

class EloquentPayrollOneOffEarnings implements PayrollOneOffEarnings
{
    public function __construct(
        private readonly ModuleState $modules,
        private readonly PayrollOwnership $ownership,
    ) {}

    public function record(string $tabelNo, string $code, string $name, float $amount, int $year, int $month, string $sourceKey, bool $taxable = true, bool $affectsSocial = true): bool
    {
        if (! $this->modules->enabled('payroll') || ! $this->ownership->isOurs()) {
            return false;
        }

        // A month whose regular run is already locked is closed: the line moves on to the next one.
        while ($this->monthLocked($year, $month)) {
            [$year, $month] = $month === 12 ? [$year + 1, 1] : [$year, $month + 1];
        }

        $line = PayrollOneOffEarning::query()->firstOrNew(['source_key' => $sourceKey]);

        if ($line->paid_payroll_run_id === null) {
            $line->fill([
                'tabel_no' => $tabelNo,
                'code' => $code,
                'name' => $name,
                'amount' => round($amount, 2),
                'pay_year' => $year,
                'pay_month' => $month,
                'taxable' => $taxable,
                'affects_social' => $affectsSocial,
            ])->save();
        }

        return true;
    }

    private function monthLocked(int $year, int $month): bool
    {
        return PayrollRun::query()
            ->where('run_type', 'regular')
            ->where('status', 'locked')
            ->whereHas('period', fn ($query) => $query->where('year', $year)->where('month', $month))
            ->exists();
    }

    public function withdraw(string $sourceKey): void
    {
        if (! InstalledTables::has('payroll_one_off_earnings')) {
            return;
        }

        PayrollOneOffEarning::query()->where('source_key', $sourceKey)->whereNull('paid_payroll_run_id')->delete();
    }
}
