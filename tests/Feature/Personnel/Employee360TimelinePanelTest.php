<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\Employee360Timeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Employee360TimelinePanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_see_employee_360_timeline_in_profile_context(): void
    {
        $personnel = $this->makePersonnel();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

        DB::table('leaves')->insert([
            'tabel_no' => $personnel->tabel_no,
            'starts_at' => '2026-04-02',
            'ends_at' => '2026-04-03',
            'reason' => 'Medical leave',
            'status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(Employee360Timeline::class, ['personnelId' => $personnel->id])
            ->assertSee(__('personnel::information.tabs.employee_360'))
            ->assertSee('Medical leave')
            ->set('type', 'leave')
            ->assertSee('Medical leave');
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'E360'.Str::upper(Str::random(5)),
            'surname' => 'Timeline',
            'name' => 'Profile',
            'patronymic' => 'User',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'employee360@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'E'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
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
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insertOrIgnore([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Officer',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
        DB::table('order_statuses')->insertOrIgnore([
            'id' => 1,
            'name' => 'Aktiv',
            'locale' => 'az',
        ]);
    }
}
