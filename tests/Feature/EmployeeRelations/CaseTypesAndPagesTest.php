<?php

namespace Tests\Feature\EmployeeRelations;

use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Modules\EmployeeRelations\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * İş növləri soraqçasının redaktəsi və ekranların tam səhifə kimi açılması.
 */
class CaseTypesAndPagesTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    public function test_case_types_are_seeded_and_editable_by_managers_only(): void
    {
        $this->assertSame(
            ['complaint', 'disciplinary', 'conflict', 'harassment', 'safety'],
            RelationCaseType::query()->orderBy('sort_order')->pluck('code')->all(),
        );

        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);

        Livewire::actingAs($hr)
            ->test(Dashboard::class)
            ->call('setTab', 'types')
            ->set('typeForm.name', 'Etik pozuntu')
            ->set('typeForm.investigation_sla_days', 15)
            ->set('typeForm.is_disciplinary', true)
            ->call('saveType')
            ->assertHasNoErrors()
            ->assertSee('Etik pozuntu');

        $type = RelationCaseType::query()->where('name', 'Etik pozuntu')->firstOrFail();
        $this->assertSame('etik_pozuntu', $type->code);
        $this->assertSame(15, $type->investigation_sla_days);

        Livewire::actingAs($hr)
            ->test(Dashboard::class)
            ->call('toggleType', $type->id);
        $this->assertFalse($type->fresh()->is_active);

        $viewer = $this->erUser([CaseAccess::SHOW], allStructures: true);
        Livewire::actingAs($viewer)
            ->test(Dashboard::class)
            ->call('setTab', 'types')
            ->assertForbidden();
    }

    public function test_pages_render_for_authorized_users(): void
    {
        $structure = $this->erStructure('Xidmət');
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE, CaseAccess::STATISTICS], [$structure->id]);
        $case = $this->erOpenCase($hr, $this->erPersonnel('Səhifəyev', $structure->id), ['title' => 'Səhifə yoxlaması']);
        app(CaseWorkflow::class)->transition($case, CaseStatus::Investigating, $hr);

        $this->actingAs($hr)->get(route('employee-relations'))->assertOk()->assertSee('Səhifə yoxlaması');
        $this->actingAs($hr)->get(route('employee-relations', ['tab' => 'statistics']))->assertOk();
        $this->actingAs($hr)->get(route('employee-relations', ['tab' => 'types']))->assertOk();
        $this->actingAs($hr)->get(route('employee-relations.cases.show', ['case' => $case->id]))
            ->assertOk()
            ->assertSee(__('employee_relations::cases.sections.make_decision'));

        $stranger = $this->erUser([]);
        $this->actingAs($stranger)->get(route('employee-relations'))->assertForbidden();
        $this->actingAs($stranger)->get(route('employee-relations.cases.show', ['case' => $case->id]))->assertNotFound();
    }
}
