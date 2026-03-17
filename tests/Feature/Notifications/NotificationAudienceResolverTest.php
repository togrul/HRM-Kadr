<?php

namespace Tests\Feature\Notifications;

use App\Models\Personnel;
use App\Models\PerformanceForm;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFormTemplate;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Notifications\Support\NotificationAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationAudienceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_employee_admin_and_same_structure_targets(): void
    {
        $adminRole = Role::findOrCreate('admin');
        Permission::findOrCreate('get-notification');
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->create(['id' => 5, 'name' => 'İR', 'shortname' => 'IR', 'code' => 5, 'level' => 1]);
        Position::query()->create(['id' => 10, 'name' => 'Məsləhətçi']);

        $subject = Personnel::factory()->create([
            'tabel_no' => '0001',
            'surname' => 'Subject',
            'name' => 'User',
            'patronymic' => 'Test',
            'birthdate' => '1990-03-16',
            'email' => 'subject@example.test',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PIN0001',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 5,
            'position_id' => 10,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $subjectUser = User::factory()->create([
            'email' => 'subject@example.test',
            'is_active' => true,
        ]);

        $sameStructurePersonnel = Personnel::factory()->create([
            'tabel_no' => '0002',
            'surname' => 'Colleague',
            'name' => 'User',
            'patronymic' => 'Test',
            'birthdate' => '1991-03-17',
            'email' => 'colleague@example.test',
            'mobile' => '0500000002',
            'nationality_id' => 1,
            'pin' => 'PIN0002',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 5,
            'position_id' => 10,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $sameStructureUser = User::factory()->create([
            'email' => 'colleague@example.test',
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'email' => 'admin@example.test',
            'is_active' => true,
        ]);
        $adminUser->assignRole($adminRole);

        $resolved = app(NotificationAudienceResolver::class)->resolve([
            'targets' => ['employee', 'same_structure', 'admins'],
        ], $subject);

        $this->assertEqualsCanonicalizing(
            [$subjectUser->id, $sameStructureUser->id, $adminUser->id],
            $resolved->pluck('id')->all()
        );
    }

    public function test_it_resolves_direct_manager_department_and_specific_users(): void
    {
        Role::findOrCreate('admin');
        Permission::findOrCreate('get-notification');
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->create(['id' => 8, 'name' => 'Maliyyə', 'shortname' => 'MAL', 'code' => 8, 'level' => 1]);
        Position::query()->create(['id' => 20, 'name' => 'Mütəxəssis']);

        $subject = Personnel::factory()->create([
            'tabel_no' => '0003',
            'surname' => 'Subject',
            'name' => 'Two',
            'patronymic' => 'Test',
            'birthdate' => '1992-03-16',
            'email' => 'subject2@example.test',
            'mobile' => '0500000003',
            'nationality_id' => 1,
            'pin' => 'PIN0003',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 8,
            'position_id' => 20,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $managerUser = User::factory()->create([
            'email' => 'manager@example.test',
            'is_active' => true,
        ]);

        Personnel::factory()->create([
            'tabel_no' => '0004',
            'surname' => 'Manager',
            'name' => 'User',
            'patronymic' => 'Test',
            'birthdate' => '1988-03-16',
            'email' => 'manager@example.test',
            'mobile' => '0500000004',
            'nationality_id' => 1,
            'pin' => 'PIN0004',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 8,
            'position_id' => 20,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Q1',
            'cycle_type' => 'quarterly',
            'period_start' => now()->startOfQuarter()->toDateString(),
            'period_end' => now()->endOfQuarter()->toDateString(),
            'status' => 'active',
            'auto_generate_forms' => false,
        ]);

        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Əsas forma',
            'code' => 'MAIN-1',
            'is_active' => true,
        ]);

        PerformanceForm::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $subject->id,
            'manager_id' => $managerUser->id,
            'result_status' => 'draft',
        ]);

        $specificUser = User::factory()->create([
            'email' => 'specific@example.test',
            'is_active' => true,
        ]);

        $resolved = app(NotificationAudienceResolver::class)->resolve([
            'targets' => ['direct_manager', 'department', 'specific_users'],
            'structure_ids' => [8],
            'user_ids' => [$specificUser->id],
        ], $subject);

        $this->assertEqualsCanonicalizing(
            [$managerUser->id, $specificUser->id],
            $resolved->pluck('id')->all()
        );
    }

    public function test_it_implicitly_resolves_department_and_specific_users_when_ids_exist(): void
    {
        Role::findOrCreate('admin');
        Permission::findOrCreate('get-notification');
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->create(['id' => 18, 'name' => 'Texniki vasitələr', 'shortname' => 'TV', 'code' => 18, 'level' => 1]);
        Position::query()->create(['id' => 30, 'name' => 'Mütəxəssis']);

        $subject = Personnel::factory()->create([
            'tabel_no' => '0005',
            'surname' => 'Birthday',
            'name' => 'Person',
            'patronymic' => 'Test',
            'birthdate' => '1992-03-17',
            'email' => 'birthday@example.test',
            'mobile' => '0500000005',
            'nationality_id' => 1,
            'pin' => 'PIN0005',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 18,
            'position_id' => 30,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        Personnel::factory()->create([
            'tabel_no' => '0006',
            'surname' => 'Department',
            'name' => 'User',
            'patronymic' => 'Test',
            'birthdate' => '1991-03-17',
            'email' => 'department@example.test',
            'mobile' => '0500000006',
            'nationality_id' => 1,
            'pin' => 'PIN0006',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 18,
            'position_id' => 30,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $departmentUser = User::factory()->create([
            'email' => 'department@example.test',
            'is_active' => true,
        ]);

        $specificUser = User::factory()->create([
            'email' => 'specific-birthday@example.test',
            'is_active' => true,
        ]);

        $resolved = app(NotificationAudienceResolver::class)->resolve([
            'targets' => ['employee'],
            'structure_ids' => [18],
            'user_ids' => [$specificUser->id],
        ], $subject);

        $this->assertEqualsCanonicalizing(
            [$departmentUser->id, $specificUser->id],
            $resolved->pluck('id')->all()
        );
    }
}
