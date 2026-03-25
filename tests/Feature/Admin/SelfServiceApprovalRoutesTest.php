<?php

namespace Tests\Feature\Admin;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Admin\Livewire\SelfServiceApprovalRoutes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SelfServiceApprovalRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_self_service_approval_policy(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));

        $this->actingAs($user);

        Livewire::test(SelfServiceApprovalRoutes::class)
            ->call('openCrud')
            ->set('form.request_type', 'leave')
            ->set('form.include_primary_approver', true)
            ->set('form.include_upper_approver', true)
            ->set('form.hr_always_included', true)
            ->call('store')
            ->assertDispatched('selfServiceApprovalRouteUpdated');

        $this->assertDatabaseHas('self_service_approval_routes', [
            'request_type' => 'leave',
            'include_primary_approver' => 1,
            'include_upper_approver' => 1,
            'hr_always_included' => 1,
        ]);
    }

    private function makePersonnel(string $email, string $surname, string $name, string $patronymic, int $structureId, int $positionId): Personnel
    {
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
            'structure_id' => $structureId,
            'position_id' => $positionId,
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
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false]);
        }
        if (! DB::table('positions')->where('id', 2)->exists()) {
            DB::table('positions')->insert(['id' => 2, 'name' => 'Section Chief', 'approval_rank' => 20, 'is_approval_target' => true]);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
