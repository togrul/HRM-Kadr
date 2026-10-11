<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Modules\Reports\Application\Services\ReportPresetService;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Domain\Models\ReportPreset;
use App\Modules\Reports\Livewire\Dashboard;
use App\Modules\Reports\Support\ReportPermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Reports\Concerns\GrantsReportPermissions;
use Tests\TestCase;

use function grantAllStructures;
use function grantStructures;

/**
 * Hesabat filtr şablonları: şəxsi/paylaşılan, policy, audit və struktur görünürlüyü —
 * başqasının saxladığı şablon tətbiq edənin görmədiyi strukturu açmır.
 */
class ReportPresetsTest extends TestCase
{
    use GrantsReportPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('structures')->insertOrIgnore([
            ['id' => 1, 'name' => 'Daxili', 'shortname' => 'D'],
            ['id' => 2, 'name' => 'Kənar', 'shortname' => 'K'],
        ]);
    }

    public function test_user_saves_lists_and_applies_a_personal_preset_from_the_dashboard(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $this->actingAs($user);

        Livewire::withQueryParams(['tab' => 'standard', 'report' => 'demographics', 'year' => 2025, 'month' => 6, 'structure_id' => 2])
            ->test(Dashboard::class)
            ->set('presetName', 'Kənar demoqrafiya')
            ->call('savePreset')
            ->assertHasNoErrors()
            ->assertDispatched('notify')
            ->assertSee('Kənar demoqrafiya');

        $preset = ReportPreset::query()->sole();
        $this->assertSame('standard', $preset->tab);
        $this->assertFalse($preset->is_shared);
        $this->assertSame(['report' => 'demographics', 'year' => 2025, 'month' => 6, 'structure_id' => 2], $preset->filters);

        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->assertSet('report', 'headcount')
            ->call('applyPreset', $preset->id)
            ->assertSet('report', 'demographics')
            ->assertSet('year', 2025)
            ->assertSet('month', 6)
            ->assertSet('structureId', 2)
            ->assertSet('selectionVersion', 1)
            ->assertSeeHtml('reports-standard-2025-6-2-1');

        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_PRESET_CREATED)->exists());
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_PRESET_APPLIED)->exists());
    }

    public function test_saving_under_an_existing_name_updates_the_preset(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $service = app(ReportPresetService::class);

        $service->save($user, 'dynamic', 'Cins üzrə', ['source' => 'personnel', 'group_by' => 'gender', 'metric' => 'count', 'year' => 2026, 'month' => 1]);
        $service->save($user, 'dynamic', 'Cins üzrə', ['source' => 'personnel', 'group_by' => 'status', 'metric' => 'count', 'year' => 2026, 'month' => 2]);

        $this->assertSame(1, ReportPreset::query()->count());
        $this->assertSame('status', ReportPreset::query()->sole()->filters['group_by']);
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_PRESET_UPDATED)->exists());
    }

    public function test_a_personal_preset_is_invisible_and_untouchable_for_other_users(): void
    {
        $owner = grantAllStructures($this->grantReports(User::factory()->create()));
        $other = grantAllStructures($this->grantReports(User::factory()->create()));
        $service = app(ReportPresetService::class);

        $preset = $service->save($owner, 'standard', 'Mənim', ['report' => 'headcount', 'year' => 2026, 'month' => 3]);

        $this->assertCount(0, $service->availableFor($other, 'standard'));

        $this->actingAs($other);
        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->call('applyPreset', $preset->id)
            ->assertForbidden();

        $this->expectException(AuthorizationException::class);
        $service->delete($other, $preset);
    }

    public function test_sharing_requires_the_manage_presets_permission(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $service = app(ReportPresetService::class);

        // Livewire: icazəsiz istifadəçinin «paylaş» seçimi nəzərə alınmır.
        $this->actingAs($user);
        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->assertSet('canSharePresets', false)
            ->set('presetName', 'Gizli')
            ->set('presetShared', true)
            ->call('savePreset');
        $this->assertFalse(ReportPreset::query()->sole()->is_shared);

        $this->expectException(AuthorizationException::class);
        $service->save($user, 'standard', 'Paylaşılan', ['report' => 'headcount', 'year' => 2026, 'month' => 3], shared: true);
    }

    public function test_shared_preset_is_visible_to_report_users_but_only_managers_change_it(): void
    {
        $manager = grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::MANAGE_PRESETS]));
        $viewer = grantAllStructures($this->grantReports(User::factory()->create()));
        $service = app(ReportPresetService::class);

        $preset = $service->save($manager, 'standard', 'Rəhbərlik paketi', ['report' => 'movements', 'year' => 2026, 'month' => 3], shared: true);

        $this->assertSame([$preset->id], $service->availableFor($viewer, 'standard')->pluck('id')->all());
        $this->assertTrue($viewer->can('view', $preset));
        $this->assertFalse($viewer->can('delete', $preset));
        $this->assertFalse($viewer->can('update', $preset));

        $this->actingAs($viewer);
        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->call('applyPreset', $preset->id)
            ->assertSet('report', 'movements')
            ->call('confirmPresetDeletion', $preset->id)
            ->assertForbidden();

        $other = grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::MANAGE_PRESETS]));
        $this->actingAs($other);
        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->call('confirmPresetDeletion', $preset->id)
            ->assertSet('showDeleteConfirmation', true)
            ->call('runConfirmedDeletion');

        $this->assertSame(0, ReportPreset::query()->count());
        $deleted = Activity::query()->where('event', ReportsAuditLogger::EVENT_PRESET_DELETED)->sole();
        $this->assertSame($other->id, (int) $deleted->causer_id);
        $this->assertSame('Rəhbərlik paketi', $deleted->getExtraProperty('preset_name'));
    }

    public function test_applying_a_preset_never_opens_a_structure_outside_the_viewers_scope(): void
    {
        $manager = grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::MANAGE_PRESETS]));
        $viewer = grantStructures($this->grantReports(User::factory()->create()), [1]);

        $preset = app(ReportPresetService::class)->save($manager, 'standard', 'Kənar struktur', ['report' => 'headcount', 'year' => 2026, 'month' => 3, 'structure_id' => 2], shared: true);

        $this->actingAs($viewer);
        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->call('applyPreset', $preset->id)
            ->assertSet('structureId', null)
            ->assertDispatched('notify', type: 'warning', message: __('reports::presets.messages.structure_dropped'));

        // Struktursuz hesabat da yalnız baxanın öz strukturları ilə qurulur.
        $this->assertSame([1], app(ReportsStructureScopeService::class)->resolveIds(null));

        $applied = Activity::query()->where('event', ReportsAuditLogger::EVENT_PRESET_APPLIED)->sole();
        $this->assertTrue((bool) $applied->getExtraProperty('structure_dropped'));
    }

    public function test_a_preset_cannot_be_saved_for_a_structure_outside_the_owners_scope(): void
    {
        $user = grantStructures($this->grantReports(User::factory()->create()), [1]);
        $this->actingAs($user);

        try {
            app(ReportPresetService::class)->save($user, 'standard', 'Sızma', ['report' => 'headcount', 'year' => 2026, 'month' => 3, 'structure_id' => 2]);
            $this->fail('Görünürlükdən kənar struktur saxlanmamalıdır.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('structure_id', $exception->errors());
        }

        $this->assertSame(0, ReportPreset::query()->count());
    }

    public function test_presets_for_a_report_group_the_user_lacks_are_hidden_and_not_applicable(): void
    {
        $manager = grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::MANAGE_PRESETS]));
        $attendanceOnly = grantAllStructures($this->grantReports(User::factory()->create(), groups: [ReportPermissions::GROUP_ATTENDANCE]));
        $service = app(ReportPresetService::class);

        $preset = $service->save($manager, 'standard', 'Kadr', ['report' => 'headcount', 'year' => 2026, 'month' => 3], shared: true);

        $this->assertCount(0, $service->availableFor($attendanceOnly, 'standard'));

        $this->expectException(ValidationException::class);
        $service->apply($attendanceOnly, $preset);
    }

    public function test_preset_name_is_validated(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $this->actingAs($user);

        Livewire::withQueryParams(['tab' => 'standard'])
            ->test(Dashboard::class)
            ->set('presetName', '   ')
            ->call('savePreset')
            ->assertHasErrors('presetName');

        Livewire::withQueryParams(['tab' => 'overview'])
            ->test(Dashboard::class)
            ->assertSet('activeTab', 'overview')
            ->set('presetName', 'Ümumi baxış')
            ->call('savePreset')
            ->assertHasErrors('presetName');

        $this->assertSame(0, ReportPreset::query()->count());
    }

    public function test_presets_table_migration_is_reversible(): void
    {
        $migration = require base_path('app/Modules/Reports/Database/Migrations/2026_10_11_110000_create_report_presets_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasTable('report_presets'));

        $migration->up();
        $this->assertTrue(Schema::hasTable('report_presets'));
    }
}
