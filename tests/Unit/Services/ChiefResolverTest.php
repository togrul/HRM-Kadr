<?php

namespace Tests\Unit\Services;

use App\Models\ChiefDelegation;
use App\Models\Personnel;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Services\Livewire\Settings\SettingsList;
use App\Services\Chief\ChiefResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChiefResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_highest_active_position_as_default_chief(): void
    {
        $this->seedReferenceData();

        $manager = $this->makePersonnel('T-001', 'Manager', 'One', positionId: 1);
        $chief = $this->makePersonnel('T-002', 'Chief', 'Main', positionId: 2);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame('permanent', $snapshot['mode']);
        $this->assertSame($chief->id, $snapshot['personnel_id']);
        $this->assertSame('Chief Main Test', $snapshot['fullname']);
        $this->assertSame('Baş direktor', $snapshot['title']);
        $this->assertNotSame($manager->id, $snapshot['personnel_id']);
    }

    public function test_with_no_approval_ranks_the_most_senior_level_signs(): void
    {
        $this->seedReferenceData();
        DB::table('positions')->update(['approval_rank' => 0]);
        DB::table('positions')->insert(['id' => 3, 'name' => 'Hüquqşünas', 'approval_rank' => 0, 'is_approval_target' => true, 'level' => 6]);
        DB::table('positions')->where('id', 2)->update(['level' => 1]);

        // Created first, so the old id tie-break made a lawyer the signatory.
        $this->makePersonnel('T-001', 'Lawyer', 'First', positionId: 3);
        $director = $this->makePersonnel('T-002', 'Director', 'Real', positionId: 2);

        $this->assertSame($director->id, app(ChiefResolver::class)->current('2026-06-13')['personnel_id']);
    }

    public function test_a_high_approval_rank_does_not_make_an_engineer_the_chief(): void
    {
        // HRM-72: istehsalatda "Mühəndis" vəzifəsinə yüksək approval_rank verilmişdi və o, direktoru üstələyirdi.
        $this->seedReferenceData();
        DB::table('structures')->insert(['id' => 2, 'name' => 'İstehsalat', 'shortname' => 'İst', 'parent_id' => 1, 'code' => 2, 'level' => 2, 'coefficient' => 1]);
        DB::table('positions')->insert([
            ['id' => 3, 'name' => 'Mühəndis', 'approval_rank' => 500, 'is_approval_target' => true, 'level' => 6],
            ['id' => 4, 'name' => 'Direktor', 'approval_rank' => 10, 'is_approval_target' => true, 'level' => 1],
        ]);

        $engineer = $this->makePersonnel('T-001', 'Cumayılov', 'Xaqani', positionId: 3, structureId: 2);
        $director = $this->makePersonnel('T-002', 'Nəcəfli', 'Samid', positionId: 4);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame($director->id, $snapshot['personnel_id']);
        $this->assertNotSame($engineer->id, $snapshot['personnel_id']);
        $this->assertSame('automatic', $snapshot['permanent_chief_selection']);
        $this->assertSame(1, $snapshot['permanent_chief_level']);
    }

    public function test_a_position_without_a_stored_level_is_classified_by_its_name(): void
    {
        $this->seedReferenceData();
        DB::table('positions')->insert([
            ['id' => 3, 'name' => 'Mühəndis', 'approval_rank' => 500, 'is_approval_target' => true, 'level' => null],
            ['id' => 4, 'name' => 'Direktor', 'approval_rank' => 0, 'is_approval_target' => true, 'level' => null],
        ]);

        $this->makePersonnel('T-001', 'Engineer', 'One', positionId: 3);
        $director = $this->makePersonnel('T-002', 'Director', 'Two', positionId: 4);

        $this->assertSame($director->id, app(ChiefResolver::class)->current('2026-06-13')['personnel_id']);
    }

    public function test_among_equal_levels_the_root_structure_then_seniority_wins(): void
    {
        $this->seedReferenceData();
        DB::table('structures')->insert(['id' => 2, 'name' => 'Filial', 'shortname' => 'Filial', 'parent_id' => 1, 'code' => 2, 'level' => 2, 'coefficient' => 1]);
        DB::table('positions')->insert([
            ['id' => 3, 'name' => 'Filial direktoru', 'approval_rank' => 900, 'is_approval_target' => true, 'level' => 1],
            ['id' => 4, 'name' => 'Direktor', 'approval_rank' => 10, 'is_approval_target' => true, 'level' => 1],
        ]);

        $this->makePersonnel('T-001', 'Branch', 'Head', positionId: 3, structureId: 2, joinDate: '2010-01-01');
        $newer = $this->makePersonnel('T-002', 'Head', 'Newer', positionId: 4, joinDate: '2022-01-01');
        $older = $this->makePersonnel('T-003', 'Head', 'Older', positionId: 4, joinDate: '2015-01-01');

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame($older->id, $snapshot['personnel_id']);
        $this->assertNotSame($newer->id, $snapshot['personnel_id']);
    }

    public function test_a_dismissed_director_is_skipped_and_the_fallback_is_flagged(): void
    {
        $this->seedReferenceData();
        DB::table('positions')->insert(['id' => 4, 'name' => 'Direktor', 'approval_rank' => 0, 'is_approval_target' => true, 'level' => 1]);

        $dismissed = $this->makePersonnel('T-001', 'Former', 'Director', positionId: 4);
        $dismissed->forceFill(['leave_work_date' => '2026-01-31'])->saveQuietly();
        $head = $this->makePersonnel('T-002', 'Dept', 'Head', positionId: 1);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame($head->id, $snapshot['personnel_id']);
        $this->assertSame('automatic', $snapshot['permanent_chief_selection']);
        $this->assertSame(4, $snapshot['permanent_chief_level']);
    }

    public function test_a_manual_choice_overrides_the_automatic_rule(): void
    {
        $this->seedReferenceData();
        $this->makePersonnel('T-001', 'Chief', 'Main', positionId: 2);
        $manager = $this->makePersonnel('T-002', 'Manager', 'One', positionId: 1);
        Setting::query()->create(['name' => 'Chief personnel id', 'value' => (string) $manager->id, 'type' => 'string']);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame($manager->id, $snapshot['personnel_id']);
        $this->assertSame('manual', $snapshot['permanent_chief_selection']);
    }

    public function test_settings_warn_when_the_automatic_chief_is_not_a_director(): void
    {
        $this->seedReferenceData();
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $admin->assignRole(Role::findOrCreate('staff', 'web'));
        $this->actingAs($admin);

        $this->makePersonnel('T-001', 'Manager', 'One', positionId: 1);

        Livewire::test(SettingsList::class)
            ->assertSee(__('services::settings.messages.automatic_chief_not_director'));

        $this->makePersonnel('T-002', 'Chief', 'Main', positionId: 2);

        Livewire::test(SettingsList::class)
            ->assertDontSee(__('services::settings.messages.automatic_chief_not_director'));
    }

    public function test_it_resolves_active_delegation_for_effective_date(): void
    {
        $this->seedReferenceData();

        $chief = $this->makePersonnel('T-010', 'Chief', 'Main', positionId: 2);
        $delegate = $this->makePersonnel('T-011', 'Delegate', 'Acting', positionId: 1);

        ChiefDelegation::query()->create([
            'chief_personnel_id' => $chief->id,
            'delegate_personnel_id' => $delegate->id,
            'starts_at' => '2026-06-10',
            'ends_at' => '2026-06-20',
            'reason' => 'leave',
            'is_active' => true,
            'created_by' => User::factory()->create()->id,
        ]);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame('delegated', $snapshot['mode']);
        $this->assertSame($delegate->id, $snapshot['personnel_id']);
        $this->assertSame($chief->id, $snapshot['permanent_chief_personnel_id']);
        $this->assertSame('Delegate Acting Test', $snapshot['fullname']);
        $this->assertSame('Şöbə müdiri', $snapshot['title']);
        $this->assertSame('leave', $snapshot['delegation_reason']);
    }

    public function test_it_falls_back_to_legacy_settings_when_no_personnel_exists(): void
    {
        Setting::query()->create(['name' => 'Chief', 'value' => 'Fərid Əsgərov', 'type' => 'string']);
        Setting::query()->create(['name' => 'Chief rank', 'value' => 'general-mayor', 'type' => 'string']);

        $snapshot = app(ChiefResolver::class)->current('2026-06-13');

        $this->assertSame('legacy', $snapshot['mode']);
        $this->assertSame('Fərid Əsgərov', $snapshot['fullname']);
        $this->assertSame('general-mayor', $snapshot['title']);
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Ali',
            'title_en' => 'Higher',
            'title_ru' => 'Higher',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'Baş ofis',
            'shortname' => 'Baş ofis',
            'parent_id' => null,
            'code' => 1,
            'level' => 1,
            'coefficient' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            ['id' => 1, 'name' => 'Şöbə müdiri', 'approval_rank' => 20, 'is_approval_target' => true],
            ['id' => 2, 'name' => 'Baş direktor', 'approval_rank' => 100, 'is_approval_target' => true],
        ]);
    }

    private function makePersonnel(string $tabelNo, string $surname, string $name, int $positionId, int $structureId = 1, string $joinDate = '2020-01-01'): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => $tabelNo,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => $joinDate,
            'added_by' => User::factory()->create()->id,
        ]));
    }
}
