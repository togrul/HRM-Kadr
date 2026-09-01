<?php

namespace App\Modules\Compensation\Domain\Contracts;

use App\Models\CompensationComponent;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeCompensation;
use Illuminate\Support\Collection;

interface CompensationReadRepository
{
    public function currentCompensation(string $tabelNo, ?string $date = null): ?EmployeeCompensation;

    /**
     * Current base pay for many staff numbers at once, keyed by tabel_no.
     *
     * The per-employee lookup above is fine inside a payslip, but a consumer
     * walking a whole roster (an export, an integration feed) would issue one
     * query per person and blow the module's query budget.
     *
     * @param  list<string>  $tabelNos
     * @return Collection<string, float>
     */
    public function baseAmountsFor(array $tabelNos, ?string $date = null): Collection;

    /**
     * Staff numbers (tabel_no) with an active compensation, optionally filtered by regime.
     *
     * @return Collection<int,string>
     */
    public function activeAssignees(?int $regimeId = null, ?string $date = null): Collection;

    /**
     * Resolved component lines (concrete amounts) for the employee.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function componentsFor(string $tabelNo, ?string $date = null): Collection;

    public function primaryBankAccount(string $tabelNo): ?EmployeeBankAccount;

    /**
     * Effective statutory rate rows for a regime on a date (regime-specific overrides the null-regime default).
     *
     * @return Collection<int,\App\Models\StatutoryRate>
     */
    public function statutoryRatesFor(?int $regimeId = null, ?string $date = null): Collection;

    /**
     * @return Collection<int,CompensationComponent>
     */
    public function componentCatalog(): Collection;
}
