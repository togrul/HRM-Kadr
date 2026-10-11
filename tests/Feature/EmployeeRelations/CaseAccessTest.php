<?php

namespace Tests\Feature\EmployeeRelations;

use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use App\Modules\EmployeeRelations\Livewire\Dashboard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * İşin açılması, icazələr və struktur görünürlüyü (məxfilik qaydası).
 */
class CaseAccessTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    public function test_hr_opens_a_case_with_number_sla_history_and_audit(): void
    {
        $structure = $this->erStructure('Maliyyə');
        $subject = $this->erPersonnel('Qasımov', $structure->id);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);

        $case = $this->erOpenCase($hr, $subject);

        $this->assertSame(sprintf('ER-%d-%05d', now()->year, $case->id), $case->case_number);
        $this->assertSame(CaseStatus::Open, $case->status);
        $this->assertSame('hr', $case->channel);
        $this->assertSame($structure->id, $case->structure_id);
        $this->assertSame(now()->addDays(3)->toDateString(), $case->stage_due_at?->toDateString());
        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'created', 'actor_user_id' => $hr->id, 'to_status' => 'open']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'employee_relations', 'event' => 'created', 'subject_id' => $case->id, 'causer_id' => $hr->id]);
    }

    public function test_opening_a_case_requires_the_create_permission_and_a_visible_subject(): void
    {
        $own = $this->erStructure('Öz şöbə');
        $foreign = $this->erStructure('Yad şöbə');
        $ownPerson = $this->erPersonnel('Görünənov', $own->id);
        $foreignPerson = $this->erPersonnel('Gizliyev', $foreign->id);

        $viewer = $this->erUser([CaseAccess::SHOW], [$own->id]);

        try {
            $this->erOpenCase($viewer, $ownPerson);
            $this->fail('show-only user must not open a case');
        } catch (AuthorizationException) {
            $this->assertSame(0, DB::table('employee_relation_cases')->count());
        }

        $manager = $this->erUser([CaseAccess::SHOW, CaseAccess::CREATE], [$own->id]);
        $case = $this->erOpenCase($manager, $ownPerson);
        $this->assertSame('manager', $case->channel);

        $this->expectException(AuthorizationException::class);
        $this->erOpenCase($manager, $foreignPerson);
    }

    public function test_cases_are_visible_only_to_managers_in_scope_owners_and_assigned_investigators(): void
    {
        $own = $this->erStructure('Öz şöbə');
        $foreign = $this->erStructure('Yad şöbə');
        $ownPerson = $this->erPersonnel('Görünənov', $own->id);
        $foreignPerson = $this->erPersonnel('Gizliyev', $foreign->id);

        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);
        $ownCase = $this->erOpenCase($hr, $ownPerson, ['title' => 'Öz şöbənin işi']);
        $foreignCase = $this->erOpenCase($hr, $foreignPerson, ['title' => 'Yad şöbənin işi']);

        $scopedManager = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$own->id]);
        $investigator = $this->erUser([CaseAccess::SHOW], [$own->id]);
        $bystander = $this->erUser([CaseAccess::SHOW], [$own->id]);
        $noPermission = $this->erUser([], allStructures: true);

        $access = app(CaseAccess::class);

        $this->assertSame([$ownCase->id], $access->visibleQuery($scopedManager)->pluck('id')->all());
        $this->assertSame([], $access->visibleQuery($bystander)->pluck('id')->all());
        $this->assertSame([], $access->visibleQuery($noPermission)->pluck('id')->all());

        app(CaseService::class)->assignInvestigator($ownCase, $hr, $investigator->id);
        $this->assertSame([$ownCase->id], $access->visibleQuery($investigator)->pluck('id')->all());
        $this->assertFalse($access->canManage($investigator, $ownCase));
        $this->assertTrue($access->canWork($investigator, $ownCase));

        Livewire::actingAs($scopedManager)
            ->test(Dashboard::class)
            ->assertSee('Öz şöbənin işi')
            ->assertDontSee('Yad şöbənin işi');

        Livewire::actingAs($scopedManager)
            ->test(CaseDetail::class, ['case' => $foreignCase->id])
            ->assertNotFound();

        Livewire::actingAs($bystander)
            ->test(CaseDetail::class, ['case' => $ownCase->id])
            ->assertNotFound();

        Livewire::actingAs($noPermission)
            ->test(Dashboard::class)
            ->assertForbidden();
    }

    public function test_investigator_must_have_module_access_and_see_the_case_structure(): void
    {
        $own = $this->erStructure('Öz şöbə');
        $foreign = $this->erStructure('Yad şöbə');
        $case = $this->erOpenCase(
            $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true),
            $this->erPersonnel('Görünənov', $own->id),
        );

        $outOfScope = $this->erUser([CaseAccess::SHOW], [$foreign->id]);
        $withoutPermission = User::factory()->create();

        foreach ([$outOfScope, $withoutPermission] as $candidate) {
            try {
                app(CaseService::class)->assignInvestigator($case, $hr, $candidate->id);
                $this->fail('ineligible investigator was assigned');
            } catch (\Illuminate\Validation\ValidationException) {
                $this->assertSame(0, $case->investigators()->count());
            }
        }

        $eligible = collect(app(CaseService::class)->eligibleInvestigators($case))->pluck('id')->all();
        $this->assertContains($hr->id, $eligible);
        $this->assertNotContains($outOfScope->id, $eligible);
    }

    public function test_dashboard_create_form_opens_a_case_and_redirects_to_it(): void
    {
        $structure = $this->erStructure('Satış');
        $subject = $this->erPersonnel('Əliyev', $structure->id);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::CREATE], [$structure->id]);

        Livewire::actingAs($hr)
            ->test(Dashboard::class)
            ->call('openCreate')
            ->set('form.case_type_id', (string) $this->erTypeId('conflict'))
            ->set('form.title', 'Komanda daxilində münaqişə')
            ->set('form.description', 'İki əməkdaş arasında mübahisə yaranıb.')
            ->call('pickPersonnel', 'subject_personnel_id', $subject->id)
            ->call('saveCase')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('employee_relation_cases', [
            'title' => 'Komanda daxilində münaqişə',
            'subject_personnel_id' => $subject->id,
            'structure_id' => $structure->id,
            'created_by' => $hr->id,
        ]);
    }
}
