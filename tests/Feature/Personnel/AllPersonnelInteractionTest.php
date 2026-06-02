<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AllPersonnelInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_personnel_can_open_add_personnel_side_menu(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-personnels', 'web'),
            Permission::findOrCreate('add-personnels', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('openSideMenu', 'add-personnel')
            ->assertSet('showSideMenu', 'add-personnel')
            ->assertSet('isSideModalOpen', true);
    }

    public function test_all_personnel_can_mount_filter_detail_flow(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('openFilter')
            ->assertSet('filterDetailMounted', true)
            ->assertSet('pendingFilterOpen', true)
            ->call('handleFilterDetailReady')
            ->assertSet('pendingFilterOpen', false);
    }

    public function test_authorized_user_can_open_onboarding_documents_side_menu(): void
    {
        $personnel = $this->makePersonnel();
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-personnels', 'web'),
            Permission::findOrCreate('assign-onboarding-documents', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('handleRowAction', 'open', [
                'type' => 'open',
                'menu' => 'onboarding-documents',
                'value' => (string) $personnel->id,
            ])
            ->assertSet('showSideMenu', 'onboarding-documents')
            ->assertSet('isSideModalOpen', true);
    }

    public function test_opening_personnel_profile_writes_access_log(): void
    {
        $personnel = $this->makePersonnel();
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-personnels', 'web'),
            Permission::findOrCreate('edit-personnels', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('handleRowAction', 'edit', [
                'type' => 'open',
                'menu' => 'edit-personnel',
                'value' => (string) $personnel->id,
            ])
            ->assertSet('showSideMenu', 'edit-personnel')
            ->assertSet('isSideModalOpen', true);

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'log_name' => 'personnel_access',
            'event' => 'profile_opened',
            'description' => 'Personnel profile opened',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => Personnel::class,
            'subject_id' => $personnel->id,
        ], config('activitylog.database_connection'));

        $properties = DB::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'))
            ->where('event', 'profile_opened')
            ->latest('id')
            ->value('properties');

        $properties = json_decode((string) $properties, true);

        $this->assertSame($personnel->id, $properties['viewed_personnel_id']);
        $this->assertSame($personnel->tabel_no, $properties['viewed_personnel_tabel_no']);
        $this->assertSame($personnel->fullname, $properties['viewed_personnel_fullname']);
    }

    public function test_authorized_user_can_open_learning_materials_side_menu(): void
    {
        $personnel = $this->makePersonnel();
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-personnels', 'web'),
            Permission::findOrCreate('assign-employee-content', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('handleRowAction', 'open', [
                'type' => 'open',
                'menu' => 'learning-materials',
                'value' => (string) $personnel->id,
            ])
            ->assertSet('showSideMenu', 'learning-materials')
            ->assertSet('isSideModalOpen', true);
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'employee@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }

        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert([
                'id' => 1,
                'country_id' => 1,
                'locale' => 'az',
                'title' => 'Azərbaycan',
            ]);
        }

        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bachelor',
            ]);
        }

        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert([
                'id' => 1,
                'name' => 'HQ',
                'shortname' => 'HQ',
                'parent_id' => null,
                'coefficient' => 1.10,
                'code' => 10,
                'level' => 1,
            ]);
        }

        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert([
                'id' => 1,
                'name' => 'Officer',
            ]);
        }

        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert([
                'id' => 1,
                'name_az' => 'Tam iş günü',
                'name_en' => 'Full time',
                'name_ru' => 'Full time',
            ]);
        }
    }
}
