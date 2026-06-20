<?php

namespace Tests\Unit\Services;

use App\Models\ChiefDelegation;
use App\Models\Personnel;
use App\Models\Setting;
use App\Models\User;
use App\Services\Chief\ChiefResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function makePersonnel(string $tabelNo, string $surname, string $name, int $positionId): Personnel
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
            'structure_id' => 1,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => User::factory()->create()->id,
        ]));
    }
}
