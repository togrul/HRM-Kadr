<?php

namespace Tests\Feature\Personnel;

use App\Models\AuditActivity;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Application\Services\Personnel360TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Personnel360TimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_cross_module_personnel_timeline(): void
    {
        $personnel = $this->makePersonnel();
        $user = User::factory()->create(['name' => 'HR Auditor']);

        DB::table('order_statuses')->insertOrIgnore([
            'id' => 1,
            'name' => 'Active',
            'locale' => 'az',
        ]);
        DB::table('order_statuses')->insertOrIgnore([
            'id' => 2,
            'name' => 'Inactive',
            'locale' => 'az',
        ]);

        DB::table('leaves')->insert([
            'tabel_no' => $personnel->tabel_no,
            'starts_at' => '2026-04-02',
            'ends_at' => '2026-04-03',
            'reason' => 'Medical',
            'status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trainingSessionId = DB::table('training_sessions')->insertGetId([
            'title' => 'Leadership Basics',
            'status' => 'completed',
            'completed_at' => '2026-04-05 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('training_delivery_records')->insert([
            'training_session_id' => $trainingSessionId,
            'personnel_id' => $personnel->id,
            'attended_hours' => 4,
            'result_status' => 'completed',
            'completed_at' => '2026-04-05 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_lifecycle_events')->insert([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'title' => 'Yeni əməkdaş onboarding',
            'description' => 'İlk həftə planı',
            'effective_date' => '2026-04-06',
            'deadline_at' => '2026-04-12',
            'owner_user_id' => $user->id,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditActivity::query()->create([
            'log_name' => 'personnel',
            'description' => 'You have updated personnel',
            'event' => 'updated',
            'subject_type' => Personnel::class,
            'subject_id' => $personnel->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => [
                'old' => ['email' => 'old@example.test', 'structure_id' => 1, 'position_id' => 1, 'gender' => 1, 'status_id' => 1],
                'attributes' => ['email' => 'new@example.test', 'structure_id' => 2, 'position_id' => 2, 'gender' => 2, 'status_id' => 2],
            ],
            'created_at' => '2026-04-04 10:00:00',
            'updated_at' => '2026-04-04 10:00:00',
        ]);

        $items = app(Personnel360TimelineService::class)->build($personnel);

        $this->assertContains('leave', $items->pluck('type')->all());
        $this->assertContains('training_delivery', $items->pluck('type')->all());
        $this->assertContains('lifecycle', $items->pluck('type')->all());
        $this->assertContains('audit', $items->pluck('type')->all());
        $this->assertSame('lifecycle', $items->first()['type']);
        $this->assertStringContainsString(
            __('employee-lifecycle::dashboard.types.onboarding'),
            $items->firstWhere('type', 'lifecycle')['title']
        );
        $this->assertStringContainsString('HR Auditor', $items->firstWhere('type', 'audit')['role']);
        $auditSummary = $items->firstWhere('type', 'audit')['summary'];
        $this->assertStringContainsString('Regional Office', $auditSummary);
        $this->assertStringContainsString('Senior Officer', $auditSummary);
        $this->assertStringContainsString(__('personnel::common.labels.woman'), $auditSummary);
        $this->assertStringContainsString('Inactive', $auditSummary);
        $this->assertStringNotContainsString('Position Id', $auditSummary);
        $this->assertStringNotContainsString('Structure Id', $auditSummary);

        $filteredItems = app(Personnel360TimelineService::class)->build($personnel, null, 80, [
            'type' => 'audit',
            'date_from' => '2026-04-04',
            'date_to' => '2026-04-04',
        ]);

        $this->assertSame(['audit'], $filteredItems->pluck('type')->unique()->values()->all());
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TL'.Str::upper(Str::random(6)),
            'surname' => 'Timeline',
            'name' => 'Employee',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'timeline@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'TL'.str_pad((string) random_int(1, 99999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insertOrIgnore([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 2,
            'name' => 'Regional Office',
            'shortname' => 'REG',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 20,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Officer',
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 2,
            'name' => 'Senior Officer',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }
}
