<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MyHrForcePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_must_reset_password_is_redirected_to_profile(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_reset_password' => true,
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));
        $user->assignRole(Role::findOrCreate(MyHrAccountProvisioningService::EMPLOYEE_ROLE, 'web'));

        $this->actingAs($user)
            ->get(route('my-hr'))
            ->assertRedirect(route('profile.edit', ['force_password_reset' => 1]));
    }

    public function test_profile_page_remains_accessible_during_forced_reset(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_reset_password' => true,
        ]);
        $user->assignRole(Role::findOrCreate(MyHrAccountProvisioningService::EMPLOYEE_ROLE, 'web'));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Şifrə yenilənməsi tələb olunur');
    }

    public function test_privileged_user_is_not_forced_through_self_service_password_reset_flow(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_reset_password' => true,
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));

        $this->actingAs($user)
            ->get(route('my-hr'))
            ->assertOk();
    }

    public function test_employee_self_service_user_logs_in_to_my_hr_by_default(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.test',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'must_reset_password' => false,
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));
        $user->assignRole(Role::findOrCreate(MyHrAccountProvisioningService::EMPLOYEE_ROLE, 'web'));

        $this->post(route('login'), [
            'email' => 'employee@example.test',
            'password' => 'secret-password',
        ])->assertRedirect(route('my-hr'));
    }
}
