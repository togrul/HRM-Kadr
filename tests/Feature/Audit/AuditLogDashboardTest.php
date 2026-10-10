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

    protected function setUp(): void
    {
        parent::setUp();
        // Struktur görünürlüyü fail closed-dur: bu testlərin istifadəçiləri bütün strukturları görür.
        \App\Models\User::created(fn (\App\Models\User $user) => grantAllStructures($user));
    }

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

    public function test_event_facet_counts_keep_every_event_clickable(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        foreach ([['login', 2], ['updated', 3]] as [$event, $times]) {
            for ($i = 0; $i < $times; $i++) {
                AuditActivity::query()->create([
                    'log_name' => 'default',
                    'description' => 'User logged in',
                    'event' => $event,
                    'causer_type' => User::class,
                    'causer_id' => $user->id,
                ]);
            }
        }

        $component = Livewire::actingAs($user)->test(ActivityLogDashboard::class);

        // The event select lives in the component's own output, so its wire:model binds.
        $component->assertSeeHtml('audit-event-filter')
            ->assertSeeHtml('data-option-id="login"');

        // Selecting an event must not zero out the other rows, or the facet cannot be
        // clicked back out of.
        $counts = fn ($view): array => $view->viewData('eventCounts')->all();

        $this->assertSame(['login' => 2, 'updated' => 3], $counts($component));

        $component->set('event', 'login');
        $this->assertSame(['login' => 2, 'updated' => 3], $counts($component));

        // Clearing the select sends null, which falls back to "all events".
        $component->set('event', null)->assertSet('event', '');
    }

    public function test_metric_cards_toggle_their_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        $make = fn (string $event, ?int $causerId, string $createdAt) => AuditActivity::query()->create([
            'log_name' => 'default',
            'description' => 'x',
            'event' => $event,
            'causer_type' => $causerId ? User::class : null,
            'causer_id' => $causerId,
        ])->forceFill(['created_at' => $createdAt])->save();

        $make('profile_opened', $user->id, now()->toDateTimeString());
        $make('updated', null, now()->toDateTimeString());
        $make('updated', $user->id, now()->subDays(3)->toDateTimeString());

        $total = fn ($component): int => $component->viewData('activities')->total();
        $component = Livewire::actingAs($user)->test(ActivityLogDashboard::class)
            ->assertSeeHtml('aria-pressed="true"');

        $this->assertSame(3, $total($component));

        $component->call('toggleMetric', 'today')->assertSet('dateFrom', today()->toDateString());
        $this->assertSame(2, $total($component));
        $this->assertTrue($component->instance()->metricActive('today'));
        $this->assertFalse($component->instance()->metricActive('total'));

        $component->call('toggleMetric', 'today')->assertSet('dateFrom', '')->assertSet('dateTo', '');
        $this->assertSame(3, $total($component));

        $component->call('toggleMetric', 'profile_opened')->assertSet('event', 'profile_opened');
        $this->assertSame(1, $total($component));
        $component->call('toggleMetric', 'profile_opened')->assertSet('event', '');

        $component->call('toggleMetric', 'users')->assertSet('usersOnly', true);
        $this->assertSame(2, $total($component));
        $this->assertStringContainsString('users_only=1', $component->instance()->exportUrl('csv'));

        $component->call('toggleMetric', 'total')->assertSet('usersOnly', false);
        $this->assertSame(3, $total($component));
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

    public function test_export_shows_names_and_labels_instead_of_codes(): void
    {
        $user = User::factory()->create(['name' => 'Audit Reviewer']);
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        AuditActivity::query()->create([
            'log_name' => 'auth',
            'description' => 'User logged in',
            'event' => 'login',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('audit.logs.export', ['format' => 'csv']))
            ->assertOk();

        $csv = $response->getFile()->getContent();

        $this->assertStringContainsString('Audit Reviewer', $csv);
        $this->assertStringContainsString('Giriş', $csv);
        $this->assertStringNotContainsString('User #'.$user->id, $csv);
    }

    public function test_search_matches_user_and_employee_names(): void
    {
        $user = User::factory()->create(['name' => 'Zeynəb Qasımova']);
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));
        $this->seedPersonnelReferenceData();

        $personnel = Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'AUD-777', 'surname' => 'Hüseynli', 'name' => 'Rauf', 'patronymic' => 'Elman',
            'birthdate' => '1990-01-01', 'gender' => 1, 'email' => 'audit-search@example.test', 'mobile' => '994501112299',
            'nationality_id' => 1, 'pin' => 'AUD0777', 'residental_address' => 'Main st', 'education_degree_id' => 1,
            'structure_id' => 1, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2026-03-01',
            'added_by' => 1, 'is_pending' => false,
        ]));

        AuditActivity::query()->create(['log_name' => 'default', 'description' => 'a', 'event' => 'login', 'causer_type' => User::class, 'causer_id' => $user->id]);
        AuditActivity::query()->create(['log_name' => 'default', 'description' => 'b', 'event' => 'updated', 'subject_type' => Personnel::class, 'subject_id' => $personnel->id]);
        AuditActivity::query()->create(['log_name' => 'default', 'description' => 'c', 'event' => 'updated']);

        $total = fn ($component): int => $component->viewData('activities')->total();
        $component = Livewire::actingAs($user)->test(ActivityLogDashboard::class);

        $component->set('search', 'Qasımova');
        $this->assertSame(1, $total($component));

        $component->set('search', 'Rauf Hüseynli');
        $this->assertSame(1, $total($component));
    }

    public function test_event_rows_add_up_to_all_and_cards_follow_filters(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

        AuditActivity::query()->create(['log_name' => 'auth', 'description' => 'x', 'event' => 'login', 'causer_type' => User::class, 'causer_id' => $user->id]);
        AuditActivity::query()->create(['log_name' => 'auth', 'description' => 'x', 'event' => 'login', 'causer_type' => User::class, 'causer_id' => $user->id]);
        AuditActivity::query()->create(['log_name' => 'other', 'description' => 'x', 'event' => null]);

        $component = Livewire::actingAs($user)->test(ActivityLogDashboard::class);
        $counts = $component->viewData('eventCounts');

        // Every row, including entries without an event, adds up to "Hamısı" and to the list.
        $this->assertSame(3, $counts->sum());
        $this->assertSame(1, $counts->get('__none__'));

        $component->set('event', '__none__');
        $this->assertSame(1, $component->viewData('activities')->total());

        // Cards count inside the current filter; "users" counts the entries its click lists.
        $component->set('event', '')->set('logName', 'auth');
        $summary = $component->viewData('summary');
        $this->assertSame(2, $summary['total']);
        $this->assertSame(2, $summary['users']);

        $component->call('toggleMetric', 'users');
        $this->assertSame($summary['users'], $component->viewData('activities')->total());
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
