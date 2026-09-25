<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceBonusCalculation;
use App\Models\PerformanceBonusRule;
use App\Models\PerformanceCycle;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Models\Structure;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The KPI dashboards of spec §9, each scoped to who is looking: the employee's own
 * cards (forecast, expected bonus, trend), a manager's team matrix, and HR's cycle
 * views — progress, overdue steps by manager, manager strictness, risk, bonus by unit
 * and position, and the company trend. Every HR view can be narrowed to a unit (with
 * its sub-units).
 */
class KpiAnalyticsService
{
    public const TREND_CYCLES = 8;

    /** Final score from which a person counts as a top performer in the risk report. */
    public const HIGH_SCORE = 106;

    public function __construct(
        private readonly KpiScoringEngine $engine,
        private readonly BonusService $bonus,
    ) {}

    /**
     * @return Collection<int, array{card: PerformanceScorecard, forecast_score: ?float, bonus_now: ?float, bonus_forecast: ?float}>
     */
    public function myCards(int $personnelId, int $cycleId): Collection
    {
        // @phpstan-ignore return.type (PHPStan below level 8 drops null inside invariant Collection generics)
        return PerformanceScorecard::query()
            ->where('personnel_id', $personnelId)
            ->where('performance_cycle_id', $cycleId)
            ->with(['items.kpi:id,code,name,unit', 'cycle', 'position:id,name'])
            ->orderBy('valid_from')
            ->get()
            ->map(function (PerformanceScorecard $card): array {
                $forecast = $this->forecastScore($card);

                return [
                    'card' => $card,
                    'forecast_score' => $forecast,
                    'bonus_now' => $this->bonus->estimate($card, $card->effectiveScore()),
                    'bonus_forecast' => $this->bonus->estimate($card, $forecast),
                ];
            });
    }

    /**
     * The KPI block recomputed on forecasts instead of actuals, then blended with the
     * competency block as the final score would be.
     */
    public function forecastScore(PerformanceScorecard $card): ?float
    {
        if ($card->items->every(fn (PerformanceScorecardItem $item): bool => $item->forecast_achievement === null && $item->achievement === null)) {
            return null;
        }

        $kpiScore = $this->engine->kpiScore($card->items->map(function (PerformanceScorecardItem $item): array {
            $achievement = $item->forecast_achievement ?? $item->achievement;

            return [
                'weight' => $item->weight,
                'score' => $achievement === null ? null : $this->engine->itemScore(
                    (float) $achievement,
                    $item->threshold === null ? null : (float) $item->threshold,
                    $item->stretch === null ? null : (float) $item->stretch,
                    $item->cap === null ? null : (float) $item->cap,
                ),
            ];
        }));

        $competency = $card->competency_score === null ? null : (float) $card->competency_score;
        if ((float) $card->competency_weight_share > 0 && $competency === null) {
            return $kpiScore;
        }

        return $this->engine->finalScore($kpiScore, $competency, (float) $card->kpi_weight_share, (float) $card->competency_weight_share);
    }

    /**
     * The person's score per cycle over the last cycles, days × FTE weighted across
     * their cards — one query for all cycles.
     *
     * @return Collection<int, array{cycle: string, score: ?float}>
     */
    public function personalTrend(int $personnelId): Collection
    {
        $cycles = $this->recentCycles();
        $cards = PerformanceScorecard::query()
            ->where('personnel_id', $personnelId)
            ->whereIn('performance_cycle_id', $cycles->modelKeys())
            ->get(['id', 'performance_cycle_id', 'valid_from', 'valid_to', 'fte', 'final_score', 'calibrated_score'])
            ->groupBy('performance_cycle_id');

        // @phpstan-ignore return.type (PHPStan below level 8 drops null inside invariant Collection generics)
        return $cycles->map(fn (PerformanceCycle $cycle): array => [
            'cycle' => $cycle->name,
            'score' => $this->weightedScore($cards->get($cycle->id, collect())),
        ]);
    }

    /**
     * A manager's people × KPI matrix, with who is at risk and whose step is late.
     *
     * @return array{cards: Collection<int, PerformanceScorecard>, kpis: Collection<string, string>}
     */
    public function teamMatrix(int $managerPersonnelId, int $cycleId): array
    {
        $cards = PerformanceScorecard::query()
            ->where('performance_cycle_id', $cycleId)
            ->where('manager_personnel_id', $managerPersonnelId)
            ->where('status', '!=', 'closed')
            ->with(['personnel:id,surname,name,patronymic', 'items.kpi:id,code,name'])
            ->get()
            ->sortBy(fn (PerformanceScorecard $card): string => (string) $card->personnel?->surname)
            ->values();

        $kpis = $cards->flatMap(fn (PerformanceScorecard $card) => $card->items->map(fn (PerformanceScorecardItem $item): array => [$item->kpi->code, $item->kpi->name]))
            ->unique('0')
            ->mapWithKeys(fn (array $pair): array => [$pair[0] => $pair[1]]);

        return ['cards' => $cards, 'kpis' => $kpis];
    }

    /**
     * Everything HR sees for a cycle, read in a fixed handful of queries: the cycle's
     * cards once (people, managers, positions), their salaries, last cycle's red list,
     * the bonus lines and the trend cards — every table is built from those.
     *
     * @return array{statuses: array<string, int>, overdue: Collection, strictness: Collection, risks: array{flight: Collection, red_twice: Collection}, bonus: array<string, mixed>, trend: Collection}
     */
    public function hrOverview(PerformanceCycle $cycle, ?int $structureId = null): array
    {
        $units = $this->unitIds($structureId);
        $cards = $this->cards($cycle->id, $units)
            ->with(['personnel:id,surname,name,patronymic,tabel_no,structure_id', 'manager:id,surname,name', 'position:id,name'])
            ->get();

        return [
            'statuses' => collect(PerformanceScorecard::STATUSES)->mapWithKeys(fn (string $status): array => [$status => $cards->where('status', $status)->count()])->all(),
            'overdue' => $this->overdue($cards),
            'strictness' => $this->strictness($cards),
            'risks' => $this->risks($cards, $cycle),
            'bonus' => $this->bonusReport($cycle, $units),
            'trend' => $this->companyTrend($units),
        ];
    }

    /**
     * Cards past their stage deadline, grouped by the manager who owns them.
     *
     * @param  Collection<int, PerformanceScorecard>  $cards
     */
    private function overdue(Collection $cards): Collection
    {
        return $cards->where('status', '!=', 'closed')
            ->groupBy('manager_personnel_id')
            ->map(fn (Collection $group): array => [
                'manager' => $this->managerName($group->first()),
                'overdue' => $group->filter(fn (PerformanceScorecard $card): bool => $card->stage_due_at !== null && $card->stage_due_at->lt(today()))->count(),
                'cards' => $group->count(),
            ])
            ->sortByDesc('overdue')
            ->values();
    }

    /**
     * Manager strictness (spec §9): a manager's average final score minus the company
     * average, with the spread of their team's scores. Strongly negative = strict.
     *
     * @param  Collection<int, PerformanceScorecard>  $cards
     */
    private function strictness(Collection $cards): Collection
    {
        $scored = $cards->whereNotNull('final_score');
        if ($scored->isEmpty()) {
            return collect();
        }

        $companyAverage = (float) $scored->avg(fn (PerformanceScorecard $card): float => (float) $card->final_score);

        return $scored->groupBy('manager_personnel_id')
            ->map(function (Collection $team) use ($companyAverage): array {
                $scores = $team->map(fn (PerformanceScorecard $card): float => (float) $card->final_score);
                $average = (float) $scores->avg();

                return [
                    'manager' => $this->managerName($team->first()),
                    'people' => $team->count(),
                    'average' => round($average, 2),
                    'delta' => round($average - $companyAverage, 2),
                    'spread' => round(sqrt($scores->map(fn (float $score): float => ($score - $average) ** 2)->avg()), 2),
                ];
            })
            ->sortBy('delta')
            ->values();
    }

    /**
     * Risk report (spec §9): top performers paid below the median of their position
     * (flight risk), and people red — not meeting expectations — two cycles in a row.
     *
     * @param  Collection<int, PerformanceScorecard>  $cards
     * @return array{flight: Collection, red_twice: Collection}
     */
    private function risks(Collection $cards, PerformanceCycle $cycle): array
    {
        $salaries = $this->salaries($cards, $cycle);
        $salaryOf = fn (PerformanceScorecard $card): ?float => $salaries->get((string) $card->personnel?->tabel_no);

        $medians = $cards->groupBy('position_id')->map(function (Collection $group) use ($salaryOf): ?float {
            $values = $group->map($salaryOf)->filter()->sort()->values();

            return $values->isEmpty() ? null : (float) $values->median();
        });

        $flight = $cards
            ->filter(fn (PerformanceScorecard $card): bool => ($card->effectiveScore() ?? 0) >= self::HIGH_SCORE
                && $salaryOf($card) !== null
                && $medians->get($card->position_id) !== null
                && $salaryOf($card) < $medians->get($card->position_id))
            ->map(fn (PerformanceScorecard $card): array => [
                ...$this->personRow($card),
                'salary_gap' => round(($salaryOf($card) / $medians->get($card->position_id) - 1) * 100, 1),
            ])
            ->values();

        $red = $cards->where('rating_category', 'not_meeting');
        $redBefore = $red->isEmpty() ? collect() : PerformanceScorecard::query()
            ->whereIn('personnel_id', $red->pluck('personnel_id'))
            ->where('rating_category', 'not_meeting')
            ->where('performance_cycle_id', PerformanceCycle::query()->where('period_end', '<', $cycle->period_start)->orderByDesc('period_end')->limit(1)->select('id'))
            ->pluck('personnel_id')
            ->flip();

        return [
            'flight' => $flight,
            'red_twice' => $red->filter(fn (PerformanceScorecard $card): bool => $redBefore->has($card->personnel_id))->map(fn (PerformanceScorecard $card): array => $this->personRow($card))->values(),
        ];
    }

    /**
     * Bonus by unit and by position (spec §9), against the cycle's fund.
     *
     * @param  array<int, int>|null  $units
     * @return array{units: Collection, positions: Collection, total: float, fund: ?float, currency: string}
     */
    private function bonusReport(PerformanceCycle $cycle, ?array $units): array
    {
        $lines = PerformanceBonusCalculation::query()
            ->where('performance_cycle_id', $cycle->id)
            ->when($units !== null, fn (Builder $query) => $query->whereHas('personnel', fn (Builder $inner) => $inner->whereIn('structure_id', $units)))
            ->with(['personnel:id,structure_id', 'personnel.structure:id,name', 'scorecard:id,position_id', 'scorecard.position:id,name'])
            ->get();

        $group = fn (callable $key, callable $label): Collection => $lines->groupBy(fn (PerformanceBonusCalculation $line): string => (string) $key($line))->map(fn (Collection $group): array => [
            'name' => $label($group->first()) ?? '—',
            'people' => $group->count(),
            'total' => round($group->sum('amount'), 2),
            'average' => round($group->avg('amount'), 2),
        ])->sortByDesc('total')->values();

        return [
            'units' => $group(fn (PerformanceBonusCalculation $line) => $line->personnel?->structure_id, fn (PerformanceBonusCalculation $line) => $line->personnel?->structure?->name),
            'positions' => $group(fn (PerformanceBonusCalculation $line) => $line->scorecard?->position_id, fn (PerformanceBonusCalculation $line) => $line->scorecard?->position?->name),
            'total' => round($lines->sum('amount'), 2),
            'fund' => PerformanceBonusRule::query()->where('performance_cycle_id', $cycle->id)->first()?->fund,
            'currency' => (string) ($lines->first()->currency ?? 'AZN'),
        ];
    }

    /**
     * Average approved/closed score per recent cycle — one query for all cycles.
     *
     * @param  array<int, int>|null  $units
     */
    private function companyTrend(?array $units): Collection
    {
        $cycles = $this->recentCycles();
        $scores = PerformanceScorecard::query()
            ->whereIn('performance_cycle_id', $cycles->modelKeys())
            ->whereIn('status', ['approved', 'closed'])
            ->when($units !== null, fn (Builder $query) => $query->whereHas('personnel', fn (Builder $inner) => $inner->whereIn('structure_id', $units)))
            ->get(['performance_cycle_id', 'final_score', 'calibrated_score'])
            ->groupBy('performance_cycle_id');

        return $cycles->map(function (PerformanceCycle $cycle) use ($scores): array {
            $values = $scores->get($cycle->id, collect())->map(fn (PerformanceScorecard $card): ?float => $card->effectiveScore())->filter(fn (?float $score): bool => $score !== null);

            return ['cycle' => $cycle->name, 'score' => $values->isEmpty() ? null : round($values->avg(), 2)];
        });
    }

    /**
     * @return EloquentCollection<int, PerformanceCycle> oldest first
     */
    private function recentCycles(): EloquentCollection
    {
        return PerformanceCycle::query()->orderByDesc('period_end')->limit(self::TREND_CYCLES)->get(['id', 'name'])->reverse()->values();
    }

    /**
     * @param  Collection<int, PerformanceScorecard>  $cards
     */
    private function weightedScore(Collection $cards): ?float
    {
        $weight = fn (PerformanceScorecard $card): float => ((int) $card->valid_from->diffInDays($card->valid_to) + 1) * (float) $card->fte;
        $scored = $cards->filter(fn (PerformanceScorecard $card): bool => $card->effectiveScore() !== null);
        $total = $scored->sum($weight);

        return $total <= 0 ? null : round($scored->sum(fn (PerformanceScorecard $card): float => $card->effectiveScore() * $weight($card)) / $total, 2);
    }

    /**
     * @return array{personnel: ?string, position: ?string, score: ?float}
     */
    private function personRow(PerformanceScorecard $card): array
    {
        return ['personnel' => $card->personnel?->fullname, 'position' => $card->position?->name, 'score' => $card->effectiveScore()];
    }

    private function managerName(PerformanceScorecard $card): string
    {
        return $card->manager ? trim($card->manager->surname.' '.$card->manager->name) : '—';
    }

    /**
     * A unit and everything under it; null means the whole organisation.
     *
     * @return array<int, int>|null
     */
    public function unitIds(?int $structureId): ?array
    {
        if (! $structureId) {
            return null;
        }

        $children = Structure::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$structureId];
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($children->get($ids[$i], collect()) as $child) {
                $ids[] = (int) $child->id;
            }
        }

        return $ids;
    }

    /**
     * @return Builder<PerformanceScorecard>
     */
    private function cards(int $cycleId, ?array $units): Builder
    {
        return PerformanceScorecard::query()
            ->where('performance_cycle_id', $cycleId)
            ->when($units !== null, fn (Builder $query) => $query->whereHas('personnel', fn (Builder $inner) => $inner->whereIn('structure_id', $units)));
    }

    /**
     * @param  Collection<int, PerformanceScorecard>  $cards
     * @return Collection<string, float>
     */
    private function salaries(Collection $cards, PerformanceCycle $cycle): Collection
    {
        $tabelNos = $cards->pluck('personnel.tabel_no')->filter()->map(fn ($tabel): string => (string) $tabel)->unique()->values()->all();

        if ($tabelNos === [] || ! app()->bound(CompensationReadRepository::class)) {
            return collect();
        }

        return app(CompensationReadRepository::class)
            ->baseAmountsFor($tabelNos, Carbon::parse($cycle->period_end)->toDateString())
            ->mapWithKeys(fn ($amount, $tabel): array => [(string) $tabel => (float) $amount]);
    }
}
