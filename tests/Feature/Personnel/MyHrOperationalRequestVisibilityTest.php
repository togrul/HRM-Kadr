<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrOperationalRequestVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_self_service_vacation_is_hidden_until_it_is_approved(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('show-vacations', 'web'));
        DB::table('role_structures')->insert([
            'role_id' => $user->id,
            'structure_id' => 1,
        ]);

        $this->actingAs($user);

        $personnel = $this->makePersonnel();

        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Qax self-service pending',
            'duration' => 4,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-04',
            'return_work_date' => '2026-05-05',
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $user->id,
            'added_by' => $user->id,
        ]);

        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Quba approved visible',
            'duration' => 5,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'return_work_date' => '2026-06-06',
            'order_given_by' => 'HR review',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'approved',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $user->id,
            'added_by' => $user->id,
        ]);
        Livewire::test(\App\Modules\Vacation\Livewire\Vacations::class)
            ->assertDontSee('Qax self-service pending')
            ->assertSee('Quba approved visible');
    }

    public function test_pending_self_service_business_trip_is_hidden_until_it_is_approved(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('show-business_trips', 'web'));
        DB::table('role_structures')->insert([
            'role_id' => $user->id,
            'structure_id' => 1,
        ]);

        $this->actingAs($user);

        $personnel = $this->makePersonnel();

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'location' => 'Şəki self-service pending',
            'description' => 'Pending request',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-12',
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $user->id,
            'added_by' => $user->id,
        ]);

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'location' => 'Gəncə approved visible',
            'description' => 'Approved request',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'order_given_by' => 'HR review',
            'order_no' => null,
            'order_date' => null,
            'approval_status' => 'approved',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $user->id,
            'added_by' => $user->id,
        ]);
        Livewire::test(\App\Modules\BusinessTrips\Livewire\BusinessTrips::class)
            ->assertDontSee('Şəki self-service pending')
            ->assertSee('Gəncə approved visible');
    }

    private function makePersonnel(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.test',
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
