<?php

namespace App\Services\Vacation;

use App\Enums\RankCategoryEnum;
use App\Models\Personnel;
use App\Models\Vacation;
use Carbon\Carbon;

/**
 * Resolves and maintains an employee's annual vacation balance (the `vacations` row per
 * tabel_no + year that the Vacation module displays). The entitlement rules mirror the
 * yearly allocation command (rank category / seniority / military service; civilians get
 * 30 days at 1+ year, pro-rated before). The order flow uses this to (a) show how many
 * days the employee is entitled to / has used / has left, (b) block a leave that exceeds
 * the remaining balance, and (c) decrement on approval / restore on cancellation.
 */
class VacationBalanceService
{
    /**
     * Annual entitlement in calendar days for the employee, as of $asOf (defaults now).
     */
    public function entitlementDays(Personnel $personnel, ?Carbon $asOf = null): int
    {
        $asOf ??= Carbon::now();
        $join = $personnel->join_work_date ? Carbon::parse($personnel->join_work_date) : null;

        $years = (int) ($join ? $join->diffInYears($asOf) : 1);
        $months = (int) ($join ? $join->diffInMonths($asOf) : 12);

        $rankCategory = $personnel->latestRank?->rank?->rankCategory;

        $days = $rankCategory
            ? $this->rankedDays($personnel, $rankCategory, $years, $months)
            : $this->nonRankedDays($years, $months);

        return (int) round($days);
    }

    /**
     * The employee's balance for the year as ['total' => , 'used' => , 'remaining' => ].
     */
    public function snapshot(Personnel $personnel, int $year): array
    {
        $row = $this->balanceRow($personnel, $year);
        $total = (int) $row->vacation_days_total;
        $remaining = max(0, (int) $row->remaining_days);

        return [
            'total' => $total,
            'used' => max(0, $total - $remaining),
            'remaining' => $remaining,
        ];
    }

    /** Deduct $days from the year's remaining balance (on approval). */
    public function consume(Personnel $personnel, int $year, int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $row = $this->balanceRow($personnel, $year);
        $row->forceFill(['remaining_days' => max(0, (int) $row->remaining_days - $days)])->save();
    }

    /** Give $days back to the year's remaining balance (on cancel/revert), capped at total. */
    public function release(Personnel $personnel, int $year, int $days): void
    {
        if ($days <= 0) {
            return;
        }

        $row = $this->balanceRow($personnel, $year);
        $row->forceFill([
            'remaining_days' => min((int) $row->vacation_days_total, (int) $row->remaining_days + $days),
        ])->save();
    }

    /**
     * The year's balance row, created lazily with the computed entitlement if the yearly
     * allocation has not run for this employee yet.
     */
    public function balanceRow(Personnel $personnel, int $year): Vacation
    {
        $existing = Vacation::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->where('year', $year)
            ->first();

        if ($existing) {
            return $existing;
        }

        $asOf = $year === Carbon::now()->year ? Carbon::now() : Carbon::create($year, 12, 31);
        $total = $this->entitlementDays($personnel, $asOf);

        return Vacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'year' => $year,
            'reserved_date_month' => null,
            'vacation_days_total' => $total,
            'remaining_days' => $total,
        ]);
    }

    private function rankedDays(Personnel $personnel, $rankCategory, int $years, int $months): float
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

    private function nonRankedDays(int $years, int $months): float
    {
        return $years < 1 ? $months * 2.5 : 30;
    }

    private function militaryServiceYears(Personnel $personnel): int
    {
        $military = $personnel->military->sum(fn ($m) => $m?->start_date?->diffInYears($m?->end_date) ?? 0);
        $special = $personnel->laborActivities
            ->where('is_special_service', true)
            ->where('is_current', false)
            ->sum(fn ($s) => $s->join_date?->diffInYears($s->leave_date) ?? 0);

        return (int) ($military + $special);
    }
}
