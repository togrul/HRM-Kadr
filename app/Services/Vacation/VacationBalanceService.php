<?php

namespace App\Services\Vacation;

use App\Models\Personnel;
use App\Models\VacationBalanceEntry;
use App\Models\VacationWorkYear;
use App\Modules\Vacation\Application\Services\EntitlementBreakdown;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Application\Services\WorkYearBalance;
use App\Modules\Vacation\Application\Services\WorkYearCalendar;
use App\Modules\Vacation\Application\Services\WorkYearPeriod;
use App\Services\Vacation\Entitlement\CivilEntitlementStrategy;
use App\Services\Vacation\Entitlement\EntitlementStrategy;
use App\Services\Vacation\Entitlement\RankedEntitlementStrategy;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * İşçinin əmək məzuniyyəti balansı İŞ İLİ üzrə (ƏM m.113.3, 131–138): hər iş ili üçün hüquq
 * (strategiya: mülki — normalar cədvəli, rütbəli — rütbə kateqoriyası) və hərəkətlər jurnalı
 * (açılış qalığı, əmrlə istifadə, geri çağırma, kompensasiya, düzəliş). İstifadə ən köhnə
 * iş ilindən başlayaraq bölüşdürülür; istifadə olunmamış qalıq sonrakı illərə keçir və yanmır
 * (m.134–135, 2006-cı ildən il həddi yoxdur).
 *
 * Uçotun başlama tarixindən (VacationSettings::LEDGER_START) əvvəl başlamış iş illəri avtomatik
 * hesablanmır — onların qalığı köhnə təqvim ili balansından (LegacyVacationMigrator) və ya
 * admin tərəfindən daxil edilən açılış qalığı ilə gəlir.
 *
 * Əmr axını: (a) balansı göstərir, (b) qalıqdan çox məzuniyyəti bloklayır, (c) təsdiqdə günləri
 * çıxır, geri alınanda həmin sənədin hərəkətlərini silir. Əvvəlki təqvim ili API-si
 * (snapshot / consume / release) geriyə uyğunluq üçün qalır.
 */
class VacationBalanceService
{
    public function __construct(
        private readonly WorkYearCalendar $calendar,
        private readonly VacationSettings $settings,
        private readonly CivilEntitlementStrategy $civil,
        private readonly RankedEntitlementStrategy $ranked,
        private readonly OrderLeaveFacts $orderFacts,
    ) {}

    public function strategyFor(Personnel $personnel): EntitlementStrategy
    {
        return $this->ranked->applies($personnel) ? $this->ranked : $this->civil;
    }

    /**
     * İllik hüquq (təqvim günü) $asOf tarixinə: rütbəli heyət üçün əvvəlki qayda, mülki işçi
     * üçün həmin tarixdə davam edən iş ilinin hüququ.
     */
    public function entitlementDays(Personnel $personnel, ?Carbon $asOf = null): int
    {
        $asOf ??= Carbon::now();

        if ($this->ranked->applies($personnel)) {
            return $this->ranked->days($personnel, $asOf);
        }

        return $this->entitlementBreakdown($personnel, $asOf)?->total() ?? 0;
    }

    /**
     * $asOf tarixində davam edən iş ilinin hüququnun tərkibi (işə qəbul tarixi yoxdursa null).
     */
    public function entitlementBreakdown(Personnel $personnel, ?CarbonInterface $asOf = null): ?EntitlementBreakdown
    {
        $this->civil->forget();
        $asOf = CarbonImmutable::parse(($asOf ?? Carbon::now())->toDateString());
        $periods = $this->periodsFor($personnel, $asOf);
        $period = end($periods) ?: null;

        if ($period === null) {
            return null;
        }

        return $this->strategyFor($personnel)->entitlement($personnel, $period, $asOf);
    }

    /**
     * $asOf tarixinədək başlamış iş illəri (xitam tarixindən sonra başlayanlar xaric).
     *
     * @return list<WorkYearPeriod>
     */
    public function periodsFor(Personnel $personnel, CarbonInterface $asOf): array
    {
        [$join, $leave] = $this->employmentDates($personnel);

        if ($join === null) {
            return [];
        }

        $until = CarbonImmutable::parse($asOf->toDateString());

        if ($leave !== null) {
            $until = $leave->lt($until) ? $leave : $until;
        }

        return $this->calendar->periods($join, $until, $this->excludedPeriods($personnel));
    }

    /**
     * İşçinin açıq iş illərinin balansı $asOf tarixinə (yazmır).
     *
     * @return list<WorkYearBalance>
     */
    public function workYears(Personnel $personnel, ?CarbonInterface $asOf = null): array
    {
        $this->civil->forget();
        $asOf = CarbonImmutable::parse(($asOf ?? Carbon::now())->toDateString());

        return $this->buildBalances($personnel, $asOf);
    }

    /**
     * $date tarixinə məzuniyyət balansı: istifadə oluna bilən iş illəri üzrə cəm və hər iş ili.
     *
     * @return array{total:int,used:int,remaining:int,work_years:list<array<string,mixed>>,next_available_from:?string}
     */
    public function balanceOn(Personnel $personnel, CarbonInterface $date, bool $persist = false): array
    {
        $this->civil->forget();
        $on = CarbonImmutable::parse($date->toDateString());

        if ($persist) {
            $this->materialize($personnel, $on);
        }

        return $this->aggregate($this->buildBalances($personnel, $on), $on);
    }

    /**
     * İl üzrə balans ['total', 'used', 'remaining'] (+ iş illəri) — cari il üçün bu günə, digər
     * illər üçün ilin sonuna. İş illərinin sətirlərini yaradır.
     *
     * @return array{total:int,used:int,remaining:int,work_years:list<array<string,mixed>>,next_available_from:?string}
     */
    public function snapshot(Personnel $personnel, int $year): array
    {
        return $this->balanceOn($personnel, $this->yearDate($year), persist: true);
    }

    /**
     * Saxlanmış balans və ya işçi üçün hələ heç bir iş ili yazılmayıbsa null. Yalnız oxuyur.
     *
     * @return array{total:int,used:int,remaining:int,work_years:list<array<string,mixed>>,next_available_from:?string}|null
     */
    public function storedSnapshot(Personnel $personnel, int $year): ?array
    {
        if (! VacationWorkYear::query()->where('tabel_no', $personnel->tabel_no)->exists()) {
            return null;
        }

        return $this->balanceOn($personnel, $this->yearDate($year));
    }

    /**
     * snapshot()-un göstərəcəyi rəqəmlər, heç nə yazmadan.
     *
     * @return array{total:int,used:int,remaining:int,work_years:list<array<string,mixed>>,next_available_from:?string}
     */
    public function previewSnapshot(Personnel $personnel, int $year): array
    {
        return $this->balanceOn($personnel, $this->yearDate($year));
    }

    /**
     * Təsdiqdə günləri balansdan çıxır: əvvəlcə göstərilən iş ili ($preferredWorkYear), sonra ən
     * köhnə istifadə oluna bilən iş ili. Qalıq çatmırsa artıq hissə sonuncu iş ilinə yazılır.
     */
    public function consume(Personnel $personnel, int $year, int $days, ?string $source = null, ?CarbonInterface $on = null, ?CarbonInterface $preferredWorkYear = null): void
    {
        if ($days <= 0) {
            return;
        }

        $this->allocate($personnel, $days, VacationBalanceEntry::KIND_USAGE, $source, $on ?? $this->yearDate($year), $preferredWorkYear, requireAvailable: true);
    }

    /**
     * Günləri balansa qaytarır (ləğv / geri alma): $source verilibsə həmin sənədin hərəkətləri
     * silinir; yoxdursa (uçotdan əvvəlki sənəd) ən yeni iş illərindən başlayaraq düzəliş yazılır.
     */
    public function release(Personnel $personnel, int $year, int $days, ?string $source = null): void
    {
        if ($source !== null && $this->reverseSource($source) > 0) {
            return;
        }

        if ($days <= 0) {
            return;
        }

        $this->giveBack($personnel, $days, VacationBalanceEntry::KIND_ADJUSTMENT, $source, $this->yearDate($year), null);
    }

    /**
     * Geri çağırmada istifadə olunmamış günləri qaytarır — məzuniyyəti verən əmrin ($fromSource)
     * çıxdığı iş illərinə, sonuncu bölüşdürülmüş hissədən başlayaraq.
     */
    public function returnDays(Personnel $personnel, int $days, string $source, ?string $fromSource, CarbonInterface $on): void
    {
        if ($days <= 0) {
            return;
        }

        $this->giveBack($personnel, $days, VacationBalanceEntry::KIND_RECALL, $source, $on, $fromSource);
    }

    /**
     * İstifadə olunmamış məzuniyyətə görə kompensasiya: günlər ən köhnə iş ilindən (və ya göstərilən
     * iş ilindən) çıxılır. ƏM m.144.2 — xitamda bütün iş illəri üzrə; işləmə dövründə yalnız qurum
     * ayarı icazə verirsə.
     *
     * @throws DomainException kompensasiya hüquqi əsassızdırsa
     */
    public function compensate(Personnel $personnel, int $days, string $source, CarbonInterface $on, ?CarbonInterface $preferredWorkYear = null): void
    {
        if ($days <= 0) {
            return;
        }

        if (! $this->compensationAllowed($personnel)) {
            throw new DomainException(__('vacation::norms.errors.compensation_requires_termination'));
        }

        $this->allocate($personnel, $days, VacationBalanceEntry::KIND_COMPENSATION, $source, $on, $preferredWorkYear, requireAvailable: false);
    }

    /**
     * Pul kompensasiyası yalnız əmək müqaviləsinə xitam verildikdə (ƏM m.144.2) və ya qurum
     * işləmə dövründə kompensasiyaya icazə veribsə.
     */
    public function compensationAllowed(Personnel $personnel): bool
    {
        return $this->employmentDates($personnel)[1] !== null || $this->settings->compensationWithoutTermination();
    }

    /** Sənədin ($source) yazdığı bütün hərəkətləri silir; silinən sayı qaytarır. */
    public function reverseSource(string $source): int
    {
        return VacationBalanceEntry::query()->where('source', $source)->delete();
    }

    /**
     * Açılış qalığı (köhnə məlumatın köçürülməsi): iş ilinə müsbət hərəkət. Uçotdan əvvəlki iş ili
     * üçün də yazıla bilər — həmin iş ili «açılış» sətri kimi yaradılır. Redaktə olunmur:
     * səhvdirsə silinib yenidən daxil edilir.
     */
    public function addOpening(Personnel $personnel, int $sequence, int $days, ?string $note = null, ?int $userId = null): VacationBalanceEntry
    {
        if ($days < 1 || $days > 366) {
            throw new DomainException(__('vacation::norms.errors.opening_days'));
        }

        $period = collect($this->periodsFor($personnel, Carbon::now()))->firstWhere('sequence', $sequence);

        if (! $period instanceof WorkYearPeriod) {
            throw new DomainException(__('vacation::norms.errors.opening_work_year'));
        }

        return DB::transaction(function () use ($personnel, $period, $days, $note, $userId): VacationBalanceEntry {
            $rows = $this->materialize($personnel, Carbon::now());
            $row = $rows->get($period->sequence) ?? VacationWorkYear::query()->create([
                'tabel_no' => $personnel->tabel_no,
                'sequence' => $period->sequence,
                'starts_on' => $period->start->toDateString(),
                'ends_on' => $period->end->toDateString(),
                'entitled_days' => 0,
                'breakdown' => null,
                'strategy' => VacationWorkYear::STRATEGY_OPENING,
            ]);

            return VacationBalanceEntry::query()->create([
                'work_year_id' => $row->id,
                'tabel_no' => $personnel->tabel_no,
                'kind' => VacationBalanceEntry::KIND_OPENING,
                'days' => $days,
                'source' => 'opening',
                'note' => $note,
                'created_by' => $userId,
            ]);
        });
    }

    /** Açılış qalığını silir; boş qalan «açılış» iş ili də silinir. */
    public function deleteOpening(Personnel $personnel, int $entryId): void
    {
        $entry = VacationBalanceEntry::query()
            ->whereKey($entryId)
            ->where('tabel_no', $personnel->tabel_no)
            ->where('kind', VacationBalanceEntry::KIND_OPENING)
            ->first();

        if ($entry === null) {
            return;
        }

        DB::transaction(function () use ($entry): void {
            $row = VacationWorkYear::query()->find($entry->work_year_id);
            $entry->delete();

            if ($row && $row->strategy === VacationWorkYear::STRATEGY_OPENING && ! $row->entries()->exists()) {
                $row->delete();
            }
        });
    }

    /**
     * İşçinin açılış qalıqları (iş ili etiketi ilə), kartda siyahı üçün.
     *
     * @return Collection<int, VacationBalanceEntry>
     */
    public function openingEntries(Personnel $personnel): Collection
    {
        return VacationBalanceEntry::query()
            ->with('workYear:id,sequence,starts_on,ends_on', 'author:id,name')
            ->where('tabel_no', $personnel->tabel_no)
            ->where('kind', VacationBalanceEntry::KIND_OPENING)
            ->orderBy('id')
            ->get();
    }

    /**
     * $asOf tarixinədək açıq iş illərinin sətirlərini yaradır və davam edən iş illərinin hüququnu
     * yeniləyir (bitmiş iş ilinin hüququ dəyişmir).
     *
     * @return Collection<int, VacationWorkYear> sequence => sətir
     */
    public function materialize(Personnel $personnel, CarbonInterface $asOf): Collection
    {
        $this->civil->forget();
        $balances = $this->buildBalances($personnel, CarbonImmutable::parse($asOf->toDateString()));
        $rows = VacationWorkYear::query()->where('tabel_no', $personnel->tabel_no)->get()->keyBy('sequence');
        $today = Carbon::today()->toDateString();

        foreach ($balances as $balance) {
            $row = $rows->get($balance->period->sequence);
            $values = [
                'starts_on' => $balance->period->start->toDateString(),
                'ends_on' => $balance->period->end->toDateString(),
                'entitled_days' => $balance->entitled,
                'breakdown' => $balance->breakdown->toArray(),
                'strategy' => $balance->strategy,
            ];

            if ($row === null) {
                $rows->put($balance->period->sequence, VacationWorkYear::query()->create([
                    'tabel_no' => $personnel->tabel_no,
                    'sequence' => $balance->period->sequence,
                    ...$values,
                ]));

                continue;
            }

            if ($row->isImported() || $row->ends_on->toDateString() < $today) {
                continue;
            }

            $row->fill($values);

            if ($row->isDirty()) {
                $row->save();
            }
        }

        return $rows;
    }

    /**
     * Davam edən və bitmiş (hesablanan) iş illərinin hüququnu normalara görə yenidən hesablayır —
     * köçürülmüş (köhnə / açılış) iş illərinə toxunmur.
     */
    public function recalculate(Personnel $personnel): int
    {
        $this->civil->forget();
        $strategy = $this->strategyFor($personnel);
        $today = CarbonImmutable::today();
        $updated = 0;

        foreach (VacationWorkYear::query()->where('tabel_no', $personnel->tabel_no)->get() as $row) {
            if ($row->isImported()) {
                continue;
            }

            $period = new WorkYearPeriod($row->sequence, CarbonImmutable::parse($row->starts_on->toDateString()), CarbonImmutable::parse($row->ends_on->toDateString()));
            $breakdown = $strategy->entitlement($personnel, $period, $today);
            $row->fill(['entitled_days' => $breakdown->total(), 'breakdown' => $breakdown->toArray(), 'strategy' => $strategy->key()]);

            if ($row->isDirty()) {
                $row->save();
                $updated++;
            }
        }

        return $updated;
    }

    /** İşçinin planlaşdırılmış məzuniyyət ayı (iş ili üzrə). */
    public function reserveMonth(Personnel $personnel, int $sequence, ?int $month): void
    {
        $rows = $this->materialize($personnel, Carbon::now());
        $row = $rows->get($sequence);

        if ($row !== null) {
            $row->forceFill(['reserved_month' => $month ?: null])->save();
        }
    }

    /**
     * @return list<WorkYearBalance>
     */
    private function buildBalances(Personnel $personnel, CarbonImmutable $asOf): array
    {
        $strategy = $this->strategyFor($personnel);
        $ledgerStart = $this->settings->ledgerStart();
        $horizon = $ledgerStart !== null && $ledgerStart->gt($asOf) ? $ledgerStart : $asOf;
        $periods = collect($this->periodsFor($personnel, $horizon))->keyBy('sequence');
        $firstComputed = $this->firstComputedSequence($periods->all(), $ledgerStart);
        $rows = VacationWorkYear::query()
            ->with('entries:id,work_year_id,kind,days')
            ->where('tabel_no', $personnel->tabel_no)
            ->orderBy('sequence')
            ->get()
            ->keyBy('sequence');
        $today = CarbonImmutable::today()->toDateString();
        $balances = [];

        $sequences = $periods->keys()->merge($rows->keys())->unique()->sort()->values();

        foreach ($sequences as $sequence) {
            $period = $periods->get($sequence);
            $row = $rows->get($sequence);

            if ($period === null && $row !== null) {
                $period = new WorkYearPeriod((int) $sequence, CarbonImmutable::parse($row->starts_on->toDateString()), CarbonImmutable::parse($row->ends_on->toDateString()));
            }

            if ($period === null || $period->start->gt($asOf)) {
                continue;
            }

            if ($row === null && $sequence < $firstComputed) {
                continue;
            }

            if ($row !== null && ($row->isImported() || $row->ends_on->toDateString() < $today)) {
                $entitled = (int) $row->entitled_days;
                $breakdown = EntitlementBreakdown::fromArray($row->breakdown, $entitled, $row->strategy);
                $rowStrategy = $row->strategy;
            } elseif ($sequence < $firstComputed) {
                // Uçotdan əvvəlki iş ili yalnız açılış qalığı ilə mövcuddur.
                $entitled = 0;
                $breakdown = new EntitlementBreakdown(0, strategy: VacationWorkYear::STRATEGY_OPENING);
                $rowStrategy = VacationWorkYear::STRATEGY_OPENING;
            } else {
                $breakdown = $strategy->entitlement($personnel, $period, $asOf);
                $entitled = $breakdown->total();
                $rowStrategy = $strategy->key();
            }

            $entries = $row !== null ? $row->entries : collect();
            $sum = fn (string $kind): int => (int) $entries->where('kind', $kind)->sum('days');

            $balances[] = new WorkYearBalance(
                period: $period,
                entitled: $entitled,
                breakdown: $breakdown,
                strategy: $rowStrategy,
                availableFrom: in_array($rowStrategy, [VacationWorkYear::STRATEGY_LEGACY, VacationWorkYear::STRATEGY_OPENING], true)
                    ? $period->start
                    : $strategy->availableFrom($personnel, $period),
                opening: $sum(VacationBalanceEntry::KIND_OPENING),
                used: -($sum(VacationBalanceEntry::KIND_USAGE) + $sum(VacationBalanceEntry::KIND_LEGACY_USAGE)),
                recalled: $sum(VacationBalanceEntry::KIND_RECALL),
                compensated: -$sum(VacationBalanceEntry::KIND_COMPENSATION),
                adjusted: $sum(VacationBalanceEntry::KIND_ADJUSTMENT),
                workYearId: $row?->id,
                reservedMonth: $row?->reserved_month,
            );
        }

        return $balances;
    }

    /**
     * @param  array<int, WorkYearPeriod>  $periods
     */
    private function firstComputedSequence(array $periods, ?CarbonImmutable $ledgerStart): int
    {
        if ($ledgerStart === null) {
            return 1;
        }

        foreach ($periods as $period) {
            if ($period->contains($ledgerStart) || $period->start->gt($ledgerStart)) {
                return $period->sequence;
            }
        }

        // Uçot işçinin son iş ilindən sonra başlayıb (məs. xitam olunmuş işçi).
        return count($periods) + 1;
    }

    /**
     * @param  list<WorkYearBalance>  $balances
     * @return array{total:int,used:int,remaining:int,work_years:list<array<string,mixed>>,next_available_from:?string}
     */
    private function aggregate(array $balances, CarbonImmutable $on): array
    {
        $available = array_values(array_filter($balances, fn (WorkYearBalance $b): bool => $b->isAvailableOn($on)));
        $pending = array_values(array_filter($balances, fn (WorkYearBalance $b): bool => ! $b->isAvailableOn($on)));

        $total = array_sum(array_map(fn (WorkYearBalance $b): int => $b->total(), $available));
        $remaining = max(0, array_sum(array_map(fn (WorkYearBalance $b): int => $b->remaining(), $available)));

        return [
            'total' => $total,
            'used' => max(0, $total - $remaining),
            'remaining' => $remaining,
            'work_years' => array_map(fn (WorkYearBalance $b): array => $b->toArray($on), $balances),
            'next_available_from' => $pending !== [] ? $pending[0]->availableFrom->toDateString() : null,
        ];
    }

    private function allocate(Personnel $personnel, int $days, string $kind, ?string $source, CarbonInterface $on, ?CarbonInterface $preferred, bool $requireAvailable): void
    {
        $this->civil->forget();
        $on = CarbonImmutable::parse($on->toDateString());

        DB::transaction(function () use ($personnel, $days, $kind, $source, $on, $preferred, $requireAvailable): void {
            $rows = $this->materialize($personnel, $on);
            $balances = $this->buildBalances($personnel, $on);

            if ($balances === []) {
                return;
            }

            $candidates = array_values(array_filter($balances, fn (WorkYearBalance $b): bool => ! $requireAvailable || $b->isAvailableOn($on)));
            $preferredDay = $preferred?->toDateString();

            usort($candidates, function (WorkYearBalance $a, WorkYearBalance $b) use ($preferredDay): int {
                $aPreferred = $preferredDay !== null && $a->period->contains(CarbonImmutable::parse($preferredDay));
                $bPreferred = $preferredDay !== null && $b->period->contains(CarbonImmutable::parse($preferredDay));

                return [$bPreferred, $a->period->sequence] <=> [$aPreferred, $b->period->sequence];
            });

            $left = $days;

            foreach ($candidates as $balance) {
                $take = min($left, max(0, $balance->remaining()));

                if ($take <= 0) {
                    continue;
                }

                $this->entry($rows->get($balance->period->sequence), $personnel, $kind, -$take, $source);
                $left -= $take;

                if ($left === 0) {
                    return;
                }
            }

            // Qalıq çatmadı (blok keçilib və ya köhnə məlumat): artıq hissə sonuncu iş ilinə yazılır.
            $target = end($candidates) ?: end($balances);
            $this->entry($rows->get($target->period->sequence), $personnel, $kind, -$left, $source);
        });
    }

    private function giveBack(Personnel $personnel, int $days, string $kind, ?string $source, CarbonInterface $on, ?string $fromSource): void
    {
        $this->civil->forget();

        DB::transaction(function () use ($personnel, $days, $kind, $source, $on, $fromSource): void {
            $left = $days;

            if ($fromSource !== null) {
                $origin = VacationBalanceEntry::query()
                    ->where('tabel_no', $personnel->tabel_no)
                    ->where('source', $fromSource)
                    ->where('days', '<', 0)
                    ->orderByDesc('id')
                    ->get();

                foreach ($origin as $entry) {
                    $give = min($left, -$entry->days);
                    $this->entryFor($entry->work_year_id, $personnel, $kind, $give, $source);
                    $left -= $give;

                    if ($left === 0) {
                        return;
                    }
                }
            }

            $at = CarbonImmutable::parse($on->toDateString());
            $rows = $this->materialize($personnel, $at);
            $balances = array_reverse($this->buildBalances($personnel, $at));

            if ($balances === []) {
                return;
            }

            foreach ($balances as $balance) {
                $give = min($left, max(0, $balance->spent()));

                if ($give <= 0) {
                    continue;
                }

                $this->entry($rows->get($balance->period->sequence), $personnel, $kind, $give, $source);
                $left -= $give;

                if ($left === 0) {
                    return;
                }
            }

            // Geri çağırmada qalan günlər hər halda işçiyə qaytarılır (ən yeni iş ilinə).
            if ($kind === VacationBalanceEntry::KIND_RECALL && $left > 0) {
                $this->entry($rows->get($balances[0]->period->sequence), $personnel, $kind, $left, $source);
            }
        });
    }

    private function entry(?VacationWorkYear $row, Personnel $personnel, string $kind, int $days, ?string $source): void
    {
        if ($row !== null) {
            $this->entryFor($row->id, $personnel, $kind, $days, $source);
        }
    }

    private function entryFor(int $workYearId, Personnel $personnel, string $kind, int $days, ?string $source): void
    {
        if ($days === 0) {
            return;
        }

        VacationBalanceEntry::query()->create([
            'work_year_id' => $workYearId,
            'tabel_no' => $personnel->tabel_no,
            'kind' => $kind,
            'days' => $days,
            'source' => $source,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * İş ilinə daxil olmayan məzuniyyət dövrləri (ƏM m.132.2): VacationSettings::WORK_YEAR_EXCLUDED_TEMPLATES
     * şablonları ilə verilmiş məzuniyyətlər.
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function excludedPeriods(Personnel $personnel): array
    {
        return $this->orderFacts->workYearGaps((string) $personnel->tabel_no);
    }

    /**
     * İşə qəbul və xitam tarixi — model bu sütunlarsız yüklənibsə (məs. qısa seçimli ekranlar)
     * verilənlər bazasından oxunur.
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function employmentDates(Personnel $personnel): array
    {
        $attributes = $personnel->getAttributes();

        if (! array_key_exists('join_work_date', $attributes) || ! array_key_exists('leave_work_date', $attributes)) {
            $attributes = (array) Personnel::query()->withTrashed()->where('tabel_no', $personnel->tabel_no)->toBase()->first(['join_work_date', 'leave_work_date']);
        }

        $date = fn (mixed $value): ?CarbonImmutable => filled($value) ? CarbonImmutable::parse((string) $value)->startOfDay() : null;

        return [$date($attributes['join_work_date'] ?? null), $date($attributes['leave_work_date'] ?? null)];
    }

    private function yearDate(int $year): CarbonImmutable
    {
        return $year === (int) Carbon::now()->year
            ? CarbonImmutable::today()
            : CarbonImmutable::create($year, 12, 31);
    }
}
