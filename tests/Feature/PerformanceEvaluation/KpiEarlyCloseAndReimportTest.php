<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceBonusRule;
use App\Models\PerformanceKpiActual;
use App\Models\PerformanceScorecard;
use App\Models\Position;
use App\Models\User;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\BonusService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiActualsImportService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

/**
 * KPI siyasətləri: işdən çıxma ilə vaxtından əvvəl bağlanan kartın bonusu
 * (`early_close_policy`) və faktların təkrar idxalının əvəzetmə (snapshot) qaydası.
 * Əsaslandırma: docs/performance-evaluation-module-design.md.
 */
class KpiEarlyCloseAndReimportTest extends TestCase
{
    use BuildsKpiCards;
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        User::created(fn (User $user) => grantAllStructures($user));

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = User::factory()->create(['email' => 'hr@example.test']);
        $this->hr->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->actingAs($this->hr);
    }

    /** 2026 Q1 = 90 gün; 2026-02-14-də çıxan işçi 45 gün işləyib. */
    private function cardClosedOn(string $reason): PerformanceScorecard
    {
        $card = $this->approvedSpecCard();
        $this->mock(CompensationReadRepository::class, fn ($mock) => $mock->shouldReceive('baseAmountsFor')->andReturn(collect([$card->personnel->tabel_no => 1000.0])));
        app(ScorecardService::class)->closeEarly($card, $reason, Carbon::parse('2026-02-14'));

        return $card->refresh()->load('personnel', 'cycle');
    }

    public function test_a_leavers_bonus_is_prorated_to_the_days_worked_by_default(): void
    {
        $card = $this->cardClosedOn('terminated');
        $bonus = app(BonusService::class);
        $rule = $bonus->rule($card->cycle);

        $this->assertSame('prorata', $rule->early_close_policy);
        $this->assertSame('0.5000', $card->prorata_factor);

        $row = $bonus->preview($card->cycle, $rule)['rows']->first();
        // 1000 × 3 ay × 15% × 50% ödəniş (bal 81.5) = 225; 45/90 gün → 112.5
        $this->assertSame(0.5, $row['prorata']);
        $this->assertSame(112.5, $row['amount']);
        $this->assertSame(112.5, $bonus->estimate($card, $card->effectiveScore()));

        $bonus->calculate($card->cycle);
        $this->assertSame(112.5, $card->refresh()->bonus?->amount);
        $this->assertSame(0.5, (float) $card->bonus?->prorata);
    }

    public function test_full_and_none_policies_are_configurable_per_rule_and_by_default(): void
    {
        $card = $this->cardClosedOn('terminated');
        $bonus = app(BonusService::class);

        $bonus->saveRule($card->cycle, [...$this->ruleData($bonus, $card->cycle), 'early_close_policy' => 'full'], $this->hr);
        $this->assertSame(225.0, $bonus->preview($card->cycle, $bonus->rule($card->cycle))['rows']->first()['amount']);

        $bonus->saveRule($card->cycle, [...$this->ruleData($bonus, $card->cycle), 'early_close_policy' => 'none'], $this->hr);
        $this->assertSame(0.0, $bonus->preview($card->cycle, $bonus->rule($card->cycle))['rows']->first()['amount']);

        $this->assertValidationKeys(['early_close_policy'], fn () => $bonus->saveRule($card->cycle, [...$this->ruleData($bonus, $card->cycle), 'early_close_policy' => 'double'], $this->hr));

        // Yeni dövrün qaydası standartını konfiqurasiyadan alır.
        config(['performance_evaluation.kpi.early_close_bonus' => 'none']);
        PerformanceBonusRule::query()->where('performance_cycle_id', $card->cycle->id)->delete();
        $this->assertSame('none', $bonus->rule($card->cycle->fresh())->early_close_policy);
    }

    public function test_a_position_change_stays_prorated_even_under_the_full_policy(): void
    {
        $card = $this->cardClosedOn('position_changed');
        $bonus = app(BonusService::class);
        $rule = $bonus->rule($card->cycle)->fill(['early_close_policy' => 'full']);

        // Dövrün qalanını yeni kart əhatə edir: «tam» eyni günləri iki dəfə ödəyərdi.
        $this->assertSame(112.5, $bonus->preview($card->cycle, $rule)['rows']->first()['amount']);
    }

    public function test_reimport_replaces_the_previous_import_and_leaves_manual_actuals_alone(): void
    {
        $card = $this->specExampleCard();
        $scorecards = app(ScorecardService::class);
        $import = app(KpiActualsImportService::class);
        $scorecards->transition($card, 'activate', $this->hr);

        // SALES cəmlənən göstəricidir: idxal toplansaydı, nəticə ikiqat olardı.
        $sales = $card->items[0];
        $version = $sales->kpiVersion;
        $version->snapshot = [...(array) $version->snapshot, 'aggregation' => 'sum'];
        $version->save();

        $scorecards->recordActual($sales, 5000, $this->hr, note: 'əl ilə');

        $sheet = fn (float $value): array => collect($import->templateRows($card->cycle))
            ->filter(fn (array $row): bool => $row['item_id'] === $sales->id)
            ->map(function (array $row) use ($value): array {
                $row['value'] = $value;

                return array_values($row);
            })
            ->values()
            ->all();

        $this->assertSame(1, $import->import($card->cycle, $sheet(90000), $this->hr)['imported']);
        $this->assertSame(1, $import->import($card->cycle, $sheet(100000), $this->hr)['imported']);
        $this->assertSame(1, $import->import($card->cycle, $sheet(100000), $this->hr)['imported']);

        $imported = PerformanceKpiActual::query()->where('performance_scorecard_item_id', $sales->id)->where('source', 'import')->get();
        $this->assertCount(1, $imported);
        $this->assertSame('100000.0000', $imported->first()->value);

        $manual = PerformanceKpiActual::query()->where('performance_scorecard_item_id', $sales->id)->where('source', 'manual')->sole();
        $this->assertSame('5000.0000', $manual->value);
        $this->assertSame('105000.0000', $sales->fresh()->actual);

        // Köhnə dəyər audit izində qalır.
        $update = Activity::query()
            ->where('subject_type', PerformanceKpiActual::class)
            ->where('subject_id', $imported->first()->id)
            ->where('event', 'updated')
            ->orderBy('id')
            ->firstOrFail();
        $this->assertEquals(90000, (float) $update->properties['old']['value']);
        $this->assertEquals(100000, (float) $update->properties['attributes']['value']);
    }

    public function test_reimport_collapses_duplicates_left_by_the_old_summing_import(): void
    {
        $card = $this->specExampleCard();
        $scorecards = app(ScorecardService::class);
        $scorecards->transition($card, 'activate', $this->hr);
        $sales = $card->items[0];

        foreach ([40000, 50000] as $value) {
            $sales->actuals()->create(['value' => $value, 'source' => 'import', 'entered_by' => $this->hr->id, 'approved_by' => $this->hr->id, 'approved_at' => now()]);
        }

        $scorecards->recordActual($sales, 70000, $this->hr, null, null, 'import', replace: true);

        $this->assertSame(['70000.0000'], $sales->actuals()->where('source', 'import')->pluck('value')->all());
        $this->assertSame(1, Activity::query()->where('subject_type', PerformanceKpiActual::class)->where('event', 'deleted')->count());
    }
}
