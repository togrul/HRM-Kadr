<?php

namespace Tests\Unit\Services;

use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiScoringEngine;
use PHPUnit\Framework\TestCase;

class KpiScoringEngineTest extends TestCase
{
    private KpiScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new KpiScoringEngine;
    }

    /**
     * Spec §6.4: sales manager, KPI share 70%, competency 30%, threshold 80%, cap 120%.
     */
    public function test_spec_worked_example_scores_81_5_and_87_05(): void
    {
        $rows = [
            ['weight' => 40, 'target' => 100000, 'actual' => 110000],
            ['weight' => 25, 'target' => 20, 'actual' => 18],
            ['weight' => 20, 'target' => 95, 'actual' => 71],
            ['weight' => 15, 'target' => 100, 'actual' => 100],
        ];

        $items = array_map(function (array $row): array {
            $achievement = $this->engine->achievement(['type' => 'quantitative', 'direction' => 'higher_better', 'target' => $row['target']], $row['actual']);

            return ['weight' => $row['weight'], 'score' => $this->engine->itemScore($achievement, 80, null, 120)];
        }, $rows);

        $kpiScore = $this->engine->kpiScore($items);

        $this->assertSame([110.0, 90.0, 0.0, 100.0], array_column($items, 'score'));
        $this->assertSame(81.5, $kpiScore);
        $this->assertSame(87.05, $this->engine->finalScore($kpiScore, 100, 70, 30));
    }

    public function test_lower_better_with_zero_actual_is_finite_and_capped(): void
    {
        $kpi = ['type' => 'quantitative', 'direction' => 'lower_better', 'target' => 5];

        $achievement = $this->engine->achievement($kpi, 0);
        $this->assertSame(200.0, $achievement);
        $this->assertSame(120.0, $this->engine->itemScore($achievement, 80, null, 120));

        $ratio = new KpiScoringEngine(KpiScoringEngine::LOWER_BETTER_RATIO);
        $this->assertSame(200.0, $ratio->achievement($kpi, 0));
        $this->assertSame(50.0, $ratio->achievement($kpi, 10));
        $this->assertSame(0.0, $this->engine->achievement($kpi, 15)); // 2 − 3 → floored at 0
    }

    public function test_threshold_zeroes_and_the_smaller_of_stretch_and_cap_cuts(): void
    {
        $this->assertSame(0.0, $this->engine->itemScore(79.99, 80, 130, 120));
        $this->assertSame(80.0, $this->engine->itemScore(80, 80, 130, 120));
        $this->assertSame(115.0, $this->engine->itemScore(140, 80, 115, 120));
        $this->assertSame(120.0, $this->engine->itemScore(140, 80, 130, 120));
        $this->assertSame(140.0, $this->engine->itemScore(140, null, null, null));
    }

    public function test_range_binary_and_qualitative_achievements(): void
    {
        $range = ['type' => 'quantitative', 'direction' => 'range', 'range_min' => 90, 'range_max' => 110];
        $this->assertSame(100.0, $this->engine->achievement($range, 95));
        $this->assertSame(90.0, $this->engine->achievement($range, 112.2)); // 2% over → −10 points

        $this->assertSame(100.0, $this->engine->achievement(['type' => 'binary', 'direction' => 'higher_better'], 1));
        $this->assertSame(0.0, $this->engine->achievement(['type' => 'binary', 'direction' => 'higher_better'], 0));
        $this->assertSame(100.0, $this->engine->achievement(['type' => 'binary', 'direction' => 'lower_better'], 0));

        $this->assertSame(110.0, $this->engine->achievement(['type' => 'qualitative', 'direction' => 'higher_better'], 4));
        $this->assertSame(75.0, $this->engine->achievement(['type' => 'qualitative', 'direction' => 'higher_better', 'scale' => [3 => 75]], 3));
    }

    public function test_final_score_waits_for_every_weighted_block_and_maps_to_a_rating(): void
    {
        $this->assertNull($this->engine->finalScore(81.5, null, 70, 30));
        $this->assertSame(81.5, $this->engine->finalScore(81.5, null, 100, 0));

        $this->assertSame('not_meeting', $this->engine->ratingCategory(69.99));
        $this->assertSame('partially_meeting', $this->engine->ratingCategory(70));
        $this->assertSame('meeting', $this->engine->ratingCategory(105));
        $this->assertSame('exceeding', $this->engine->ratingCategory(115));
        $this->assertSame('far_exceeding', $this->engine->ratingCategory(115.01));
        $this->assertNull($this->engine->ratingCategory(null));
    }
}
