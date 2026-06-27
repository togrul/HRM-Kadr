<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Modules\Services\Livewire\Users\AddUser;
use App\Modules\Services\Livewire\Users\AllUsers;
use App\Modules\Services\Livewire\Users\DeleteUser;
use App\Modules\Services\Livewire\Users\EditUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_user_is_forbidden_without_manage_settings_permission(): void
    {
        $this->actingAs(User::factory()->create());

        // mount() must gate the component — an unprivileged user cannot even open it.
        Livewire::test(AddUser::class)->assertForbidden();
    }

    public function test_edit_user_is_forbidden_without_manage_settings_permission(): void
    {
        $victim = User::factory()->create(['password' => Hash::make('original-secret')]);

        $this->actingAs(User::factory()->create());

        // Regression for the account-takeover hole: an unprivileged user must not
        // be able to drive EditUser against an arbitrary target id.
        Livewire::test(EditUser::class, ['userModel' => $victim->id])->assertForbidden();
    }

    public function test_authorized_admin_can_create_user_and_password_is_hashed(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $this->actingAs($admin);

        $roleId = Role::findOrCreate('staff', 'web')->id;

        Livewire::test(AddUser::class)
            ->set('user.name', 'New Person')
            ->set('user.email', 'newperson@example.com')
            ->set('user.password', 'Str0ng-Pass-1')
            ->set('user.confirm-password', 'Str0ng-Pass-1')
            ->set('roleId', $roleId)
            ->call('store')
            ->assertHasNoErrors();

        $user = User::where('email', 'newperson@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Str0ng-Pass-1', $user->password));
    }

    public function test_weak_password_is_rejected_on_create(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $this->actingAs($admin);

        Livewire::test(AddUser::class)
            ->set('user.name', 'Weak Pass')
            ->set('user.email', 'weak@example.com')
            ->set('user.password', '1234')
            ->set('user.confirm-password', '1234')
            ->set('roleId', Role::findOrCreate('staff', 'web')->id)
            ->call('store')
            ->assertHasErrors('user.password');
    }

    public function test_delete_user_is_forbidden_without_permission(): void
    {
        $victim = User::factory()->create();

        $this->actingAs(User::factory()->create());

        // Regression for the commented-out authz hole: an unprivileged user must not be
        // able to arm the delete component against an arbitrary target.
        Livewire::test(DeleteUser::class)
            ->call('setDeleteUser', $victim->id)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id, 'deleted_at' => null]);
    }

    public function test_all_users_screen_is_forbidden_without_permission(): void
    {
        $this->actingAs(User::factory()->create());

        // mount() gates the whole screen — force-delete/restore are unreachable below it.
        Livewire::test(AllUsers::class)->assertForbidden();
    }

    public function test_authorized_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $this->actingAs($admin);

        $victim = User::factory()->create();

        Livewire::test(DeleteUser::class)
            ->call('setDeleteUser', $victim->id)
            ->call('deleteUser')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $victim->id]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'users', 'event' => 'deleted']);
    }

    public function test_authorized_admin_can_force_delete_and_restore(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $this->actingAs($admin);

        $trashed = User::factory()->create(['is_active' => false]);
        $trashed->delete();

        Livewire::test(AllUsers::class)
            ->call('restoreData', $trashed->id)
            ->assertHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $trashed->id, 'deleted_at' => null, 'is_active' => true]);

        $trashed->delete();
        Livewire::test(AllUsers::class)
            ->call('forceDeleteData', $trashed->id)
            ->assertHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $trashed->id]);

        $this->assertDatabaseHas('activity_log', ['log_name' => 'users', 'event' => 'restored']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'users', 'event' => 'force_deleted']);
    }
}
