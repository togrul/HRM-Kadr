<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingNeedsQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('training-needs:query-budget', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('training_needs_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_stays_within_default_query_budgets_for_larger_dataset(): void
    {
        foreach (range(1, 6) as $index) {
            DB::table('training_levels')->insert([
                'name' => 'Level '.$index,
                'score' => $index * 10,
                'sort_order' => $index,
                'description' => null,
                'is_default' => $index === 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('training_competency_groups')->insert([
            'name' => 'Functional',
            'slug' => 'functional',
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 100) as $index) {
            DB::table('training_competencies')->insert([
                'training_competency_group_id' => 1,
                'name' => 'Competency '.$index,
                'slug' => 'competency-'.$index,
                'description' => null,
                'is_mandatory' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('training_programs')->insert([
                'title' => 'Program '.$index,
                'slug' => 'program-'.$index,
                'code' => 'TP'.$index,
                'delivery_type' => 'internal',
                'duration_hours' => 8,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('training_program_competency_map')->insert([
                'training_program_id' => $index,
                'training_competency_id' => $index,
                'target_level_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (range(1, 25) as $index) {
            DB::table('training_annual_plans')->insert([
                'title' => 'Plan '.$index,
                'plan_year' => now()->year,
                'plan_quarter' => $index % 4 === 0 ? null : (($index % 4) + 1),
                'status' => 'review',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (range(1, 150) as $index) {
            DB::table('training_plan_items')->insert([
                'training_annual_plan_id' => (($index - 1) % 25) + 1,
                'training_program_id' => (($index - 1) % 100) + 1,
                'training_competency_id' => (($index - 1) % 100) + 1,
                'position_id' => null,
                'target_level_id' => (($index - 1) % 6) + 1,
                'priority' => $index % 3 === 0 ? 'high' : ($index % 2 === 0 ? 'medium' : 'low'),
                'participant_count' => (($index - 1) % 12) + 1,
                'need_count' => (($index - 1) % 5) + 1,
                'estimated_budget' => 100 + $index,
                'source_mix' => 'performance_gap,skill_gap',
                'review_status' => 'approved',
                'suggested_score' => 50 + $index,
                'review_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (range(1, 120) as $index) {
            DB::table('training_sessions')->insert([
                'training_annual_plan_id' => (($index - 1) % 25) + 1,
                'training_program_id' => (($index - 1) % 100) + 1,
                'title' => 'Session '.$index,
                'location' => 'Room '.$index,
                'trainer_name' => 'Trainer '.$index,
                'scheduled_start_at' => now()->addDays($index),
                'scheduled_end_at' => now()->addDays($index)->addHours(4),
                'capacity' => 20,
                'planned_budget' => 250 + $index,
                'status' => 'scheduled',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (range(1, 60) as $index) {
            DB::table('training_feedback_forms')->insert([
                'training_session_id' => (($index - 1) % 120) + 1,
                'title' => 'Feedback '.$index,
                'questions' => json_encode([
                    ['prompt' => 'Question '.$index, 'type' => 'rating'],
                ], JSON_THROW_ON_ERROR),
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exitCode = Artisan::call('training-needs:query-budget', [
            '--year' => now()->year,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.overview_build'), (int) data_get($results, 'overview_build.queries'));
        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.planning_build'), (int) data_get($results, 'planning_build.queries'));
        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.calendar_build'), (int) data_get($results, 'calendar_build.queries'));
        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.analytics_build'), (int) data_get($results, 'analytics_build.queries'));
        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.results_summary_build'), (int) data_get($results, 'results_summary_build.queries'));
        $this->assertLessThanOrEqual(config('training_needs.performance.query_budget.reports_build'), (int) data_get($results, 'reports_build.queries'));
    }
}
