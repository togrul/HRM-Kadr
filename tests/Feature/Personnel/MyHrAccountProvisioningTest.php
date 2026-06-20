<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrAccountProvisioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrAccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_provision_self_service_account_for_personnel(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('manage-my-hr-accounts', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');

        Livewire::actingAs($hr)
            ->test(MyHrAccountProvisioning::class, ['personnelModel' => $personnel->id])
            ->call('provision')
            ->assertSet('resetUrl', fn ($value) => is_string($value) && str_contains($value, 'reset-password'))
            ->assertDispatched('notify');

        $user = User::query()->where('email', 'employee@example.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->must_reset_password);
        $this->assertNotNull($user->self_service_invited_at);
        $this->assertTrue($user->hasRole('Employee Self-Service'));
        $this->assertTrue($user->can('view-own-onboarding-documents'));
        $this->assertTrue($user->can('acknowledge-own-onboarding-documents'));
        $this->assertDatabaseHas('user_personnel_links', [
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'self_service_provisioned',
        ]);
    }

    public function test_existing_linked_user_gets_reset_link_regenerated(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('manage-my-hr-accounts', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'is_active' => true,
            'must_reset_password' => false,
        ]);

        DB::table('user_personnel_links')->insert([
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'manual',
            'resolved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($hr)
            ->test(MyHrAccountProvisioning::class, ['personnelModel' => $personnel->id])
            ->call('provision')
            ->assertSet('resetUrl', fn ($value) => is_string($value) && str_contains($value, 'reset-password'));

        $this->assertTrue((bool) $user->fresh()->must_reset_password);
    }

    public function test_personnel_without_email_cannot_be_provisioned(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('manage-my-hr-accounts', 'web'));

        $personnel = $this->makePersonnel(null);

        Livewire::actingAs($hr)
            ->test(MyHrAccountProvisioning::class, ['personnelModel' => $personnel->id])
            ->call('provision')
            ->assertHasErrors(['provision']);
    }

    public function test_hr_can_save_manual_explicit_link_for_existing_user(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('manage-my-hr-accounts', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');
        $user = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.test',
            'is_active' => true,
        ]);

        Livewire::actingAs($hr)
            ->test(MyHrAccountProvisioning::class, ['personnelModel' => $personnel->id])
            ->set('manualLink.user_id', $user->id)
            ->call('saveManualLink')
            ->assertSet('manualLink.user_id', $user->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('user_personnel_links', [
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'manual_self_service_link',
        ]);
        $this->assertTrue($user->fresh()->hasRole('Employee Self-Service'));
    }

    public function test_existing_user_with_same_email_requires_manual_link_instead_of_auto_claim(): void
    {
        $this->seedReferenceData();

        $hr = User::factory()->create(['is_active' => true]);
        $hr->givePermissionTo(Permission::findOrCreate('manage-my-hr-accounts', 'web'));

        $personnel = $this->makePersonnel('employee@example.test');
        $existingUser = User::factory()->create([
            'email' => 'employee@example.test',
            'is_active' => true,
            'must_reset_password' => false,
        ]);

        Livewire::actingAs($hr)
            ->test(MyHrAccountProvisioning::class, ['personnelModel' => $personnel->id])
            ->call('provision')
            ->assertHasErrors(['provision']);

        $this->assertFalse((bool) $existingUser->fresh()->must_reset_password);
        $this->assertDatabaseMissing('user_personnel_links', [
            'user_id' => $existingUser->id,
            'personnel_id' => $personnel->id,
        ]);
    }

    private function makePersonnel(?string $email): Personnel
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
        Permission::findOrCreate('show-my-hr', 'web');
        Permission::findOrCreate('manage-my-hr-accounts', 'web');
        Permission::findOrCreate('view-own-onboarding-documents', 'web');
        Permission::findOrCreate('acknowledge-own-onboarding-documents', 'web');
        Role::findOrCreate('Employee Self-Service', 'web');

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
