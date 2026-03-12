<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceEvaluationQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('performance-evaluation:query-budget', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('performance_evaluation_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_stays_within_default_query_budgets_for_larger_dataset(): void
    {
        foreach (range(1, 80) as $index) {
            DB::table('performance_cycles')->insert([
                'name' => 'Cycle '.$index,
                'cycle_type' => 'annual',
                'period_start' => now()->startOfYear()->toDateString(),
                'period_end' => now()->endOfYear()->toDateString(),
                'status' => 'draft',
                'auto_generate_forms' => true,
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('performance_form_templates')->insert([
                'name' => 'Template '.$index,
                'code' => 'PF'.$index,
                'description' => 'Description '.$index,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('performance_test_banks')->insert([
                'name' => 'Bank '.$index,
                'code' => 'TB'.$index,
                'description' => null,
                'pass_score' => 60,
                'duration_minutes' => 30,
                'max_attempts' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('performance_form_template_sections')->insert([
                'performance_form_template_id' => $index,
                'name' => 'Section '.$index,
                'weight_percent' => 100,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('performance_form_template_items')->insert([
                'performance_form_template_section_id' => $index,
                'training_competency_id' => null,
                'name' => 'Item '.$index,
                'description' => null,
                'weight_percent' => 100,
                'low_score_threshold' => 60,
                'requires_comment' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (range(1, 3) as $questionIndex) {
                DB::table('performance_test_questions')->insert([
                    'performance_test_bank_id' => $index,
                    'training_competency_id' => null,
                    'question_type' => $questionIndex === 1 ? 'multiple_choice' : ($questionIndex === 2 ? 'open_answer' : 'behavioral'),
                    'prompt' => 'Question '.$index.'-'.$questionIndex,
                    'max_score' => 100,
                    'sort_order' => $questionIndex,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($questionIndex === 1) {
                    foreach (range(1, 4) as $optionIndex) {
                        DB::table('performance_test_question_options')->insert([
                            'performance_test_question_id' => (($index - 1) * 3) + $questionIndex,
                            'label' => 'Option '.$index.'-'.$questionIndex.'-'.$optionIndex,
                            'is_correct' => $optionIndex === 1,
                            'score_value' => $optionIndex === 1 ? 100 : 0,
                            'sort_order' => $optionIndex,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $exitCode = Artisan::call('performance-evaluation:query-budget', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertLessThanOrEqual(config('performance_evaluation.performance.query_budget.overview_build'), (int) data_get($payload, 'results.0.queries'));
        $this->assertLessThanOrEqual(config('performance_evaluation.performance.query_budget.templates_build'), (int) data_get($payload, 'results.1.queries'));
        $this->assertLessThanOrEqual(config('performance_evaluation.performance.query_budget.tests_build'), (int) data_get($payload, 'results.2.queries'));
    }
}
