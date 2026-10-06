<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

/**
 * Pure KPI arithmetic (spec §6): achievement per direction/type, threshold and cap
 * rules, the weighted KPI score and the KPI + competency final score. Every value is
 * a percentage (110 = 110%) kept at 4 decimals; nothing here touches the database.
 */
class KpiScoringEngine
{
    /** A = 2 − actual/target: stays finite when the actual is 0 (spec default). */
    public const LOWER_BETTER_COMPLEMENT = 'complement';

    /** A = target/actual. */
    public const LOWER_BETTER_RATIO = 'ratio';

    /** @var array<int, float|int> qualitative rating → achievement % */
    public const DEFAULT_QUALITATIVE_SCALE = [1 => 0, 2 => 60, 3 => 100, 4 => 110, 5 => 120];

    /** Points lost per 1% the actual strays outside a range KPI's bounds. */
    public const DEFAULT_RANGE_PENALTY = 5;

    public const RATING_CATEGORIES = ['not_meeting', 'partially_meeting', 'meeting', 'exceeding', 'far_exceeding'];

    public function __construct(
        private readonly string $lowerBetterMethod = self::LOWER_BETTER_COMPLEMENT,
        private readonly float $rangePenalty = self::DEFAULT_RANGE_PENALTY,
    ) {}

    /**
     * Achievement % of one actual against its KPI definition.
     *
     * @param  array{type:string, direction:string, target?:float|int|string|null, range_min?:float|int|string|null, range_max?:float|int|string|null, scale?:array<int|string, float|int>|null}  $kpi
     */
    public function achievement(array $kpi, float $actual): float
    {
        $target = (float) ($kpi['target'] ?? 0);

        $achievement = match (true) {
            $kpi['type'] === 'binary' => $this->binary($kpi['direction'], $actual),
            $kpi['type'] === 'qualitative' => (float) (($kpi['scale'] ?? null ?: self::DEFAULT_QUALITATIVE_SCALE)[(int) round($actual)] ?? 0),
            $kpi['direction'] === 'lower_better' => $this->lowerBetter($target, $actual),
            $kpi['direction'] === 'range' => $this->range((float) ($kpi['range_min'] ?? 0), (float) ($kpi['range_max'] ?? 0), $actual),
            default => $this->higherBetter($target, $actual),
        };

        return round(max(0.0, $achievement), 4);
    }

    /**
     * Item score after the threshold/stretch/cap rules (spec §6.2, linear curve):
     * below threshold scores 0, above stretch or cap is cut at the smaller of the two.
     */
    public function itemScore(float $achievement, ?float $threshold, ?float $stretch, ?float $cap): float
    {
        if ($threshold !== null && $achievement < $threshold) {
            return 0.0;
        }

        $ceiling = collect([$stretch, $cap])->filter(fn (?float $value): bool => $value !== null)->min();

        return round($ceiling !== null ? min($achievement, (float) $ceiling) : $achievement, 4);
    }

    /**
     * Σ weight × score; an item with no actual yet contributes nothing.
     *
     * @param  iterable<array{weight: float|int|string, score: float|int|string|null}>  $items
     */
    public function kpiScore(iterable $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['weight'] / 100 * (float) ($item['score'] ?? 0);
        }

        return round($total, 4);
    }

    /**
     * KPI block × its share + competency block × its share. Null while a block that
     * carries weight has no score yet.
     */
    public function finalScore(?float $kpiScore, ?float $competencyScore, float $kpiShare, float $competencyShare): ?float
    {
        if (($kpiShare > 0 && $kpiScore === null) || ($competencyShare > 0 && $competencyScore === null)) {
            return null;
        }

        return round((float) $kpiScore * $kpiShare / 100 + (float) $competencyScore * $competencyShare / 100, 4);
    }

    /**
     * Default rating bands (spec §6.5): <70, 70–89, 90–105, 106–115, >115.
     */
    public function ratingCategory(?float $score): ?string
    {
        return match (true) {
            $score === null => null,
            $score > 115 => 'far_exceeding',
            $score > 105 => 'exceeding',
            $score >= 90 => 'meeting',
            $score >= 70 => 'partially_meeting',
            default => 'not_meeting',
        };
    }

    private function higherBetter(float $target, float $actual): float
    {
        if ($target == 0.0) {
            return $actual >= 0 ? 100.0 : 0.0;
        }

        return $actual / $target * 100;
    }

    private function lowerBetter(float $target, float $actual): float
    {
        if ($target == 0.0) {
            return $actual <= 0 ? 100.0 : 0.0;
        }

        // ponytail: ratio with a zero actual is unbounded; it falls back to the complement's 200%.
        if ($this->lowerBetterMethod === self::LOWER_BETTER_RATIO && $actual > 0) {
            return $target / $actual * 100;
        }

        return (2 - $actual / $target) * 100;
    }

    private function range(float $min, float $max, float $actual): float
    {
        if ($actual >= $min && $actual <= $max) {
            return 100.0;
        }

        $bound = $actual < $min ? $min : $max;
        $deviationPct = $bound == 0.0 ? abs($actual) * 100 : abs($actual - $bound) / abs($bound) * 100;

        return 100 - $deviationPct * $this->rangePenalty;
    }

    private function binary(string $direction, float $actual): float
    {
        $achieved = $direction === 'lower_better' ? $actual <= 0 : $actual >= 1;

        return $achieved ? 100.0 : 0.0;
    }
}
