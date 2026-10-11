<?php

namespace App\Modules\Compensation\Infrastructure\Persistence\Eloquent;

use App\Models\EmployeeTaxRelief;
use App\Modules\Compensation\Domain\Contracts\TaxReliefReadRepository;
use App\Support\Database\InstalledTables;

class EloquentTaxReliefReadRepository implements TaxReliefReadRepository
{
    public function codesFor(string $tabelNo, string $periodStart, string $periodEnd, bool $legal = false): array
    {
        if (! InstalledTables::has('employee_tax_reliefs')) {
            return [];
        }

        return EmployeeTaxRelief::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate($legal ? 'legal_from' : 'payroll_from', '<=', $periodEnd)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $periodStart))
            ->pluck('category')
            ->map(fn ($code): string => (string) $code)
            ->unique()
            ->values()
            ->all();
    }
}
