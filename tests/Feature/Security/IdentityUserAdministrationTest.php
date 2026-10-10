<?php

use App\Models\Role;
use App\Modules\Services\Livewire\Roles\ManageRoles;
use App\Modules\Services\Livewire\Roles\Permissions;
use App\Modules\Services\Livewire\Roles\SetPermission;
use App\Modules\Services\Livewire\Users\AddUser;
use App\Modules\Services\Livewire\Users\AllUsers;
use App\Modules\Services\Livewire\Users\DeleteUser;
use App\Modules\Services\Livewire\Users\EditUser;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * `access-settings` artıq tam admin deyil: istifadəçilər `manage-users`, rollar `manage-roles`
 * ilə idarə olunur; heç kim özündə olmayan hüququ başqasına (və ya özünə) verə, özündən
 * geniş hüquqlu hesabı dəyişə, öz rolunu dəyişə bilməz.
 */

function identityAdmin(array $extra = []): \App\Models\User
{
    return identityUser(array_merge(['access-settings', 'manage-users'], $extra), ['password' => Hash::make('Admin-Passw0rd-1')]);
}

it('does not open user management with access-settings alone', function (): void {
    $this->actingAs(identityUser(['access-settings']));

    Livewire::test(AllUsers::class)->assertForbidden();
    Livewire::test(AddUser::class)->assertForbidden();
});

it('does not open role management without manage-roles', function (): void {
    $this->actingAs(identityAdmin());

    Livewire::test(ManageRoles::class)->assertForbidden();
});

it('refuses to edit or delete a user who holds permissions the actor lacks', function (): void {
    $this->actingAs(identityAdmin());
    $superAdmin = identityUser(['access-settings', 'manage-users', 'manage-payroll']);

    Livewire::test(EditUser::class, ['userModel' => $superAdmin->id])->assertForbidden();
    Livewire::test(DeleteUser::class)->call('setDeleteUser', $superAdmin->id)->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
});

it('refuses to assign a role that carries permissions the actor lacks', function (): void {
    $this->actingAs(identityAdmin());
    $target = identityUser();
    $powerful = Role::query()->create(['name' => 'Payroll Boss', 'guard_name' => 'web']);
    $powerful->givePermissionTo(Permission::findOrCreate('manage-payroll', 'web'));

    Livewire::test(EditUser::class, ['userModel' => $target->id])
        ->set('roleId', $powerful->id)
        ->call('store')
        ->assertHasErrors('roleId');

    Livewire::test(AddUser::class)
        ->set('user.name', 'Yeni')
        ->set('user.email', 'yeni@example.test')
        ->set('user.password', 'Str0ng-Passw0rd-1')
        ->set('user.confirm-password', 'Str0ng-Passw0rd-1')
        ->set('roleId', $powerful->id)
        ->call('store')
        ->assertHasErrors('roleId');

    expect($target->fresh()->hasRole($powerful))->toBeFalse();
    $this->assertDatabaseMissing('users', ['email' => 'yeni@example.test']);
});

it('does not let an admin change their own role, status or e-mail', function (): void {
    $admin = identityAdmin();
    $mine = Role::query()->create(['name' => 'Settings', 'guard_name' => 'web']);
    $other = Role::query()->create(['name' => 'Other', 'guard_name' => 'web']);
    $admin->assignRole($mine);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['userModel' => $admin->id])
        ->set('roleId', $other->id)
        ->set('user.is_active', false)
        ->set('user.email', 'new-me@example.test')
        ->call('store')
        ->assertHasErrors(['roleId', 'user.is_active', 'user.email']);

    expect($admin->fresh()->hasRole($mine))->toBeTrue()
        ->and($admin->fresh()->email)->not->toBe('new-me@example.test');
});

it('requires the admin\'s own password to change another user\'s credentials', function (): void {
    $this->actingAs(identityAdmin());
    $target = identityUser([], ['email' => 'target@example.test']);
    $role = Role::query()->create(['name' => 'Plain', 'guard_name' => 'web']);

    Livewire::test(EditUser::class, ['userModel' => $target->id])
        ->set('roleId', $role->id)
        ->set('user.email', 'hijacked@example.test')
        ->call('store')
        ->assertHasErrors(['user.actor_password' => 'required'])
        ->set('user.actor_password', 'wrong')
        ->call('store')
        ->assertHasErrors('user.actor_password');

    expect($target->fresh()->email)->toBe('target@example.test');
});

it('does not let a role manager grant permissions they lack or edit their own role', function (): void {
    $manager = identityUser(['access-settings', 'manage-roles']);
    $ownRole = Role::query()->create(['name' => 'Role Managers', 'guard_name' => 'web']);
    $manager->assignRole($ownRole);
    $foreign = Role::query()->create(['name' => 'Clerks', 'guard_name' => 'web']);
    $payroll = Permission::findOrCreate('manage-payroll', 'web');
    $usersPermission = Permission::findOrCreate('manage-users', 'web');
    $this->actingAs($manager);

    Livewire::test(SetPermission::class, ['roleModel' => $foreign->id])
        ->set('permissionList', [$payroll->id])
        ->call('store')
        ->assertNotDispatched('permissionSet')
        ->assertDispatched('notify', type: 'error', message: __('services::roles.messages.role_exceeds_your_permissions'));

    Livewire::test(SetPermission::class, ['roleModel' => $ownRole->id])
        ->set('permissionList', [$usersPermission->id])
        ->call('store')
        ->assertNotDispatched('permissionSet')
        ->assertDispatched('notify', type: 'error', message: __('services::roles.messages.own_role_locked'));

    expect($foreign->fresh()->hasPermissionTo('manage-payroll'))->toBeFalse()
        ->and($ownRole->fresh()->hasPermissionTo('manage-users'))->toBeFalse();
});

it('protects system roles referenced by name from renaming and squatting', function (): void {
    $this->actingAs(identityUser(['access-settings', 'manage-roles']));
    $selfService = Role::query()->firstOrCreate(['name' => 'Employee Self-Service', 'guard_name' => 'web']);
    $plain = Role::query()->create(['name' => 'Plain', 'guard_name' => 'web']);

    Livewire::test(ManageRoles::class)
        ->call('editRole', $selfService->id)
        ->set('role_name', 'Renamed')
        ->call('store')
        ->assertHasErrors('role_name');

    Livewire::test(ManageRoles::class)
        ->call('editRole', $plain->id)
        ->set('role_name', 'HR Admin')
        ->call('store')
        ->assertHasErrors('role_name');

    expect($selfService->fresh()->name)->toBe('Employee Self-Service')
        ->and($plain->fresh()->name)->toBe('Plain');
});

it('keeps permission names immutable so permissions cannot be swapped', function (): void {
    $this->actingAs(identityUser(['access-settings', 'manage-roles']));
    $permission = Permission::findOrCreate('show-staff', 'web');

    Livewire::test(Permissions::class)
        ->call('editPermission', $permission->id)
        ->set('permission_name', 'manage-payroll')
        ->set('permission_description', 'Ştat cədvəlinə baxış icazəsi.')
        ->call('store');

    expect($permission->fresh()->name)->toBe('show-staff');
});
