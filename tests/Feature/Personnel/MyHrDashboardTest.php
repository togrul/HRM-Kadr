<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_route_requires_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('my-hr'))
            ->assertForbidden();
    }

    public function test_my_hr_shows_remediation_state_when_personnel_link_cannot_be_resolved(): void
    {
        $user = $this->makeUserWithPermission('show-my-hr');

        $this->actingAs($user)
            ->get(route('my-hr'))
            ->assertOk()
            ->assertSee('Şəxsi kabinet hələ əməkdaş kartı ilə bağlanmayıb')
            ->assertSee('HR administratoru user hesabını aktiv əməkdaş kartı ilə bağladıqdan sonra');
    }

    public function test_my_hr_bootstraps_linked_personnel_context_from_user_identity(): void
    {
        $user = $this->makeUserWithPermission('show-my-hr', 'employee@example.test');
        $employee = $this->makePersonnel($user->email, 'Doe', 'Jane', 'Smith');

        $this->actingAs($user)
            ->get(route('my-hr'))
            ->assertOk()
            ->assertSee('Şəxsi kabinet')
            ->assertSee('Doe Jane Smith')
            ->assertSee('Officer')
            ->assertSee('HQ')
            ->assertSee($employee->email);
    }

    private function makeUserWithPermission(string $permission, ?string $email = null): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email' => $email ?: 'user-'.Str::lower(Str::random(8)).'@example.test',
        ]);

        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }

    private function makePersonnel(string $email, string $surname, string $name, string $patronymic): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => $name,
            'patronymic' => $patronymic,
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
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
