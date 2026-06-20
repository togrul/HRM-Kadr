<?php

namespace Tests\Feature\Reports;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Reports\Application\Services\ComparativeReportService;
use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsOverviewService;
use App\Modules\Reports\Application\Services\StandardReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsServiceRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_training_report_does_not_duplicate_participants_or_hours_when_session_has_multiple_feedback_rows(): void
    {
        $this->seedPersonnelSupportTables();

        $personnelA = $this->createPersonnelRecord('TR-001', 1);
        $personnelB = $this->createPersonnelRecord('TR-002', 1);

        DB::table('training_sessions')->insert([
            'id' => 1,
            'title' => 'Session 1',
            'scheduled_start_at' => '2026-02-10 10:00:00',
            'scheduled_end_at' => '2026-02-10 18:00:00',
            'status' => 'completed',
            'completed_at' => '2026-02-10 18:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('training_delivery_records')->insert([
            [
                'id' => 1,
                'training_session_id' => 1,
                'personnel_id' => $personnelA->id,
                'attended_hours' => 4,
                'result_status' => 'completed',
                'completed_at' => '2026-02-10 18:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'training_session_id' => 1,
                'personnel_id' => $personnelB->id,
                'attended_hours' => 4,
                'result_status' => 'completed',
                'completed_at' => '2026-02-10 18:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('training_feedback_forms')->insert([
            ['id' => 1, 'training_session_id' => 1, 'title' => 'Form A', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'training_session_id' => 1, 'title' => 'Form B', 'status' => 'closed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('training_feedback_responses')->insert([
            [
                'training_feedback_form_id' => 1,
                'training_session_id' => 1,
                'personnel_id' => $personnelA->id,
                'overall_score' => 5,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'training_feedback_form_id' => 2,
                'training_session_id' => 1,
                'personnel_id' => $personnelB->id,
                'overall_score' => 3,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = app(StandardReportService::class)->build('training', ['year' => 2026, 'month' => 2]);
        $row = collect($payload['rows'])->first();

        $this->assertSame(2, $row['participants_count']);
        $this->assertSame(8.0, $row['attended_hours']);
        $this->assertSame(4.0, $row['average_feedback_score']);
    }

    public function test_comparisons_apply_structure_scope_to_training_and_performance(): void
    {
        $this->seedPersonnelSupportTables();

        $personnelA = $this->createPersonnelRecord('CP-001', 1);
        $personnelB = $this->createPersonnelRecord('CP-002', 2);
        $user = User::factory()->create();

        DB::table('training_sessions')->insert([
            ['id' => 1, 'title' => 'S1', 'scheduled_start_at' => '2026-01-10 09:00:00', 'scheduled_end_at' => '2026-01-10 18:00:00', 'status' => 'completed', 'completed_at' => '2026-01-10 18:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'title' => 'S2', 'scheduled_start_at' => '2026-01-11 09:00:00', 'scheduled_end_at' => '2026-01-11 18:00:00', 'status' => 'completed', 'completed_at' => '2026-01-11 18:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('training_delivery_records')->insert([
            ['id' => 1, 'training_session_id' => 1, 'personnel_id' => $personnelA->id, 'attended_hours' => 5, 'result_status' => 'completed', 'completed_at' => '2026-01-10 18:00:00', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'training_session_id' => 2, 'personnel_id' => $personnelB->id, 'attended_hours' => 7, 'result_status' => 'completed', 'completed_at' => '2026-01-11 18:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('performance_cycles')->insert([
            [
                'id' => 1,
                'name' => '2026 Q1',
                'cycle_type' => 'quarterly',
                'period_start' => '2026-01-01',
                'period_end' => '2026-03-31',
                'status' => 'closed',
                'auto_generate_forms' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('performance_form_templates')->insert([
            ['id' => 1, 'name' => 'Template', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('performance_forms')->insert([
            [
                'id' => 1,
                'performance_cycle_id' => 1,
                'performance_form_template_id' => 1,
                'personnel_id' => $personnelA->id,
                'manager_id' => $user->id,
                'hr_reviewer_id' => $user->id,
                'self_status' => 'done',
                'manager_status' => 'done',
                'hr_status' => 'done',
                'final_score' => 90,
                'final_category' => 'high',
                'result_status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'performance_cycle_id' => 1,
                'performance_form_template_id' => 1,
                'personnel_id' => $personnelB->id,
                'manager_id' => $user->id,
                'hr_reviewer_id' => $user->id,
                'self_status' => 'done',
                'manager_status' => 'done',
                'hr_status' => 'done',
                'final_score' => 50,
                'final_category' => 'weak',
                'result_status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = app(ComparativeReportService::class)->build(2026, 3, 1);

        $this->assertSame([['label' => '2026', 'sessions_count' => 1, 'attended_hours' => 5.0]], $payload['training_years']);
        $this->assertSame([['label' => __('reports::dashboard.dynamic.performance_categories.high'), 'value' => 1]], $payload['performance_distribution']);
    }

    public function test_personnel_reports_use_selected_report_date_snapshot(): void
    {
        $this->seedPersonnelSupportTables();

        $active = $this->createPersonnelRecord('PD-001', 1, ['join_work_date' => '2025-01-01', 'gender' => 1]);
        $this->createPersonnelRecord('PD-002', 1, ['join_work_date' => '2026-01-10', 'leave_work_date' => '2026-02-15', 'gender' => 2]);
        $this->createPersonnelRecord('PD-003', 1, ['join_work_date' => '2026-04-01', 'gender' => 1]);

        $filters = ['year' => 2026, 'month' => 3, 'structure_id' => 1];

        $headcount = app(StandardReportService::class)->build('headcount', $filters);
        $status = app(DynamicReportBuilderService::class)->build('personnel', 'status', 'count', $filters);

        $this->assertSame(1, data_get($headcount, 'summary.0.value'));

        $statusRows = collect($status['rows'])->keyBy('group_label');
        $this->assertSame(1, data_get($statusRows, __('reports::dashboard.labels.active').'.metric_value'));
        $this->assertSame(1, data_get($statusRows, __('reports::dashboard.labels.terminated').'.metric_value'));

        $this->assertNotNull($active);
    }

    public function test_performance_reports_respect_selected_period_overlap(): void
    {
        $this->seedPersonnelSupportTables();

        $user = User::factory()->create();
        $personnel = $this->createPersonnelRecord('PF-001', 1);

        DB::table('performance_cycles')->insert([
            [
                'id' => 1,
                'name' => 'Q1 2026',
                'cycle_type' => 'quarterly',
                'period_start' => '2026-01-01',
                'period_end' => '2026-03-31',
                'status' => 'closed',
                'auto_generate_forms' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Q3 2026',
                'cycle_type' => 'quarterly',
                'period_start' => '2026-07-01',
                'period_end' => '2026-09-30',
                'status' => 'closed',
                'auto_generate_forms' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('performance_form_templates')->insert([
            ['id' => 1, 'name' => 'Template', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('performance_forms')->insert([
            [
                'id' => 1,
                'performance_cycle_id' => 1,
                'performance_form_template_id' => 1,
                'personnel_id' => $personnel->id,
                'manager_id' => $user->id,
                'hr_reviewer_id' => $user->id,
                'self_status' => 'done',
                'manager_status' => 'done',
                'hr_status' => 'done',
                'final_score' => 88,
                'final_category' => 'high',
                'result_status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'performance_cycle_id' => 2,
                'performance_form_template_id' => 1,
                'personnel_id' => $personnel->id,
                'manager_id' => $user->id,
                'hr_reviewer_id' => $user->id,
                'self_status' => 'done',
                'manager_status' => 'done',
                'hr_status' => 'done',
                'final_score' => 61,
                'final_category' => 'medium',
                'result_status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $marchStandard = app(StandardReportService::class)->build('performance', ['year' => 2026, 'month' => 3]);
        $marchDynamic = app(DynamicReportBuilderService::class)->build('performance', 'cycle', 'forms_count', ['year' => 2026, 'month' => 3]);
        $augustStandard = app(StandardReportService::class)->build('performance', ['year' => 2026, 'month' => 8]);

        $this->assertSame(['Q1 2026'], collect($marchStandard['rows'])->pluck('cycle_name')->all());
        $this->assertSame(['Q1 2026'], collect($marchDynamic['rows'])->pluck('group_label')->all());
        $this->assertSame(['Q3 2026'], collect($augustStandard['rows'])->pluck('cycle_name')->all());
    }

    public function test_overview_hires_and_exits_are_ytd_to_report_date(): void
    {
        $this->seedPersonnelSupportTables();

        $this->createPersonnelRecord('OV-001', 1, ['join_work_date' => '2026-01-05', 'leave_work_date' => '2026-02-10']);
        $this->createPersonnelRecord('OV-002', 1, ['join_work_date' => '2026-03-01']);
        $this->createPersonnelRecord('OV-003', 1, ['join_work_date' => '2025-06-01', 'leave_work_date' => '2026-03-05']);

        $payload = app(ReportsOverviewService::class)->build(2026, 3);

        $this->assertSame(2, data_get($payload, 'kpis.new_hires'));
        $this->assertSame(2, data_get($payload, 'kpis.exits'));
    }

    private function seedPersonnelSupportTables(): void
    {
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        DB::table('countries')->insertOrIgnore([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insertOrIgnore([
            ['id' => 1, 'name' => 'DMX', 'shortname' => 'DMX'],
            ['id' => 2, 'name' => 'OPS', 'shortname' => 'OPS'],
        ]);

        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Analyst',
        ]);

        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }

    private function createPersonnelRecord(string $tabelNo, int $structureId, array $overrides = []): Personnel
    {
        $addedBy = User::query()->value('id') ?? User::factory()->create()->id;

        $payload = array_merge([
            'tabel_no' => $tabelNo,
            'surname' => 'Aliyev',
            'name' => 'Murad',
            'patronymic' => 'Rashad',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'phone' => '0120000000',
            'mobile' => '0500000000',
            'email' => "{$tabelNo}@example.test",
            'nationality_id' => 1,
            'pin' => substr(str_replace('-', '', $tabelNo).'1234', 0, 7),
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => $addedBy,
        ], $overrides);

        return Personnel::query()->create($payload);
    }
}
