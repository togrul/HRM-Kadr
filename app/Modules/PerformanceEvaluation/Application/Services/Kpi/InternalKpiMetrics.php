<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\EmployeeContentAssignment;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use Illuminate\Support\Carbon;

/**
 * KPIs the HRM already knows (spec §8, "HRM daxili modullar"): a KPI names one metric
 * and every active card's item is refilled from it — nightly and on demand. Each run
 * keeps a single `hrm` actual per item, updated in place, so the aggregation sees the
 * latest value only. Manual actuals on the same item are left alone.
 */
class InternalKpiMetrics
{
    /** metric → the unit it is measured in */
    public const METRICS = [
        'attendance_rate' => 'percent',
        'absence_days' => 'days',
        'training_completion' => 'percent',
    ];

    public function __construct(private readonly ScorecardService $scorecards) {}

    /**
     * Refreshes every active card whose KPIs read an internal metric. Returns the
     * number of items updated.
     */
    public function sync(?int $cycleId = null): int
    {
        $items = PerformanceScorecardItem::query()
            ->whereHas('kpi', fn ($query) => $query->whereNotNull('source_metric'))
            ->whereHas('scorecard', fn ($query) => $query->where('status', 'active')->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId)))
            ->with(['kpi:id,source_metric', 'scorecard.personnel:id,tabel_no'])
            ->get();

        $touched = collect();
        foreach ($items as $item) {
            $card = $item->scorecard;
            $to = min(today(), Carbon::parse($card->valid_to));
            $value = $this->value((string) $item->kpi->source_metric, $card->personnel, Carbon::parse($card->valid_from), $to);

            if ($value === null) {
                continue;
            }

            $item->actuals()->updateOrCreate(['source' => 'hrm'], [
                'value' => $value,
                'note' => __('performance_evaluation::kpi.metrics.synced_note', ['date' => $to->format('d.m.Y')]),
                'approved_at' => now(),
            ]);
            $touched->put($card->id, $card);
        }

        $touched->each(fn (PerformanceScorecard $card) => $this->scorecards->recalculate($card));

        return $items->count();
    }

    public function value(string $metric, ?Personnel $personnel, Carbon $from, Carbon $to): ?float
    {
        if ($personnel === null || $from->gt($to)) {
            return null;
        }

        return match ($metric) {
            'attendance_rate', 'absence_days' => $this->attendance($metric, (string) $personnel->tabel_no, $from, $to),
            'training_completion' => $this->trainingCompletion($personnel->id, $from, $to),
            default => null,
        };
    }

    /**
     * From the monthly attendance summaries the period touches; null until a month is
     * summarised.
     */
    private function attendance(string $metric, string $tabelNo, Carbon $from, Carbon $to): ?float
    {
        if ($tabelNo === '' || ! app()->bound(PayrollAttendanceReadRepository::class)) {
            return null;
        }

        $repository = app(PayrollAttendanceReadRepository::class);
        $working = 0;
        $absent = 0;

        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $summary = $repository->monthlyAbsence($tabelNo, $month->year, $month->month);
            $working += $summary['working_days'] ?? 0;
            $absent += $summary['absence_days'] ?? 0;
        }

        if ($working === 0) {
            return null;
        }

        return $metric === 'absence_days'
            ? (float) $absent
            : round(max(0, $working - $absent) / $working * 100, 2);
    }

    /**
     * Share of learning assignments due in the period that were completed.
     */
    private function trainingCompletion(int $personnelId, Carbon $from, Carbon $to): ?float
    {
        $statuses = EmployeeContentAssignment::query()
            ->where('personnel_id', $personnelId)
            ->where('status', '!=', 'waived')
            ->whereBetween('due_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->pluck('status');

        if ($statuses->isEmpty()) {
            return null;
        }

        return round($statuses->filter(fn (string $status): bool => $status === 'completed')->count() / $statuses->count() * 100, 2);
    }
}
