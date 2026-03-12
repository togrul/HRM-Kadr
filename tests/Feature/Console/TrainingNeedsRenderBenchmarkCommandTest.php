<?php

namespace Tests\Feature\Console;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrainingNeedsRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('training-needs:render-benchmark', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('training_needs_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_reports_render_metrics_for_training_dashboard_flows(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPermissions($user);

        $program = TrainingProgram::query()->create([
            'title' => 'Benchmark Program',
            'slug' => 'benchmark-program',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => 'Benchmark Plan',
            'plan_year' => 2026,
            'status' => 'draft',
        ]);

        $session = TrainingSession::query()->create([
            'training_annual_plan_id' => $plan->id,
            'training_program_id' => $program->id,
            'title' => 'Benchmark Session',
            'scheduled_start_at' => '2026-04-01 10:00:00',
            'scheduled_end_at' => '2026-04-01 12:00:00',
            'status' => 'scheduled',
            'auto_fill_participants' => false,
        ]);

        TrainingFeedbackForm::query()->create([
            'training_session_id' => $session->id,
            'title' => 'Benchmark Feedback',
            'status' => 'open',
            'questions' => [
                ['type' => 'rating', 'text' => 'Question'],
            ],
        ]);

        $exitCode = Artisan::call('training-needs:render-benchmark', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'planning_render.status'));
        $this->assertSame('ok', data_get($results, 'calendar_render.status'));
        $this->assertSame('ok', data_get($results, 'analytics_render.status'));
        $this->assertSame('ok', data_get($results, 'results_summary_render.status'));
        $this->assertSame('ok', data_get($results, 'session_detail_workspace_render.status'));
        $this->assertSame('ok', data_get($results, 'calendar_session_detail_update.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'planning_render.response_bytes'));
        $this->assertGreaterThan(0, (int) data_get($results, 'calendar_session_detail_update.response_bytes'));
    }

    public function test_it_provisions_temporary_session_fixture_when_dataset_has_no_session(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPermissions($user);

        TrainingProgram::query()->create([
            'title' => 'Benchmark Program',
            'slug' => 'benchmark-program',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        TrainingAnnualPlan::query()->create([
            'title' => 'Benchmark Plan',
            'plan_year' => 2026,
            'status' => 'draft',
        ]);

        $exitCode = Artisan::call('training-needs:render-benchmark', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame('ok', data_get($results, 'session_detail_workspace_render.status'));
        $this->assertSame('ok', data_get($results, 'calendar_session_detail_update.status'));
        $this->assertSame(0, TrainingSession::query()->count());
        $this->assertSame(0, TrainingFeedbackForm::query()->count());
    }

    private function grantPermissions(\App\Models\User $user): void
    {
        foreach ([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ]);
    }
}
