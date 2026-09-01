<?php

namespace App\Modules\Payroll\Domain\Contracts;

use App\Models\Payslip;
use Illuminate\Support\Collection;

interface PayslipReadRepository
{
    /**
     * Locked (officially paid) payslips for an employee, newest first.
     *
     * @return Collection<int,Payslip>
     */
    public function lockedPayslipsFor(string $tabelNo): Collection;

    /**
     * A single locked payslip scoped to the employee (null if it isn't theirs).
     */
    public function payslipFor(int $id, string $tabelNo): ?Payslip;
}
