<?php

namespace Tests\Feature\EmployeeRelations;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Application\Services\StatisticsService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Livewire\Dashboard;
use App\Modules\Personnel\Support\MyHr\MyHrTabs;
use App\Services\Modules\ModuleState;
use App\Support\Permissions\PermissionTranslationKey;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Anonimləşdirilmiş statistika (struktur görünürlüyü + kiçik qrupların gizlədilməsi),
 * icazələrin miqrasiyası/tərcümələri və modulun söndürülə bilməsi.
 */
class StatisticsAndModuleTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_statistics_are_scoped_to_visible_structures_and_suppress_small_groups(): void
    {
        Carbon::setTestNow('2026-08-01 09:00:00');
        $this->erFakeDrafter();

        $own = $this->erStructure('Böyük şöbə');
        $foreign = $this->erStructure('Yad şöbə');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);

        $repeat = $this->erPersonnel('Təkrarov', $own->id);
        $others = collect(['Birov', 'İkiyev', 'Üçov'])->map(fn (string $name) => $this->erPersonnel($name, $own->id));

        // Böyük şöbə: 5 iş (biri eyni işçi barəsində 2-ci), 3-ü intizam, 2-si münaqişə.
        $cases = collect([
            $this->erOpenCase($hr, $repeat),
            $this->erOpenCase($hr, $repeat),
            $this->erOpenCase($hr, $others[0]),
            $this->erOpenCase($hr, $others[1], ['case_type_id' => $this->erTypeId('conflict')]),
            $this->erOpenCase($hr, $others[2], ['case_type_id' => $this->erTypeId('conflict')]),
        ]);
        // Yad şöbə: 4 iş.
        foreach (range(1, 4) as $index) {
            $this->erOpenCase($hr, $this->erPersonnel('Yad'.$index, $foreign->id));
        }

        Carbon::setTestNow('2026-08-05 09:00:00');
        foreach ($cases->take(3) as $case) {
            app(CaseWorkflow::class)->transition($case->fresh(), CaseStatus::Investigating, $hr);
        }
        Carbon::setTestNow('2026-08-11 09:00:00');
        foreach ($cases->take(3) as $case) {
            app(DecisionService::class)->decide($case->fresh(), $hr, DecisionOutcome::VerbalWarning, 'Xəbərdarlıq');
        }

        $statsViewer = $this->erUser([CaseAccess::STATISTICS], [$own->id]);
        $stats = app(StatisticsService::class)->build($statsViewer);

        $this->assertSame(5, $stats['total']);
        $this->assertSame([['label' => 'Böyük şöbə', 'value' => 5]], $stats['by_structure']);
        $this->assertSame([
            ['label' => 'İntizam pozuntusu', 'value' => 3],
            ['label' => 'Münaqişə', 'value' => null],
        ], $stats['by_type']);
        // Təkrarlanan hal (bir işçi) — kiçik say olduğu üçün gizlədilir.
        $this->assertNull($stats['repeat_subjects']);
        $this->assertSame(10.0, $stats['avg_investigation_days']);
        // Açıq qalan 2 iş 3 günlük SLA-nı keçib (gizlədilir); 3 iş araşdırmaya gec keçib: cəmi 5 pozuntu.
        $this->assertNull($stats['overdue']);
        $this->assertSame(5, $stats['sla_breaches']);

        // Görünməyən struktur filtri görünürlüyü genişləndirmir.
        $this->assertSame(0, app(StatisticsService::class)->build($statsViewer, $foreign->id)['total']);

        $allViewer = $this->erUser([CaseAccess::STATISTICS], allStructures: true);
        $this->assertSame(9, app(StatisticsService::class)->build($allViewer)['total']);
        $this->assertSame(4, app(StatisticsService::class)->build($allViewer, $foreign->id)['total']);

        $nobody = $this->erUser([CaseAccess::STATISTICS]);
        $this->assertSame(0, app(StatisticsService::class)->build($nobody)['total']);

        Livewire::actingAs($statsViewer)
            ->test(Dashboard::class)
            ->assertSet('activeTab', 'statistics')
            ->assertSee('Böyük şöbə')
            ->assertDontSee('Yad şöbə')
            ->assertDontSee('Təkrarov')
            ->assertSee('&lt;3', false);

        $this->expectException(AuthorizationException::class);
        app(StatisticsService::class)->build($this->erUser([CaseAccess::SHOW], allStructures: true));
    }

    public function test_permissions_are_seeded_with_descriptions_and_translated_groups(): void
    {
        $permissions = [
            CaseAccess::SHOW,
            CaseAccess::CREATE,
            CaseAccess::MANAGE,
            CaseAccess::REVEAL,
            CaseAccess::STATISTICS,
            CaseAccess::SUBMIT_OWN,
        ];

        $this->assertSame(6, DB::table('permissions')->whereIn('name', $permissions)->count());

        foreach (['az', 'en'] as $locale) {
            foreach ($permissions as $permission) {
                $group = PermissionTranslationKey::groupKeyFromPermission($permission);
                $method = PermissionTranslationKey::methodKeyFromPermission($permission);

                $this->assertTrue(Lang::has('services::permissions.groups.'.$group, $locale, false), "{$locale}: qrup {$group}");
                $this->assertTrue(Lang::has('services::permissions.methods.'.$method, $locale, false), "{$locale}: metod {$method}");
                $this->assertArrayHasKey($permission, trans('permission_descriptions.items', [], $locale), "{$locale}: təsvir {$permission}");
            }
        }

        $this->assertSame(
            'İşçi münasibətləri modulunu açmaq, özünün açdığı və araşdırıcı təyin olunduğu şikayət və intizam işlərini görmək icazəsi verir.',
            DB::table('permissions')->where('name', CaseAccess::SHOW)->value('description'),
        );
    }

    public function test_module_can_be_switched_off_and_its_my_hr_section_disappears(): void
    {
        $state = app(ModuleState::class);
        $catalog = $state->all();

        $this->assertTrue($state->enabled('employee-relations'));
        $this->assertContains('employee-relations', MyHrTabs::all());
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('employee-relations'));

        $catalog['employee-relations']['enabled'] = false;
        $state->refresh($catalog);

        try {
            $this->assertNotContains('employee-relations', MyHrTabs::all());
        } finally {
            $catalog['employee-relations']['enabled'] = true;
            $state->refresh($catalog);
        }
    }

    public function test_my_hr_shows_the_employee_relations_section(): void
    {
        $structure = $this->erStructure('Satış');
        $employee = $this->erPersonnel('Kabinetov', $structure->id);
        $user = $this->erUser(['show-my-hr', CaseAccess::SUBMIT_OWN]);
        $this->erLink($user, $employee);

        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'employee-relations'])
            ->test(\App\Modules\Personnel\Livewire\MyHr\MyHrDashboard::class)
            ->call('setActiveTab', 'employee-relations')
            ->assertSee(__('personnel::my_hr.tabs.employee_relations'))
            ->assertSeeHtml('wire:name="employee-relations.my-cases"');
    }
}
