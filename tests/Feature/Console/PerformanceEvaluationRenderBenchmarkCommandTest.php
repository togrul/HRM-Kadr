<?php

namespace Tests\Feature\Console;

use App\Models\Personnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerformanceEvaluationRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('performance-evaluation:render-benchmark', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('performance_evaluation_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_reports_render_metrics_for_performance_flows(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPermissions($user);
        $personnel = $this->createPersonnel($user->id);

        DB::table('performance_cycles')->insert([
            'id' => 1,
            'name' => '2026 Annual',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'draft',
            'auto_generate_forms' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('performance_form_templates')->insert([
            'id' => 1,
            'name' => 'Main Template',
            'code' => 'TMP-1',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('performance_form_template_sections')->insert([
            'id' => 1,
            'performance_form_template_id' => 1,
            'name' => 'Section 1',
            'weight_percent' => 100,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('performance_form_template_items')->insert([
            'id' => 1,
            'performance_form_template_section_id' => 1,
            'name' => 'Criterion 1',
            'weight_percent' => 100,
            'low_score_threshold' => 60,
            'requires_comment' => false,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('performance_forms')->insert([
            'id' => 1,
            'performance_cycle_id' => 1,
            'performance_form_template_id' => 1,
            'personnel_id' => $personnel->id,
            'manager_id' => $user->id,
            'hr_reviewer_id' => $user->id,
            'self_status' => 'draft',
            'manager_status' => 'draft',
            'hr_status' => 'draft',
            'result_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('performance_test_banks')->insert([
            'id' => 1,
            'name' => 'Benchmark Bank',
            'code' => 'BNK-1',
            'pass_score' => 60,
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Render-time (ms) budgets are machine-speed dependent and flake on slow/cold
        // CI runners and dev machines. Pass generous render-time ceilings so this test
        // deterministically asserts that the flows render successfully and within their
        // payload-size budgets; absolute render-time gating is handled by the
        // env-configurable benchmark run in CI, not by this feature test.
        $exitCode = Artisan::call('performance-evaluation:render-benchmark', [
            '--json' => true,
            '--overview-render-budget' => 5000,
            '--evaluations-summary-render-budget' => 5000,
            '--tests-summary-render-budget' => 5000,
            '--workspace-render-budget' => 5000,
            '--score-capture-render-budget' => 5000,
            '--score-open-render-budget' => 5000,
        ]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'overview_render.status'));
        $this->assertSame('ok', data_get($results, 'evaluations_summary_render.status'));
        $this->assertSame('ok', data_get($results, 'tests_summary_render.status'));
        $this->assertSame('ok', data_get($results, 'evaluator_workspace_render.status'));
        $this->assertSame('ok', data_get($results, 'score_capture_render.status'));
        $this->assertSame('ok', data_get($results, 'evaluator_open_score_form_update.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'evaluator_workspace_render.response_bytes'));
        $this->assertGreaterThan(0, (int) data_get($results, 'evaluator_open_score_form_update.response_bytes'));
    }

    private function grantPermissions(\App\Models\User $user): void
    {
        foreach ([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]);
    }

    private function createPersonnel(int $userId): Personnel
    {
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'DMX',
            'shortname' => 'DMX',
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'name' => 'Analyst',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);

        return Personnel::query()->create([
            'tabel_no' => 'PF-001',
            'surname' => 'Aliyev',
            'name' => 'Murad',
            'patronymic' => 'Rashad',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'phone' => '0120000000',
            'mobile' => '0500000000',
            'email' => 'murad@example.test',
            'nationality_id' => 1,
            'pin' => 'ABC1234',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => $userId,
        ]);
    }
}
