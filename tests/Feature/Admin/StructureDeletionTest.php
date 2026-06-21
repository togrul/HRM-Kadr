<?php

namespace Tests\Feature\Admin;

use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Admin\Livewire\Structures;
use App\Services\Structures\StructureDependencyMap;
use App\Services\Structures\StructureDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class StructureDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependents_are_discovered_dynamically_from_the_schema(): void
    {
        $tables = array_column(app(StructureDependencyMap::class)->deletionOrder(), 'table');

        // Found via live foreign-key introspection…
        $this->assertContains('personnels', $tables);
        $this->assertContains('candidates', $tables);
        $this->assertContains('self_service_approval_routes', $tables);
        // …plus the unconstrained supplement (column exists but no DB foreign key).
        $this->assertContains('staff_schedules', $tables);
    }

    public function test_usage_is_detected_across_a_structure_and_its_descendants(): void
    {
        $parent = Structure::query()->create(['name' => 'Parent', 'shortname' => 'P']);
        $child = Structure::query()->create(['name' => 'Child', 'shortname' => 'C', 'parent_id' => $parent->id]);
        $free = Structure::query()->create(['name' => 'Free', 'shortname' => 'F']);
        $this->staffScheduleFor($child->id);

        $service = app(StructureDeletionService::class);

        $this->assertTrue($service->isUsed($parent->id), 'usage on a descendant counts for the parent');
        $this->assertFalse($service->isUsed($free->id));
    }

    public function test_cascade_removes_structure_descendants_and_dependents_and_reports_impact(): void
    {
        $parent = Structure::query()->create(['name' => 'Parent', 'shortname' => 'P']);
        $child = Structure::query()->create(['name' => 'Child', 'shortname' => 'C', 'parent_id' => $parent->id]);
        $free = Structure::query()->create(['name' => 'Free', 'shortname' => 'F']);
        $this->staffScheduleFor($child->id);

        $impact = app(StructureDeletionService::class)->cascadeDelete($parent->id);

        $this->assertDatabaseMissing('structures', ['id' => $parent->id]);
        $this->assertDatabaseMissing('structures', ['id' => $child->id]);
        $this->assertSame(0, StaffSchedule::query()->where('structure_id', $child->id)->count());
        $this->assertDatabaseHas('structures', ['id' => $free->id]); // unrelated structure untouched
        $this->assertSame(1, $impact['staff_schedules'] ?? 0);
    }

    public function test_dependent_entities_are_deleted_and_their_children_are_cascade_wired(): void
    {
        // The service removes the dependent ENTITY row; that entity's deep child records
        // are guaranteed to go with it because their foreign keys are ON DELETE CASCADE.
        // (FK enforcement itself can't be toggled here — RefreshDatabase wraps each test
        //  in a transaction and SQLite ignores PRAGMA foreign_keys inside one — so we
        //  assert the parent is deleted AND the cascade contract is declared in schema.)
        $user = User::factory()->create();
        $structure = Structure::query()->create(['name' => 'Unit', 'shortname' => 'U']);

        $candidateId = DB::table('candidates')->insertGetId([
            'surname' => 'X', 'name' => 'Y', 'patronymic' => 'Z', 'structure_id' => $structure->id,
            'height' => 175, 'status_id' => 30, 'gender' => 1, 'knowledge_test' => 0,
            'physical_fitness_exam' => 0, 'attitude_to_military' => 'liable', 'creator_id' => $user->id,
        ]);
        DB::table('candidate_talent_pool_entries')->insert([
            'candidate_id' => $candidateId, 'pool_name' => 'Reserve', 'status' => 'active',
        ]);

        app(StructureDeletionService::class)->cascadeDelete($structure->id);

        $this->assertDatabaseMissing('candidates', ['id' => $candidateId]);
        $this->assertDatabaseMissing('structures', ['id' => $structure->id]);

        $childFk = collect(\Illuminate\Support\Facades\Schema::getForeignKeys('candidate_talent_pool_entries'))
            ->first(fn ($fk) => $fk['foreign_table'] === 'candidates');
        $this->assertNotNull($childFk);
        $this->assertSame('cascade', strtolower((string) $childFk['on_delete']));
    }

    public function test_the_destructive_cascade_is_recorded_in_the_activity_log(): void
    {
        $this->actingAs(User::factory()->create());
        $structure = Structure::query()->create(['name' => 'Audited', 'shortname' => 'A']);
        $this->staffScheduleFor($structure->id);

        app(StructureDeletionService::class)->cascadeDelete($structure->id);

        $activity = Activity::query()->where('log_name', 'structures')->where('event', 'cascade_deleted')->latest('id')->first();

        $this->assertNotNull($activity, 'a destructive cascade must be audited');
        $this->assertSame('Audited', data_get($activity->properties, 'structure'));
        $this->assertSame(1, data_get($activity->properties, 'deleted_rows.staff_schedules'));
    }

    public function test_livewire_delete_warns_via_confirm_modal_then_performs(): void
    {
        $structure = Structure::query()->create(['name' => 'Doomed', 'shortname' => 'D']);
        $this->staffScheduleFor($structure->id);

        Livewire::actingAs(User::factory()->create())
            ->test(Structures::class)
            ->call('deleteModel', $structure->id)
            ->assertDispatched('confirm-structure-delete') // routed into the global confirm modal
            ->call('performDelete')
            ->assertDispatched('deleted');

        $this->assertDatabaseMissing('structures', ['id' => $structure->id]);
    }

    private function staffScheduleFor(int $structureId): void
    {
        StaffSchedule::query()->create([
            'structure_id' => $structureId, 'position_id' => null,
            'total' => 1, 'filled' => 0, 'vacant' => 1,
        ]);
    }
}
