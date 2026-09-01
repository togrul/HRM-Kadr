<?php

namespace App\Modules\Payroll\Infrastructure\Persistence\Eloquent;

use App\Models\Payslip;
use App\Modules\Payroll\Domain\Contracts\PayslipReadRepository;
use Illuminate\Support\Collection;

class EloquentPayslipReadRepository implements PayslipReadRepository
{
    public function lockedPayslipsFor(string $tabelNo): Collection
    {
        return Payslip::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'locked')
            ->with('run.period')
            ->orderByDesc('id')
            ->get();
    }

    public function payslipFor(int $id, string $tabelNo): ?Payslip
    {
        return Payslip::query()
            ->where('id', $id)
            ->where('tabel_no', $tabelNo)
            ->where('status', 'locked')
            ->with(['lines', 'run.period'])
            ->first();
    }
}
