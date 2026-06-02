<?php

namespace Tests\Feature\Audit;

use App\Models\AuditActivity;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Audit\Livewire\ActivityLogDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_route_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('audit.logs'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_audit_log_module(): void
    {
        $user = User::factory()->create();
        $this->seedPersonnelReferenceData();

        $personnel = Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'AUD-001',
            'surname' => 'Callalli',
            'name' => 'Togrul',
            'patronymic' => 'Ismayil',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'audit-personnel@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'AUD0001',
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));

        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        $activity = AuditActivity::query()->create([
            'log_name' => 'personnel_access',
            'description' => 'Personnel profile opened',
            'event' => 'profile_opened',
            'subject_type' => Personnel::class,
            'subject_id' => $personnel->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => [
                'viewed_personnel_id' => $personnel->id,
                'viewed_personnel_tabel_no' => $personnel->tabel_no,
                'viewed_personnel_fullname' => $personnel->fullname,
                'ip' => '127.0.0.1',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('audit.logs'))
            ->assertOk()
            ->assertSee('audit.activity-log-dashboard');

        Livewire::actingAs($user)
            ->test(ActivityLogDashboard::class)
            ->assertSee('Əməkdaş profili açıldı')
            ->assertSee('Profil açıldı')
            ->assertSee($user->name)
            ->assertSee($personnel->fullname)
            ->set('search', 'profile')
            ->assertSee('Əməkdaş profili açıldı')
            ->call('selectActivity', $activity->id)
            ->assertSee('Baxılan əməkdaşın adı')
            ->assertSee($personnel->fullname)
            ->assertSee('127.0.0.1');
    }

    public function test_authorized_user_can_export_audit_logs(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        AuditActivity::query()->create([
            'log_name' => 'auth',
            'description' => 'User logged in',
            'event' => 'login',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => ['ip' => '127.0.0.1'],
        ]);

        $response = $this->actingAs($user)
            ->get(route('audit.logs.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=utf-8');

        $this->assertStringContainsString('audit-logs-', (string) $response->headers->get('content-disposition'));
    }

    public function test_attendance_overtime_audit_events_are_localized(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        AuditActivity::query()->create([
            'log_name' => 'attendance',
            'description' => 'Attendance overtime request approved.',
            'event' => 'overtime_request.approved',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => ['date' => '2026-05-12'],
        ]);

        Livewire::actingAs($user)
            ->test(ActivityLogDashboard::class)
            ->assertSee('Əlavə iş sorğusu təsdiqləndi')
            ->assertDontSee('Overtime Request.approved')
            ->assertDontSee('Attendance overtime request approved.');
    }

    private function seedPersonnelReferenceData(): void
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
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Officer',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }
}
