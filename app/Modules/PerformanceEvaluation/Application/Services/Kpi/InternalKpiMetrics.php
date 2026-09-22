<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\EmployeeContentAssignment;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Automatic KPI sources (spec §8): HRM data the system already holds (attendance,
 * learning) and external systems through the generic REST connector. A KPI names one
 * source and every active card's item is refilled from it — nightly and on demand. Each
 * run keeps a single system actual per item (`hrm` or `integration`), updated in place,
 * so the aggregation sees the latest value only. Manual actuals are left alone.
 *
 * An external source that fails keeps the last value, marks the KPI stale and tells HR
 * once; the next successful run clears the mark.
 */
class InternalKpiMetrics
{
    /** metric → the unit it is measured in */
    public const METRICS = [
        'attendance_rate' => 'percent',
        'absence_days' => 'days',
        'training_completion' => 'percent',
        'rest' => null,
    ];

    public function __construct(
        private readonly ScorecardService $scorecards,
        private readonly RestKpiConnector $rest,
        private readonly ScorecardNotifier $notifier,
    ) {}

    /**
     * Refreshes every active card whose KPIs read an internal metric. Returns the
     * number of items updated.
     */
    public function sync(?int $cycleId = null): int
    {
        $items = PerformanceScorecardItem::query()
            ->whereHas('kpi', fn ($query) => $query->whereNotNull('source_metric'))
            ->whereHas('scorecard', fn ($query) => $query->where('status', 'active')->when($cycleId, fn ($q) => $q->where('performance_cycle_id', $cycleId)))
            ->with(['kpi', 'scorecard.personnel:id,tabel_no,email,pin'])
            ->get();

        $touched = collect();
        $failures = [];
        foreach ($items as $item) {
            $card = $item->scorecard;
            $kpi = $item->kpi;
            $to = min(today(), Carbon::parse($card->valid_to));

            try {
                $value = $kpi->source_metric === 'rest'
                    ? ($card->personnel ? $this->rest->fetch($kpi->integration_config ?? [], $card->personnel, Carbon::parse($card->valid_from), $to) : null)
                    : $this->value((string) $kpi->source_metric, $card->personnel, Carbon::parse($card->valid_from), $to);
            } catch (RuntimeException $exception) {
                $failures[$kpi->id] ??= [$kpi, $exception->getMessage()];

                continue;
            }

            if ($value === null) {
                continue;
            }

            $item->actuals()->updateOrCreate(['source' => $kpi->source_metric === 'rest' ? 'integration' : 'hrm'], [
                'value' => $value,
                'note' => __('performance_evaluation::kpi.metrics.synced_note', ['date' => $to->format('d.m.Y')]),
                'approved_at' => now(),
            ]);
            $touched->put($card->id, $card);
        }

        $touched->each(fn (PerformanceScorecard $card) => $this->scorecards->recalculate($card));
        $this->recordConnectorState($items->pluck('kpi')->where('source_metric', 'rest')->unique('id'), $failures);

        return $items->count();
    }

    /**
     * @param  Collection<int, PerformanceKpi>  $kpis
     * @param  array<int, array{0: PerformanceKpi, 1: string}>  $failures
     */
    private function recordConnectorState(Collection $kpis, array $failures): void
    {
        foreach ($kpis as $kpi) {
            if (! isset($failures[$kpi->id])) {
                $kpi->forceFill(['integration_synced_at' => now(), 'integration_error' => null])->save();

                continue;
            }

            $wasFailing = filled($kpi->integration_error);
            $kpi->forceFill(['integration_error' => $failures[$kpi->id][1]])->save();

            if (! $wasFailing) {
                $this->notifier->connectorFailed($kpi, $failures[$kpi->id][1]);
            }
        }
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
