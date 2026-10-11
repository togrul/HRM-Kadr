<?php

namespace Tests\Feature\Reports;

use App\Models\Role;
use App\Models\User;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Livewire\Comparisons;
use App\Modules\Reports\Livewire\Dashboard;
use App\Modules\Reports\Livewire\DynamicBuilder;
use App\Modules\Reports\Livewire\StandardReports;
use App\Modules\Reports\Support\ReportPermissions;
use App\Modules\Services\Livewire\Roles\SetPermission;
use App\Support\Permissions\PermissionTranslationKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Reports\Concerns\GrantsReportPermissions;
use Tests\TestCase;

/**
 * Hesabatların rol matrisi: modul girişi (`show-reports`) yalnız paneli açır, hər hesabat öz
 * qrupunun icazəsini istəyir, maaş qrupu əlavə olaraq maaş icazəsi tələb edir; açılış və
 * ixrac audit jurnalına düşür; köhnə rollar miqrasiyadan sonra əvvəlki kimi görür.
 */
class ReportsPermissionMatrixTest extends TestCase
{
    use GrantsReportPermissions;
    use RefreshDatabase;

    public function test_module_permission_alone_opens_only_the_overview(): void
    {
        $user = $this->grantReports(User::factory()->create(), groups: [], export: false);
        $this->actingAs($user);

        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->assertSet('activeTab', 'overview')
            ->assertSet('availableTabs', ['overview']);

        Livewire::test(StandardReports::class)->assertForbidden();
        Livewire::test(DynamicBuilder::class)->assertForbidden();
        Livewire::test(Comparisons::class)->assertForbidden();
    }

    public function test_a_group_permission_limits_the_catalog_sources_and_cards_to_that_group(): void
    {
        $user = $this->grantReports(User::factory()->create(), groups: [ReportPermissions::GROUP_ATTENDANCE]);
        $this->actingAs($user);

        $standard = Livewire::test(StandardReports::class, ['report' => 'headcount'])
            ->assertSet('report', 'attendance');
        $this->assertSame(['attendance'], array_column($standard->get('reportOptions'), 'key'));

        // Başqa qrupun hesabatına keçid icazə verilən hesabata qaytarılır.
        $standard->set('report', 'demographics')->assertSet('report', 'attendance');

        $dynamic = Livewire::test(DynamicBuilder::class, ['source' => 'personnel'])
            ->assertSet('source', 'attendance');
        $this->assertSame(['attendance'], array_column($dynamic->get('sourceOptions'), 'key'));

        $comparisons = Livewire::test(Comparisons::class)
            ->assertSet('visibleCards', ['attendance_months'])
            ->assertSee(__('reports::dashboard.comparisons.cards.attendance_mom'));

        // Başqa qrupların kartları nəinki gizlənir, heç hesablanmır da.
        $this->assertSame(['attendance_months'], array_keys($comparisons->instance()->payload));
    }

    public function test_print_and_header_export_refuse_a_report_outside_the_users_groups(): void
    {
        $user = $this->grantReports(User::factory()->create(), groups: [ReportPermissions::GROUP_ATTENDANCE]);
        $this->actingAs($user);

        $this->get(route('reports.print-standard', ['report' => 'headcount', 'year' => 2026, 'month' => 3]))->assertForbidden();
        $this->get(route('reports.print-dynamic', ['source' => 'personnel', 'year' => 2026, 'month' => 3]))->assertForbidden();
        $this->get(route('reports.print-standard', ['report' => 'attendance', 'year' => 2026, 'month' => 3]))->assertOk();

        // Ümumi baxışın başlıq ixracı kadr (headcount) hesabatıdır — qrup yoxdursa düymə də yoxdur.
        Livewire::test(Dashboard::class)
            ->assertDontSee('/reports/print/', false)
            ->call('exportExcel')
            ->assertForbidden();
    }

    public function test_payroll_reports_require_the_payroll_permission_as_well(): void
    {
        $access = app(ReportsAccessService::class);
        $user = $this->grantReports(User::factory()->create(), groups: [ReportPermissions::GROUP_PAYROLL]);

        $this->assertFalse($access->canViewGroup(ReportPermissions::GROUP_PAYROLL, $user));

        Permission::findOrCreate('show-payroll', 'web');
        $user->givePermissionTo('show-payroll');

        $this->assertTrue($access->canViewGroup(ReportPermissions::GROUP_PAYROLL, $user->fresh()));
        $this->assertFalse($access->canViewGroup(ReportPermissions::GROUP_PERSONNEL, $user->fresh()));
    }

    public function test_group_permission_without_module_permission_opens_nothing(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-personnel-reports', 'web');
        $user->givePermissionTo('show-personnel-reports');

        $this->assertSame([], app(ReportsAccessService::class)->visibleGroups($user));
        $this->actingAs($user)->get(route('reports'))->assertForbidden();
    }

    public function test_opening_and_exporting_a_report_is_written_to_the_audit_log(): void
    {
        $user = $this->grantReports(User::factory()->create());
        $this->actingAs($user);

        Livewire::withQueryParams(['tab' => 'standard', 'report' => 'demographics', 'year' => 2026, 'month' => 4])
            ->test(Dashboard::class);

        Livewire::test(StandardReports::class, ['report' => 'headcount', 'year' => 2026, 'month' => 4])
            ->call('exportCsv')
            ->assertFileDownloaded();

        $this->get(route('reports.print-dynamic', ['source' => 'personnel', 'year' => 2026, 'month' => 4]))->assertOk();

        $opened = Activity::query()->where('log_name', ReportsAuditLogger::LOG_NAME)->where('event', ReportsAuditLogger::EVENT_OPENED)->first();
        $this->assertNotNull($opened);
        $this->assertSame($user->id, (int) $opened->causer_id);
        $this->assertSame('demographics', $opened->getExtraProperty('report'));

        $exports = Activity::query()
            ->where('log_name', ReportsAuditLogger::LOG_NAME)
            ->where('event', ReportsAuditLogger::EVENT_EXPORTED)
            ->get();

        $this->assertEqualsCanonicalizing(['csv', 'print'], $exports->map(fn (Activity $activity) => $activity->getExtraProperty('format'))->all());
        $this->assertSame('headcount', $exports->firstWhere(fn (Activity $a) => $a->getExtraProperty('format') === 'csv')?->getExtraProperty('report'));
    }

    public function test_refused_export_leaves_no_export_audit_entry(): void
    {
        $user = $this->grantReports(User::factory()->create(), export: false);
        $this->actingAs($user);

        Livewire::test(StandardReports::class)->call('exportExcel')->assertForbidden();

        $this->assertFalse(Activity::query()->where('event', ReportsAuditLogger::EVENT_EXPORTED)->exists());
    }

    public function test_migration_keeps_existing_report_roles_seeing_what_they_saw(): void
    {
        $migration = require base_path('app/Modules/Reports/Database/Migrations/2026_10_11_100000_seed_report_group_permissions.php');
        $migration->down();

        $viewer = Role::query()->create(['name' => 'Report Viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo(Permission::findOrCreate('show-reports', 'web'));

        $payrollViewer = Role::query()->create(['name' => 'Payroll Report Viewer', 'guard_name' => 'web']);
        $payrollViewer->givePermissionTo(Permission::findOrCreate('show-reports', 'web'), Permission::findOrCreate('show-payroll', 'web'));

        $admin = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $unrelated = Role::query()->create(['name' => 'Unrelated', 'guard_name' => 'web']);

        $directUser = User::factory()->create();
        $directUser->givePermissionTo('show-reports');

        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $access = app(ReportsAccessService::class);
        $groupsWithoutPayroll = array_values(array_diff(array_keys(ReportPermissions::GROUPS), [ReportPermissions::GROUP_PAYROLL]));

        $viewerUser = User::factory()->create();
        $viewerUser->assignRole($viewer);
        $this->assertSame($groupsWithoutPayroll, $access->visibleGroups($viewerUser));
        $this->assertFalse($viewerUser->can('show-payroll-reports'));
        $this->assertFalse($viewerUser->can(ReportPermissions::SCHEDULE));

        $payrollUser = User::factory()->create();
        $payrollUser->assignRole($payrollViewer);
        $this->assertSame(array_keys(ReportPermissions::GROUPS), $access->visibleGroups($payrollUser));

        $this->assertSame($groupsWithoutPayroll, $access->visibleGroups($directUser->fresh()));

        foreach (array_diff(ReportPermissions::all(), [ReportPermissions::VIEW, ReportPermissions::EXPORT]) as $permission) {
            $this->assertTrue($admin->fresh()->hasPermissionTo($permission), $permission.' Admin roluna verilməlidir');
        }

        $this->assertSame(0, $unrelated->fresh()->permissions()->count());
    }

    public function test_every_report_permission_has_group_method_and_description_translations(): void
    {
        foreach (['az', 'en'] as $locale) {
            foreach (ReportPermissions::all() as $permission) {
                $group = PermissionTranslationKey::groupKeyFromPermission($permission);
                $method = PermissionTranslationKey::methodKeyFromPermission($permission);

                $this->assertTrue(Lang::has('services::permissions.groups.'.$group, $locale, false), "{$locale}: qrup {$group}");
                $this->assertTrue(Lang::has('services::permissions.methods.'.$method, $locale, false), "{$locale}: metod {$method}");
                $this->assertArrayHasKey($permission, trans('permission_descriptions.items', [], $locale), "{$locale}: təsvir {$permission}");
            }
        }
    }

    public function test_role_screen_lists_report_groups_under_their_translated_labels(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('access-settings', 'web'), Permission::findOrCreate('manage-roles', 'web'));
        $manager->givePermissionTo(ReportPermissions::all());
        $role = Role::query()->create(['name' => 'Hesabat auditoru', 'guard_name' => 'web']);

        app()->setLocale('az');

        Livewire::actingAs($manager)
            ->test(SetPermission::class, ['roleModel' => $role->id])
            ->set('permissionSearch', 'hesabatlar')
            ->assertSee(__('services::permissions.groups.personnel_reports'))
            ->assertSee(__('services::permissions.groups.payroll_reports'))
            ->assertSee(__('services::permissions.groups.report_presets'))
            ->assertSee(__('services::permissions.methods.schedule'));
    }
}
