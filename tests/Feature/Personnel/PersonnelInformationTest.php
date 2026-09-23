<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelDisposal;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Personnel\Livewire\Information;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The personnel "Information" drawer: contracts, education requests, master degrees,
 * pension cards, disposals and the 360 timeline, one tab each.
 */
class PersonnelInformationTest extends TestCase
{
    use RefreshDatabase;

    private const DISPOSALS_TAB = 4;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);
    }

    public function test_it_is_forbidden_without_the_right_to_edit_personnel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Information::class, ['personnelModel' => $this->person('T-1')->tabel_no])->assertForbidden();
    }

    public function test_only_the_open_tab_renders_and_switching_stays_cheap(): void
    {
        $this->actingAs($this->editor());
        $person = $this->person('T-1');
        $person->disposals()->create(['disposal_date' => '2026-02-01', 'disposal_reason' => 'Ezamiyyət səbəbi']);

        $screen = Livewire::test(Information::class, ['personnelModel' => $person->tabel_no])
            ->assertDontSee('Ezamiyyət səbəbi');

        DB::enableQueryLog();
        $screen->call('setCurrentStep', self::DISPOSALS_TAB)->assertSee('Ezamiyyət səbəbi');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The person, the permission check and the one open tab's list — not all five lists.
        $this->assertLessThanOrEqual(6, $queries);
    }

    public function test_row_actions_cannot_reach_another_employees_records(): void
    {
        $this->actingAs($this->editor());
        $mine = $this->person('T-1');
        $theirs = $this->person('T-2');
        $foreign = $theirs->disposals()->create(['disposal_date' => '2026-02-01', 'disposal_reason' => 'Başqasının qeydi']);
        $own = $mine->disposals()->create(['disposal_date' => '2026-03-01', 'disposal_reason' => 'Öz qeydim']);

        foreach (['forceDeleteDisposal', 'updateDisposal'] as $action) {
            Livewire::test(Information::class, ['personnelModel' => $mine->tabel_no])->call($action, $foreign->id)->assertNotFound();
        }
        $this->assertNotNull(PersonnelDisposal::query()->find($foreign->id));

        Livewire::test(Information::class, ['personnelModel' => $mine->tabel_no])->call('forceDeleteDisposal', $own->id);
        $this->assertNull(PersonnelDisposal::query()->find($own->id));
    }

    public function test_it_adds_a_disposal_to_the_open_employee(): void
    {
        $this->actingAs($this->editor());
        $person = $this->person('T-1');

        Livewire::test(Information::class, ['personnelModel' => $person->tabel_no])
            ->call('setCurrentStep', self::DISPOSALS_TAB)
            ->set('disposals.disposal_date', '10.04.2026')
            ->set('disposals.disposal_reason', 'Yeni qeyd')
            ->call('addDisposal')
            ->assertHasNoErrors()
            ->assertSee('Yeni qeyd');

        $this->assertSame(1, $person->disposals()->count());
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-personnels', 'web'));

        return $user;
    }

    private function person(string $tabelNo): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => $tabelNo,
            'surname' => 'Test',
            'name' => 'Person',
            'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => strtolower($tabelNo).'@example.test',
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => 'PIN'.$tabelNo,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-01-05',
            'added_by' => 1,
        ]));
    }
}
