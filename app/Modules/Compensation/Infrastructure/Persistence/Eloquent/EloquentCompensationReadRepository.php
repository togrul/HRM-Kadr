<?php

namespace App\Modules\Compensation\Infrastructure\Persistence\Eloquent;

use App\Models\CompensationComponent;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeCompensation;
use App\Modules\Compensation\Application\Services\AllowanceResolver;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentCompensationReadRepository implements CompensationReadRepository
{
    public function __construct(
        private readonly CompensationService $compensationService,
        private readonly AllowanceResolver $allowanceResolver,
    ) {}

    public function currentCompensation(string $tabelNo, ?string $date = null): ?EmployeeCompensation
    {
        return $this->compensationService->currentFor($tabelNo, $date ? Carbon::parse($date) : null);
    }

    public function baseAmountsFor(array $tabelNos, ?string $date = null): Collection
    {
        $tabelNos = array_values(array_filter(array_map('trim', $tabelNos), static fn (string $v): bool => $v !== ''));

        if ($tabelNos === []) {
            return collect();
        }

        $on = ($date ? Carbon::parse($date) : now())->toDateString();

        // Newest effective row wins per employee; `keyBy` on an ascending sort
        // therefore leaves the latest one in place.
        return EmployeeCompensation::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get(['tabel_no', 'base_amount'])
            ->keyBy('tabel_no')
            ->map(fn (EmployeeCompensation $row): float => (float) $row->base_amount);
    }

    public function activeAssignees(?int $regimeId = null, ?string $date = null): Collection
    {
        $on = ($date ? Carbon::parse($date) : now())->toDateString();

        return EmployeeCompensation::query()
            ->where('status', 'active')
            ->when($regimeId, fn ($q) => $q->where('regime_id', $regimeId))
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->orderBy('tabel_no')
            ->pluck('tabel_no')
            ->unique()
            ->values();
    }

    public function componentsFor(string $tabelNo, ?string $date = null): Collection
    {
        return $this->allowanceResolver->resolve($tabelNo, $date ? Carbon::parse($date) : null);
    }

    public function primaryBankAccount(string $tabelNo): ?EmployeeBankAccount
    {
        return EmployeeBankAccount::query()
            ->where('tabel_no', $tabelNo)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();
    }

    public function statutoryRatesFor(?int $regimeId = null, ?string $date = null): Collection
    {
        $on = ($date ? Carbon::parse($date) : now())->toDateString();

        return \App\Models\StatutoryRate::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->where(fn ($q) => $q->whereNull('regime_id')->when($regimeId, fn ($qq) => $qq->orWhere('regime_id', $regimeId)))
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy(fn ($rate) => $rate->component_code.'|'.$rate->payer)
            ->map(fn ($group) => $group->sortByDesc(fn ($rate) => $rate->regime_id === $regimeId ? 1 : 0)->first())
            ->values();
    }

    public function componentCatalog(): Collection
    {
        return CompensationComponent::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get();
    }
}
