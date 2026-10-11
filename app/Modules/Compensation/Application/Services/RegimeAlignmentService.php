<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Services\StructureScope;
use App\Support\Database\InstalledTables;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rejimi şirkət standartından fərqli olan işçilər və onların toplu düzəlişi
 * (docs/payroll-legal-basis.md §8.3).
 *
 * Düzəliş mövcud təyinatı DƏYİŞDİRMİR: seçilmiş tarixdən yeni effektiv təyinat yaradılır (eyni
 * baza, valyuta, komponent sətirləri — yalnız rejim fərqli), köhnəsi bir gün əvvəl bağlanır. Tarix
 * ayın 1-i olmalıdır və işçinin son kilidlənmiş (ödənilmiş) ayından sonra gəlməlidir — kilidli
 * aylar köhnə rejimlə qalır, retro mühərriki onları yenidən hesablayanda da köhnə təyinatı görür.
 * Keçmiş aylar üzrə vergi/sığorta fərqi «Vergi düzəliş hesabatı» ilə görünür (Payroll).
 */
class RegimeAlignmentService
{
    public function __construct(
        private readonly CompanySectorSettings $sector,
        private readonly CompensationService $compensation,
    ) {}

    /**
     * Bu gün aktiv təyinatı standart rejimdən fərqli olan işçilər.
     *
     * @return list<array{tabel_no: string, full_name: string, compensation_id: int, regime_id: int|null, regime_name: string, effective_from: string|null, earliest_date: string}>
     */
    public function mismatches(?StructureScope $scope = null, ?string $on = null): array
    {
        $defaultId = $this->sector->defaultRegimeId();

        if ($defaultId === null) {
            return [];
        }

        $on ??= now()->toDateString();

        $query = EmployeeCompensation::query()
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->where(fn ($q) => $q->whereNull('regime_id')->orWhere('regime_id', '!=', $defaultId))
            ->with('regime:id,name')
            ->orderBy('tabel_no');

        if ($scope !== null) {
            $query = $scope->constrainThrough($query, 'personnel');
        }

        $rows = $query->get();
        $names = $this->names($rows->pluck('tabel_no')->map(fn ($no): string => (string) $no)->all());
        $locked = $this->lastLockedPeriodEnds($rows->pluck('tabel_no')->map(fn ($no): string => (string) $no)->all());

        $result = [];

        foreach ($rows->unique('tabel_no') as $row) {
            $result[] = [
                'tabel_no' => (string) $row->tabel_no,
                'full_name' => $names[(string) $row->tabel_no] ?? (string) $row->tabel_no,
                'compensation_id' => (int) $row->getKey(),
                'regime_id' => $row->regime_id === null ? null : (int) $row->regime_id,
                'regime_name' => (string) ($row->regime->name ?? '—'),
                'effective_from' => $this->dateString($row->getAttribute('effective_from')),
                'earliest_date' => $this->earliestDate($locked[(string) $row->tabel_no] ?? null, $on),
            ];
        }

        return $result;
    }

    /**
     * Seçilmiş işçilərin rejimini $effectiveFrom tarixindən hədəf rejimə (standart: şirkət
     * standartı) keçirir. Hər işçi ayrıca yoxlanır; keçə bilməyən işçi səbəbi ilə `skipped`-ə düşür.
     *
     * @param  list<string>  $tabelNos
     * @return array{done: list<string>, skipped: array<string, string>}
     *
     * @throws ValidationException
     */
    public function realign(array $tabelNos, string $effectiveFrom, string $reason, ?int $targetRegimeId = null): array
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['alignReason' => __('compensation::regimes.messages.reason_required')]);
        }

        $date = Carbon::parse($effectiveFrom)->startOfDay();

        if ($date->day !== 1) {
            throw ValidationException::withMessages(['alignDate' => __('compensation::regimes.messages.first_of_month')]);
        }

        $targetRegimeId ??= $this->sector->defaultRegimeId();
        $target = $targetRegimeId === null ? null : CompensationRegime::query()->where('is_active', true)->find($targetRegimeId);

        if ($target === null) {
            throw ValidationException::withMessages(['alignDate' => __('compensation::regimes.messages.no_default_regime')]);
        }

        $tabelNos = array_values(array_unique(array_filter(array_map('strval', $tabelNos), fn (string $no): bool => $no !== '')));
        $locked = $this->lastLockedPeriodEnds($tabelNos);
        $done = [];
        $skipped = [];

        foreach ($tabelNos as $tabelNo) {
            $problem = $this->problemFor($tabelNo, $date, (int) $target->getKey(), $locked[$tabelNo] ?? null);

            if ($problem !== null) {
                $skipped[$tabelNo] = __('compensation::regimes.skip.'.$problem);

                continue;
            }

            DB::transaction(function () use ($tabelNo, $date, $target, $reason): void {
                $current = $this->compensation->currentFor($tabelNo, $date);

                if ($current === null) {
                    return;
                }

                $lines = $current->lines->map(fn (Model $line): array => $line->only(['component_id', 'amount', 'percent', 'note']))->all();
                $new = $this->compensation->assignCompensation($tabelNo, [
                    'regime_id' => (int) $target->getKey(),
                    'pay_grade_id' => $current->getAttribute('pay_grade_id'),
                    'base_amount' => $current->base_amount,
                    'currency' => $current->currency ?? 'AZN',
                    'effective_from' => $date->toDateString(),
                    'order_no' => $current->getAttribute('order_no'),
                    'note' => __('compensation::regimes.note', ['reason' => mb_substr($reason, 0, 1500)]),
                ], $lines);

                activity('compensation')
                    ->causedBy(auth()->user())
                    ->performedOn($new)
                    ->withProperties([
                        'tabel_no' => $tabelNo,
                        'old_compensation_id' => (int) $current->getKey(),
                        'old_regime_id' => $current->regime_id,
                        'new_regime_id' => (int) $target->getKey(),
                        'effective_from' => $date->toDateString(),
                        'reason' => $reason,
                        'company_sector' => $this->sector->sector(),
                    ])
                    ->event('regime_realigned')
                    ->log('regime_realigned');
            });

            $done[] = $tabelNo;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }

    /**
     * Ən tez icazəli tarix: son kilidli ayın bitdiyi günün ertəsi ayın 1-i (kilidli ay yoxdursa —
     * $on tarixinin ayının 1-i).
     */
    public function earliestDate(?string $lastLockedEnd, ?string $on = null): string
    {
        $floor = Carbon::parse($on ?? now()->toDateString())->startOfMonth();

        if ($lastLockedEnd === null) {
            return $floor->toDateString();
        }

        $afterLocked = Carbon::parse($lastLockedEnd)->addDay()->startOfMonth();

        return ($afterLocked->greaterThan($floor) ? $afterLocked : $floor)->toDateString();
    }

    private function problemFor(string $tabelNo, Carbon $date, int $targetRegimeId, ?string $lastLockedEnd): ?string
    {
        if ($lastLockedEnd !== null && $date->lessThanOrEqualTo(Carbon::parse($lastLockedEnd))) {
            return 'locked_period';
        }

        $current = $this->compensation->currentFor($tabelNo, $date);

        if ($current === null) {
            return 'no_compensation';
        }

        if ((int) $current->regime_id === $targetRegimeId) {
            return 'already_aligned';
        }

        if ($current->effective_from !== null && Carbon::parse($current->effective_from)->greaterThanOrEqualTo($date)) {
            return 'starts_later';
        }

        // Tarixdən sonra başlayan başqa aktiv təyinat (gələcək maaş dəyişikliyi) — onu bağlamamaq üçün.
        $future = EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->whereDate('effective_from', '>', $date->toDateString())
            ->exists();

        return $future ? 'future_assignment' : null;
    }

    /**
     * İşçi üzrə kilidlənmiş payslip olan son ayın bitmə tarixi.
     *
     * @param  list<string>  $tabelNos
     * @return array<string, string>
     */
    public function lastLockedPeriodEnds(array $tabelNos): array
    {
        if ($tabelNos === [] || ! InstalledTables::has('payslips') || ! InstalledTables::has('payroll_runs')) {
            return [];
        }

        return DB::table('payslips')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->whereIn('payslips.tabel_no', $tabelNos)
            ->where('payroll_runs.status', 'locked')
            ->groupBy('payslips.tabel_no')
            ->selectRaw('payslips.tabel_no as tabel_no, max(payroll_periods.ends_on) as ends_on')
            ->pluck('ends_on', 'tabel_no')
            ->mapWithKeys(fn ($date, $tabelNo): array => [(string) $tabelNo => substr((string) $date, 0, 10)])
            ->all();
    }

    private function dateString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : null;
    }

    /**
     * @param  list<string>  $tabelNos
     * @return array<string, string>
     */
    private function names(array $tabelNos): array
    {
        if ($tabelNos === [] || ! InstalledTables::has('personnels')) {
            return [];
        }

        return DB::table('personnels')
            ->whereIn('tabel_no', $tabelNos)
            ->get(['tabel_no', 'surname', 'name', 'patronymic'])
            ->mapWithKeys(fn ($row): array => [(string) $row->tabel_no => trim($row->surname.' '.$row->name.' '.$row->patronymic)])
            ->all();
    }
}
