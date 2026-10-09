<?php

namespace App\Services\Vacation\Entitlement;

use App\Enums\RankCategoryEnum;
use App\Models\Personnel;
use App\Models\RankCategory;
use App\Modules\Vacation\Application\Services\EntitlementBreakdown;
use App\Modules\Vacation\Application\Services\WorkYearPeriod;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Rütbəli heyət (rütbə kateqoriyası olan işçilər): əvvəlki qayda dəyişmədən — kateqoriyanın
 * illik günləri, birinci il üçün aylıq norma × işlənmiş ay, çavuş / gizir üçün xidmət + hərbi
 * staj 20 ildən çoxdursa 40 gün. Birinci il üçün 6 ay gözləmə tətbiq olunmur.
 */
class RankedEntitlementStrategy implements EntitlementStrategy
{
    public const KEY = 'ranked';

    public function key(): string
    {
        return self::KEY;
    }

    public function applies(Personnel $personnel): bool
    {
        return $this->rankCategory($personnel) !== null;
    }

    private function rankCategory(Personnel $personnel): ?RankCategory
    {
        $rank = $personnel->latestRank?->getRelationValue('rank');
        $category = $rank instanceof Model ? $rank->getRelationValue('rankCategory') : null;

        return $category instanceof RankCategory ? $category : null;
    }

    /**
     * Əvvəlki VacationBalanceService::entitlementDays() hesabı (dəyişməyib).
     */
    public function days(Personnel $personnel, Carbon $asOf): int
    {
        if (! array_key_exists('join_work_date', $personnel->getAttributes())) {
            // Loaded without the column (a narrow select): read the full record.
            $personnel = Personnel::query()->withTrashed()->where('tabel_no', $personnel->tabel_no)->first() ?? $personnel;
        }

        $join = $personnel->join_work_date ? Carbon::parse($personnel->join_work_date) : null;

        $years = (int) ($join ? $join->diffInYears($asOf) : 1);
        $months = (int) ($join ? $join->diffInMonths($asOf) : 12);

        $rankCategory = $this->rankCategory($personnel);

        return $rankCategory ? (int) round($this->rankedDays($personnel, $rankCategory, $years, $months)) : 0;
    }

    public function entitlement(Personnel $personnel, WorkYearPeriod $period, CarbonImmutable $asOf): EntitlementBreakdown
    {
        $at = $asOf->gt($period->end) ? $period->end : $asOf;

        return new EntitlementBreakdown($this->days($personnel, Carbon::parse($at->toDateString())), strategy: self::KEY);
    }

    public function availableFrom(Personnel $personnel, WorkYearPeriod $period): CarbonImmutable
    {
        return $period->start;
    }

    private function rankedDays(Personnel $personnel, RankCategory $rankCategory, int $years, int $months): float
    {
        if ($years <= 0) {
            return (float) $rankCategory->vacation_days_per_month * $months;
        }

        // Sergeants / warrant officers with 20+ combined service years get 40 days.
        if (in_array($rankCategory->id, [RankCategoryEnum::CAVUS->value, RankCategoryEnum::GIZIR->value], true)) {
            return ($years + $this->militaryServiceYears($personnel)) >= 20 ? 40 : $rankCategory->vacation_days_count;
        }

        return (float) $rankCategory->vacation_days_count;
    }

    private function militaryServiceYears(Personnel $personnel): int
    {
        $years = fn (mixed $start, mixed $end): float => $start instanceof CarbonInterface ? $start->diffInYears($end) : 0;

        $military = $personnel->military->sum(fn (Model $m): float => $years($m->getAttribute('start_date'), $m->getAttribute('end_date')));
        $special = $personnel->laborActivities
            ->where('is_special_service', true)
            ->where('is_current', false)
            ->sum(fn (Model $s): float => $years($s->getAttribute('join_date'), $s->getAttribute('leave_date')));

        return (int) ($military + $special);
    }
}
