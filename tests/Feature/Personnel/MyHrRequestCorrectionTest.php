<?php

namespace Tests\Feature\Personnel;

use App\Models\Leave;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrRequestCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_correction_request_for_existing_leave(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        Role::findOrCreate('Employee Self-Service', 'web');
        $user->assignRole('Employee Self-Service');
        $user->givePermissionTo([
            Permission::findOrCreate('show-my-hr', 'web'),
        ]);

        $personnel = $this->makePersonnel($user->email);
        $leave = Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => 1,
            'starts_at' => '2026-04-10',
            'ends_at' => '2026-04-10',
            'duration_unit' => 'day',
            'total_days' => 1,
            'reason' => 'Köhnə səbəb',
            'status_id' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(MyHrRequests::class, ['personnelId' => $personnel->id])
            ->call('openCorrectionForm', 'leave', $leave->id)
            ->set('correctionForm.starts_at', '2026-04-11')
            ->set('correctionForm.ends_at', '2026-04-12')
            ->set('correctionForm.reason', 'Tarix səhv düşüb')
            ->call('storeCorrectionRequest')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('employee_request_change_requests', [
            'requestable_type' => Leave::class,
            'requestable_id' => $leave->id,
            'personnel_id' => $personnel->id,
            'requested_by_user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
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
            DB::table('country_translations')->insert(['id' => 1, 'country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer']);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
        if (! DB::table('leave_types')->where('id', 1)->exists()) {
            DB::table('leave_types')->insert(['id' => 1, 'name' => 'İllik', 'attendance_code' => 'IL', 'max_days' => 20, 'requires_document' => false]);
        }
    }
}
