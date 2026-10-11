<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\CareerReadinessScoreService;
use App\Modules\PerformanceEvaluation\Application\Services\SuccessionService;
use App\Modules\PerformanceEvaluation\Livewire\SuccessionWorkspace;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Karyera hazırlıq skoru: konfiq çəkili, izah edilə bilən, struktur görünürlüyü ilə məhdud.
 */
class CareerReadinessScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 10:00:00');
        config(['strategic_hr.career_readiness.weights' => ['performance' => 35, 'training' => 20, 'kpi' => 20, 'portfolio' => 10, 'tenure' => 15]]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_score_is_the_weighted_sum_of_normalized_components(): void
    {
        $person = $this->makePersonnel('Hazır', '2016-10-11');
        $this->seedSignals($person, performance: 80.0, trainings: 2, kpi: 110.0, portfolio: 5);

        $result = app(CareerReadinessScoreService::class)->scoresFor([$person->id])[$person->id];
        $components = collect($result['components'])->keyBy('key');

        // performance 80/100 → 80 × 35% = 28; training 2/4 → 50 × 20% = 10; KPI 110 ≥ 100 → 100 × 20% = 20;
        // portfolio 5/5 → 100 × 10% = 10; tenure 10 il ≥ 5 → 100 × 15% = 15.  Cəm 83.
        $this->assertSame(80, $components['performance']['normalized']);
        $this->assertSame(28.0, $components['performance']['contribution']);
        $this->assertSame(50, $components['training']['normalized']);
        $this->assertSame(10.0, $components['training']['contribution']);
        $this->assertSame(100, $components['kpi']['normalized']);
        $this->assertSame(100, $components['portfolio']['normalized']);
        $this->assertSame(15.0, $components['tenure']['contribution']);
        $this->assertSame(83, $result['score']);
        $this->assertSame('ready', $result['band']);
        $this->assertEqualsWithDelta($result['score'], collect($result['components'])->sum('contribution'), 0.5);
    }

    public function test_missing_data_contributes_nothing_and_is_labelled(): void
    {
        $person = $this->makePersonnel('Yeni', '2026-04-11');

        $result = app(CareerReadinessScoreService::class)->scoresFor([$person->id])[$person->id];
        $performance = collect($result['components'])->firstWhere('key', 'performance');

        $this->assertNull($performance['value']);
        $this->assertSame(0, $performance['normalized']);
        $this->assertSame(__('performance_evaluation::succession.readiness_score.no_data'), $performance['display']);
        // Yalnız staj: 0.5 il / 5 il = 10 → 10 × 15% = 1.5 → 2.
        $this->assertSame(2, $result['score']);
        $this->assertSame('early', $result['band']);
    }

    public function test_weights_come_from_config_and_are_normalized_to_one_hundred(): void
    {
        config(['strategic_hr.career_readiness.weights' => ['performance' => 1, 'training' => 0, 'kpi' => 0, 'portfolio' => 0, 'tenure' => 1]]);
        $person = $this->makePersonnel('Çəki', '2016-10-11');
        $this->seedSignals($person, performance: 60.0, trainings: 0, kpi: null, portfolio: 0);

        $result = app(CareerReadinessScoreService::class)->scoresFor([$person->id])[$person->id];

        $this->assertSame(['performance', 'tenure'], array_column($result['components'], 'key'));
        $this->assertSame([50.0, 50.0], array_column($result['components'], 'weight'));
        $this->assertSame(80, $result['score']); // 60 × 50% + 100 × 50%
    }

    public function test_only_final_kpi_scorecards_and_recent_completed_trainings_count(): void
    {
        $person = $this->makePersonnel('Filtr', '2016-10-11');
        $cycle = $this->cycle();
        DB::table('performance_scorecards')->insert([
            ['performance_cycle_id' => $cycle->id, 'personnel_id' => $person->id, 'status' => 'draft', 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'final_score' => 120, 'created_at' => now(), 'updated_at' => now()],
            ['performance_cycle_id' => $cycle->id, 'personnel_id' => $person->id, 'status' => 'approved', 'valid_from' => '2025-01-01', 'valid_to' => '2025-12-31', 'final_score' => 70, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $session = DB::table('training_sessions')->insertGetId(['title' => 'Köhnə', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('training_delivery_records')->insert([
            'training_session_id' => $session, 'personnel_id' => $person->id, 'result_status' => 'completed',
            'completed_at' => '2020-01-01 10:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $components = collect(app(CareerReadinessScoreService::class)->scoresFor([$person->id])[$person->id]['components'])->keyBy('key');

        $this->assertSame(70.0, $components['kpi']['value']);
        $this->assertNull($components['training']['value']);
    }

    public function test_scores_are_limited_to_the_viewers_structures_and_batched(): void
    {
        $inside = $this->makePersonnel('Daxili', '2016-10-11');
        $outside = $this->makePersonnel('Kənar', '2016-10-11');
        $this->seedSignals($inside, performance: 90.0, trainings: 1, kpi: 100.0, portfolio: 1);
        $this->seedSignals($outside, performance: 90.0, trainings: 1, kpi: 100.0, portfolio: 1);
        $viewer = grantStructures(User::factory()->create(), [$inside->structure_id]);

        // Görünürlük və cədvəl siyahısı hər səhifədə keşlənir — ölçmədən əvvəl isidilir.
        app(\App\Services\StructureService::class)->scopeFor($viewer);
        app(CareerReadinessScoreService::class)->scoresFor([$inside->id], $viewer);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $scores = app(CareerReadinessScoreService::class)->scoresFor([$inside->id, $outside->id], $viewer);
        app('events')->forget(QueryExecuted::class);

        $this->assertSame([$inside->id], array_keys($scores));
        // personnels + 4 komponent sorğusu (staj personnels sətrindən gəlir) = ən çox 5 sorğu, işçi sayından asılı deyil.
        $this->assertLessThanOrEqual(5, count($queries), implode("\n", $queries));
    }

    public function test_the_succession_workspace_shows_the_score_with_its_breakdown(): void
    {
        $inside = $this->makePersonnel('Varis', '2016-10-11');
        $outside = $this->makePersonnel('Görünməz', '2016-10-11');
        $this->seedSignals($inside, performance: 80.0, trainings: 2, kpi: 110.0, portfolio: 5);
        $service = app(SuccessionService::class);
        $plan = $service->createPlan(['role_title' => 'Departament rəhbəri']);
        $service->addCandidate($plan->id, $inside->id, '1_2_years');
        $service->addCandidate($plan->id, $outside->id, '3_5_years');

        $user = grantStructures(User::factory()->create(), [$inside->structure_id]);
        $user->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->actingAs($user);

        Livewire::test(SuccessionWorkspace::class)
            ->call('setSection', 'plans')
            ->assertSeeHtml('data-readiness-score="83"')
            ->assertSeeHtml('data-readiness-breakdown')
            ->assertSee(__('performance_evaluation::succession.readiness_score.components.performance'))
            ->assertSee('+28.0')
            ->assertDontSeeHtml('data-readiness-score="15"');
    }

    private function seedSignals(Personnel $person, float $performance, int $trainings, ?float $kpi, int $portfolio): void
    {
        $cycle = $this->cycle();
        $template = DB::table('performance_form_templates')->insertGetId(['name' => 'Şablon '.Str::random(4), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('performance_forms')->insert([
            ['performance_cycle_id' => $cycle->id, 'performance_form_template_id' => $template, 'personnel_id' => $person->id, 'final_score' => 40, 'created_at' => now()->subYear(), 'updated_at' => now()->subYear()],
            ['performance_cycle_id' => $cycle->id, 'performance_form_template_id' => $template, 'personnel_id' => $person->id, 'final_score' => $performance, 'created_at' => now(), 'updated_at' => now()],
        ]);

        for ($i = 0; $i < $trainings; $i++) {
            $session = DB::table('training_sessions')->insertGetId(['title' => 'Təlim '.$i, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('training_delivery_records')->insert([
                'training_session_id' => $session, 'personnel_id' => $person->id, 'result_status' => 'completed',
                'completed_at' => now()->subMonths($i + 1), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if ($kpi !== null) {
            DB::table('performance_scorecards')->insert([
                'performance_cycle_id' => $cycle->id, 'personnel_id' => $person->id, 'status' => 'closed',
                'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'final_score' => $kpi, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        for ($i = 0; $i < $portfolio; $i++) {
            DB::table('personnel_event_records')->insert([
                'personnel_id' => $person->id, 'event_type' => 'conference', 'participation_role' => 'speaker', 'title' => 'Tədbir '.$i,
                'start_date' => '2025-05-01', 'verification_status' => 'verified', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('personnel_event_records')->insert([
            'personnel_id' => $person->id, 'event_type' => 'conference', 'participation_role' => 'speaker', 'title' => 'Təsdiqsiz',
            'start_date' => '2025-05-01', 'verification_status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function cycle(): PerformanceCycle
    {
        return PerformanceCycle::query()->create([
            'name' => 'Dövr '.Str::random(4), 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'status' => 'active',
        ]);
    }

    private function makePersonnel(string $surname, string $joinDate): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'CR'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'join_work_date' => $joinDate, 'added_by' => 1, 'is_pending' => false,
        ]));
    }
}
