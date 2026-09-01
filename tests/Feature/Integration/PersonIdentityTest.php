<?php

namespace Tests\Feature\Integration;

use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\PersonRegistry;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The stable person identity.
 *
 * `tabel_no` remains the internal key — every domain table foreign-keys it and a
 * person restored under the same number keeps their history. What it cannot do
 * is identify a person to a system outside that cascade, and that gap is what
 * `person_uid` closes.
 *
 * The failure it prevents is invisible: a payroll system computing income tax on
 * a cumulative yearly base would split that base across two records and tax the
 * employee at a lower bracket, with no error anywhere.
 */
class PersonIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Country::query()->create(['id' => 1, 'code' => 'AZ']);
        EducationDegree::query()->create([
            'id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bakalavr',
        ]);
        WorkNorm::query()->create([
            'id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Polniy',
        ]);
        Structure::query()->create([
            'id' => 500, 'name' => 'IT', 'shortname' => 'IT',
            'parent_id' => null, 'coefficient' => 1, 'code' => 500, 'level' => 1,
        ]);
        Position::query()->create([
            'id' => 500, 'name' => 'Proqramçı', 'approval_rank' => 0, 'is_approval_target' => true,
        ]);
    }

    public function test_every_hire_receives_an_identity(): void
    {
        $person = $this->hire('TB-1');

        $this->assertNotEmpty($person->person_uid);
        $this->assertSame(36, strlen((string) $person->person_uid));
    }

    public function test_two_people_do_not_share_an_identity(): void
    {
        $this->assertNotSame($this->hire('TB-1')->person_uid, $this->hire('TB-2')->person_uid);
    }

    /**
     * The behaviour the staff-number design was chosen for, carried across the
     * system boundary: recreate a person under a number they held before and
     * they are the same person, not a new one.
     */
    public function test_recreating_under_the_same_number_restores_the_identity(): void
    {
        $first = $this->hire('TB-7');
        $uid = $first->person_uid;

        $first->forceDelete();

        $again = $this->hire('TB-7');

        $this->assertSame($uid, $again->person_uid);
        $this->assertNotSame($first->id, $again->id);
    }

    /**
     * Renumbering keeps the identity and adds a mapping.
     *
     * The old number keeps resolving to the same person, which is what keeps
     * historical records readable.
     */
    public function test_renumbering_keeps_the_identity(): void
    {
        $person = $this->hire('TB-9');
        $uid = $person->person_uid;

        $person->forceFill(['tabel_no' => 'TB-9-A'])->save();

        $this->assertSame($uid, $person->fresh()->person_uid);
        $this->assertSame($uid, PersonRegistry::query()->where('tabel_no', 'TB-9')->value('person_uid'));
        $this->assertSame($uid, PersonRegistry::query()->where('tabel_no', 'TB-9-A')->value('person_uid'));
    }

    /**
     * A re-hire under a NEW number is still the same person.
     *
     * Here the registry cannot help — the number is new — so the identity has to
     * be carried over deliberately by whoever records the re-hire. The test pins
     * that the mechanism supports it.
     */
    public function test_a_rehire_can_carry_the_previous_identity(): void
    {
        $first = $this->hire('TB-10');
        $uid = $first->person_uid;

        $first->forceFill(['leave_work_date' => '2026-05-31'])->save();
        $first->delete();

        $second = $this->hire('TB-11', $uid);

        $this->assertSame($uid, $second->person_uid);
        $this->assertSame(2, Personnel::withTrashed()->where('person_uid', $uid)->count());
    }

    /** An externally supplied identity is never overwritten. */
    public function test_a_supplied_identity_is_kept(): void
    {
        $uid = '11111111-2222-3333-4444-555555555555';

        $this->assertSame($uid, $this->hire('TB-12', $uid)->person_uid);
    }

    private function hire(string $tabelNo, ?string $personUid = null): Personnel
    {
        $user = User::factory()->create();

        $attributes = [
            'tabel_no' => $tabelNo,
            'surname' => 'Əliyev',
            'name' => 'Elçin',
            'patronymic' => 'Rəşad oğlu',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.$tabelNo,
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => 500,
            'position_id' => 500,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ];

        $person = new Personnel($attributes);

        if ($personUid !== null) {
            $person->person_uid = $personUid;
        }

        $person->save();

        return $person;
    }
}
