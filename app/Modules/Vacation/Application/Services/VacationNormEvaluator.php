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
    /**
     * @param  Collection<int, VacationNorm>  $norms  aktiv normalar
     */
    public function evaluate(LeaveEntitlementFacts $facts, CarbonImmutable $on, Collection $norms): EntitlementBreakdown
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
            ->filter(fn (VacationNorm $norm): bool => $seniorityYears >= (int) ($norm->min_value ?? 0)
                && ($norm->max_value === null || $seniorityYears < (int) $norm->max_value))
            ->max('days') ?? 0);

        $children = (int) ($byGroup->get(VacationNorm::GROUP_CHILDREN, collect())
            ->filter(fn (VacationNorm $norm): bool => $this->childrenRuleApplies($norm, $facts, $on))
            ->max('days') ?? 0);

        $conditions = (int) ($this->applicable($byGroup->get(VacationNorm::GROUP_CONDITIONS, collect()), $facts, $on)->max('days') ?? 0);

        return new EntitlementBreakdown($baseDays, $seniority, $children, $conditions, $seniorityYears);
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
