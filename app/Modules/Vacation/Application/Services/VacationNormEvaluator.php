<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\VacationNorm;
use App\Modules\Personnel\Contracts\LeaveEntitlementFacts;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Məzuniyyət normalarını işçinin faktlarına tətbiq edir (ƏM m.114–117, 119, 136):
 *   - əsas: işçiyə şamil olan bütün sətirlərin ən böyüyü (normalar «azı» minimumlardır, kollektiv /
 *     əmək müqaviləsi daha çox verə bilər — m.145); şamil olan sətir `exclusive`-dirsə (m.118–121
 *     kateqoriyası) staj, uşaq və şərait əlavələri verilmir (m.116.3, 117.4);
 *   - staj: ümumi əmək stajının düşdüyü [min, max) aralığı — ən böyük gün;
 *   - uşaqlı valideyn: şərtə uyğun sətirlərin ən böyüyü (2 və 5 gün toplanmır); qadınlara, eləcə də
 *     işçi üzrə yazılmış sətirlə (uşaqlarını təkbaşına böyüdən ata, övladlığa götürən) tətbiq olunur;
 *     14 yaşınadək uşaq həmin təqvim ilinin sonunadək sayılır (m.117.3);
 *   - əmək şəraiti: vəzifə / işçi üzrə sətirlərin ən böyüyü.
 * Hamısı iş ilinin başlanğıc tarixinə görə qiymətləndirilir. Saf: normalar çağırandan gəlir.
 */
class VacationNormEvaluator
{
    /** m.131.6: əmək şəraitinə görə əlavə məzuniyyət hüququ — həmin şəraitdə üst-üstə 6 ay. */
    public const CONDITIONS_WAIT_MONTHS = 6;

    /** NK 95, b.10: tam ay = cəmlənmiş günlər ÷ 30,4. */
    public const DAYS_PER_MONTH = 30.4;

    /**
     * @param  Collection<int, VacationNorm>  $norms  aktiv normalar
     */
    public function evaluate(LeaveEntitlementFacts $facts, CarbonImmutable $on, Collection $norms, ?WorkYearPeriod $period = null, ?CarbonImmutable $asOf = null): EntitlementBreakdown
    {
        $byGroup = $norms->where('is_active', true)->groupBy('group');

        $base = $this->applicable($byGroup->get(VacationNorm::GROUP_BASE, collect()), $facts, $on);
        $baseDays = (int) ($base->max('days') ?? 0);
        $exclusive = $base->contains(fn (VacationNorm $norm): bool => $norm->exclusive);

        $seniorityYears = $facts->seniorityYearsOn($on);

        if ($exclusive) {
            return new EntitlementBreakdown($baseDays, seniorityYears: $seniorityYears, exclusive: true);
        }

        $seniority = (int) ($byGroup->get(VacationNorm::GROUP_SENIORITY, collect())
            ->filter(fn (VacationNorm $norm): bool => $this->seniorityBandApplies($norm, $facts, $on))
            ->max('days') ?? 0);

        $children = (int) ($byGroup->get(VacationNorm::GROUP_CHILDREN, collect())
            ->filter(fn (VacationNorm $norm): bool => $this->childrenRuleApplies($norm, $facts, $on))
            ->max('days') ?? 0);

        $conditions = $this->conditionsDays($byGroup->get(VacationNorm::GROUP_CONDITIONS, collect()), $facts, $on, $period, $asOf);

        return new EntitlementBreakdown($baseDays, $seniority, $children, $conditions, $seniorityYears);
    }

    /**
     * Əmək şəraitinə görə əlavə məzuniyyət (ƏM m.131.6; NK-nin 30.05.2005 tarixli 95 nömrəli
     * qərarı ilə təsdiq edilmiş Qaydalar), gün-gün:
     *   - günün şərait normu: işçinin həmin gündəki vəzifəsi üzrə (köçürmə əmrlərindən) və işçi
     *     üzrə qüvvədə olan sətirlərdən ən böyüyü (b.14); peşəsi siyahıda olmayan işçiyə dövrlü
     *     işçi sətri (b.13);
     *   - «Şəraitdən kənar» işçi sətrinin dövrü — iş gününün 90%-dən azı şəraitdə (b.12) — və
     *     staja daxil olmayan dövrlər (ödənişsiz, uşağa qulluq; b.8) sayılmır;
     *   - hüquq şəraitdə üst-üstə 6 aydan sonra (b.7, m.131.6);
     *   - iş ili üçün hər norm üzrə tam aylar ayrıca (b.11), tam ay = günlər ÷ 30,4, yarımdan az
     *     qalıq atılır (b.10): günlər = Σ norma × aylar ÷ 12;
     *   - hazırda şəraitdə işləyən işçiyə cari iş ili üçün əlavə məzuniyyət 12 ay bitmədən tam
     *     verilə bilər (avans, b.7) — qalan günlər hazırkı vəzifədə davam edəcəyi kimi sayılır.
     * İş ili və tarix verilməyibsə (köhnə çağırış) iş ilinin başlanğıcında şamil olan ən böyük norma.
     *
     * @param  Collection<int, VacationNorm>  $norms
     */
    private function conditionsDays(Collection $norms, LeaveEntitlementFacts $facts, CarbonImmutable $on, ?WorkYearPeriod $period, ?CarbonImmutable $asOf): int
    {
        // Yalnız bu işçiyə aid ola bilən sətirlər: onun özü və tarixçəsindəki vəzifələr.
        $positions = array_filter([$facts->positionId, ...array_column($facts->positionChanges, 1)], fn ($id): bool => $id !== null);
        $norms = $norms->filter(fn (VacationNorm $norm): bool => match ($norm->scope) {
            VacationNorm::SCOPE_POSITION => in_array($norm->position_id, $positions, true),
            VacationNorm::SCOPE_PERSONNEL => filled($norm->tabel_no) && (string) $norm->tabel_no === $facts->tabelNo,
            default => false,
        })->values();

        if ($norms->isEmpty()) {
            return 0;
        }

        if ($period === null || $asOf === null || $facts->joinDate === null) {
            return (int) ($this->applicable($norms->reject(fn (VacationNorm $norm): bool => $norm->not_in_conditions), $facts, $on)->max('days') ?? 0);
        }

        $until = $asOf->lt($period->end) ? $asOf : $period->end;

        if ($this->conditionsDaysUntil($norms, $facts, $until) / self::DAYS_PER_MONTH < self::CONDITIONS_WAIT_MONTHS) {
            return 0;
        }

        // Avans (b.7): yalnız hazırda şəraitdə işləyən üçün qalan günlər də sayılır.
        $projected = $this->normOn($norms, $facts, $asOf) !== null;
        $daysByNorm = [];

        for ($day = $period->start; $day->lte($period->end); $day = $day->addDay()) {
            if ($day->gt($asOf) && ! $projected) {
                break;
            }

            $norm = $this->countedNormOn($norms, $facts, $day);

            if ($norm !== null) {
                $daysByNorm[$norm->id] = ($daysByNorm[$norm->id] ?? 0) + 1;
            }
        }

        $total = 0.0;

        foreach ($daysByNorm as $id => $days) {
            $months = min(12, (int) round($days / self::DAYS_PER_MONTH, 0, PHP_ROUND_HALF_UP));
            $total += (int) $norms->firstWhere('id', $id)?->days * $months / 12;
        }

        return (int) round($total, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * İşə qəbuldan $until-ədək (daxil) şəraitdə sayılan günlər — 6 ay hüququ üçün; hədd
     * keçiləndə dayanır.
     *
     * @param  Collection<int, VacationNorm>  $norms
     */
    private function conditionsDaysUntil(Collection $norms, LeaveEntitlementFacts $facts, CarbonImmutable $until): int
    {
        $count = 0;
        $needed = (int) ceil(self::CONDITIONS_WAIT_MONTHS * self::DAYS_PER_MONTH);

        for ($day = $facts->joinDate; $day !== null && $day->lte($until) && $count < $needed; $day = $day->addDay()) {
            if ($this->countedNormOn($norms, $facts, $day) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Gün əlavə məzuniyyət stajına daxildirsə onun normu, yoxsa null.
     *
     * @param  Collection<int, VacationNorm>  $norms
     */
    private function countedNormOn(Collection $norms, LeaveEntitlementFacts $facts, CarbonImmutable $day): ?VacationNorm
    {
        if (($facts->joinDate !== null && $day->lt($facts->joinDate))
            || ($facts->leaveDate !== null && $day->gt($facts->leaveDate))
            || $facts->isSeniorityGap($day)) {
            return null;
        }

        return $this->normOn($norms, $facts, $day);
    }

    /**
     * Günün şərait normu: «Şəraitdən kənar» dövrü yoxdursa, həmin gün qüvvədə olan vəzifə və
     * işçi sətirlərindən ən böyüyü (b.14).
     *
     * @param  Collection<int, VacationNorm>  $norms
     */
    private function normOn(Collection $norms, LeaveEntitlementFacts $facts, CarbonImmutable $day): ?VacationNorm
    {
        $date = $day->toDateString();
        $position = $facts->positionOn($day);
        $best = null;

        foreach ($norms as $norm) {
            if (! $norm->coversDate($date)) {
                continue;
            }

            if ($norm->not_in_conditions) {
                return null;
            }

            $applies = $norm->scope === VacationNorm::SCOPE_PERSONNEL
                || ($norm->scope === VacationNorm::SCOPE_POSITION && $norm->position_id === $position);

            if ($applies && ($best === null || $norm->days > $best->days)) {
                $best = $norm;
            }
        }

        return $best;
    }

    /**
     * m.116.1 sərhədləri mətnə görə: «X ildən Y ilədək» — hər iki uc daxil, «X ildən çox» — X
     * xaric. Düz 10 il iki aralığa düşür, böyük olanı (4 gün) götürülür; düz 15 il hələ «on
     * ildən on beş ilədək» aralığıdır (4 gün), 6 gün yalnız 15 ildən çox olanda.
     */
    private function seniorityBandApplies(VacationNorm $norm, LeaveEntitlementFacts $facts, CarbonImmutable $on): bool
    {
        $from = $facts->compareSeniorityTo((int) ($norm->min_value ?? 0), $on);

        if ($norm->max_value === null) {
            return $from > 0;
        }

        return $from >= 0 && $facts->compareSeniorityTo((int) $norm->max_value, $on) <= 0;
    }

    /**
     * Sahəsi (scope) işçiyə şamil olan sətirlər.
     *
     * @param  Collection<int, VacationNorm>  $norms
     * @return Collection<int, VacationNorm>
     */
    private function applicable(Collection $norms, LeaveEntitlementFacts $facts, CarbonImmutable $on): Collection
    {
        return $norms->filter(fn (VacationNorm $norm): bool => match ($norm->scope) {
            VacationNorm::SCOPE_ALL => true,
            VacationNorm::SCOPE_VTISK_CATEGORY => $norm->condition !== null && $norm->condition === $facts->positionCategory,
            VacationNorm::SCOPE_POSITION => $norm->position_id !== null && $norm->position_id === $facts->positionId,
            VacationNorm::SCOPE_PERSONNEL => filled($norm->tabel_no) && (string) $norm->tabel_no === $facts->tabelNo,
            VacationNorm::SCOPE_AGE_UNDER_16 => $facts->birthdate !== null && $facts->birthdate->lte($on) && $facts->birthdate->addYears(16)->gt($on),
            VacationNorm::SCOPE_AGE_16_18 => $facts->birthdate !== null && $facts->birthdate->addYears(16)->lte($on) && $facts->birthdate->addYears(18)->gt($on),
            VacationNorm::SCOPE_DISABILITY => $facts->isDisabledOn($on),
            default => false,
        })->values();
    }

    private function childrenRuleApplies(VacationNorm $norm, LeaveEntitlementFacts $facts, CarbonImmutable $on): bool
    {
        if ($norm->scope === VacationNorm::SCOPE_PERSONNEL) {
            if (blank($norm->tabel_no) || (string) $norm->tabel_no !== $facts->tabelNo) {
                return false;
            }
        } elseif ($norm->women_only && ! $facts->isFemale()) {
            return false;
        }

        return match ($norm->condition) {
            VacationNorm::CONDITION_CHILDREN_UNDER_14 => $this->childrenUnder14($facts, $on) >= max(1, (int) ($norm->min_value ?? 1)),
            VacationNorm::CONDITION_DISABLED_CHILD => $this->disabledChildren($facts, $on, $norm->max_value) >= max(1, (int) ($norm->min_value ?? 1)),
            default => false,
        };
    }

    /** m.117.3: uşaq 14 yaşını tamamladığı təqvim ilinin sonunadək sayılır. */
    private function childrenUnder14(LeaveEntitlementFacts $facts, CarbonImmutable $on): int
    {
        return collect($facts->children)
            ->filter(fn (array $child): bool => $child['birthdate'] !== null
                && $child['birthdate']->lte($on)
                && $child['birthdate']->addYears(14)->year >= $on->year)
            ->count();
    }

    private function disabledChildren(LeaveEntitlementFacts $facts, CarbonImmutable $on, ?int $maxAge): int
    {
        return collect($facts->children)
            ->filter(fn (array $child): bool => $child['disabled']
                && $child['birthdate'] !== null
                && $child['birthdate']->lte($on)
                && ($maxAge === null || $child['birthdate']->addYears($maxAge)->gt($on)))
            ->count();
    }
}
