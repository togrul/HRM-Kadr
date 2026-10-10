<?php

namespace Tests\Feature\Services;

use App\Models\Setting;
use App\Models\User;
use App\Modules\Services\Livewire\Ranks\DeleteRank;
use App\Modules\Services\Livewire\Roles\DeleteRole;
use App\Modules\Services\Livewire\Settings\AddSettings;
use App\Modules\Services\Livewire\Settings\DeleteSettings;
use App\Modules\Services\Livewire\Settings\SettingsList;
use App\Modules\Services\Livewire\Users\EditUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['password' => Hash::make('current-secret')]);
        $admin->givePermissionTo(
            Permission::findOrCreate('access-settings', 'web'),
            Permission::findOrCreate('manage-users', 'web'),
            Permission::findOrCreate('manage-roles', 'web'),
        );
        $admin->assignRole(Role::findOrCreate('staff', 'web'));
        $this->actingAs($admin);

        return $admin;
    }

    public function test_editing_own_password_requires_and_accepts_the_current_password(): void
    {
        $admin = $this->admin();

        $component = Livewire::test(EditUser::class, ['userModel' => $admin->id])
            ->assertSee(__('services::common.labels.current_password'))
            ->set('user.password', 'Brand-New-Pass-1')
            ->set('user.confirm-password', 'Brand-New-Pass-1')
            ->call('store')
            ->assertHasErrors(['user.old_password' => 'required']);

        $component->set('user.old_password', 'wrong-secret')
            ->call('store')
            ->assertHasErrors(['user.old_password']);

        $component->set('user.old_password', 'current-secret')
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('userAdded', __('services::users.messages.updated'));

        $this->assertTrue(Hash::check('Brand-New-Pass-1', $admin->refresh()->password));
    }

    public function test_editing_another_user_asks_for_the_admins_own_password_not_the_targets(): void
    {
        $this->admin();
        $other = User::factory()->create();

        $component = Livewire::test(EditUser::class, ['userModel' => $other->id])
            ->assertDontSee(__('services::common.labels.current_password'))
            ->assertSee(__('services::users.fields.actor_password'))
            ->set('roleId', Role::findOrCreate('staff', 'web')->id)
            ->set('user.password', 'Brand-New-Pass-1')
            ->set('user.confirm-password', 'Brand-New-Pass-1')
            ->call('store')
            ->assertHasErrors(['user.actor_password' => 'required']);

        $component->set('user.actor_password', 'current-secret')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('Brand-New-Pass-1', $other->refresh()->password));
    }

    public function test_admin_role_and_roles_with_users_cannot_be_deleted(): void
    {
        $this->admin();
        $adminRole = Role::findOrCreate('admin', 'web');
        $usedRole = Role::findOrCreate('staff', 'web');
        $freeRole = Role::findOrCreate('unused', 'web');

        Livewire::test(DeleteRole::class)
            ->call('setDeleteRole', $adminRole->id)
            ->assertNotDispatched('deleteRoleWasSet')
            ->assertDispatched('notify', type: 'error', message: __('services::roles.messages.admin_role_protected'));

        Livewire::test(DeleteRole::class)
            ->call('setDeleteRole', $usedRole->id)
            ->assertNotDispatched('deleteRoleWasSet')
            ->assertDispatched('notify', type: 'error', message: __('services::roles.messages.role_has_users'));

        // Assigned between opening the confirmation and confirming it: the delete itself re-checks.
        $late = Livewire::test(DeleteRole::class)->call('setDeleteRole', $freeRole->id)->assertDispatched('deleteRoleWasSet');
        $lateUser = User::factory()->create();
        $lateUser->assignRole($freeRole);
        $late->call('deleteRole')->assertNotDispatched('roleWasDeleted');
        $lateUser->removeRole($freeRole);

        Livewire::test(DeleteRole::class)
            ->call('setDeleteRole', $freeRole->id)
            ->call('deleteRole')
            ->assertDispatched('roleWasDeleted');

        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
        $this->assertDatabaseHas('roles', ['id' => $usedRole->id]);
        $this->assertDatabaseMissing('roles', ['id' => $freeRole->id]);
    }

    public function test_a_rank_used_by_personnel_cannot_be_deleted(): void
    {
        $this->admin();
        DB::table('ranks')->insert([['id' => 1, 'name_az' => 'Leytenant'], ['id' => 2, 'name_az' => 'Kapitan']]);
        DB::table('personnel_ranks')->insert(['tabel_no' => 'T1', 'rank_id' => 1, 'name' => 'x', 'given_date' => '2024-01-01']);

        Livewire::test(DeleteRank::class)
            ->call('setDeleteRank', 1)
            ->assertNotDispatched('deleteRankWasSet')
            ->assertDispatched('notify', type: 'error', message: __('services::ranks.messages.in_use'));

        Livewire::test(DeleteRank::class)
            ->call('setDeleteRank', 2)
            ->call('deleteRank')
            ->assertDispatched('rankWasDeleted');

        $this->assertDatabaseHas('ranks', ['id' => 1]);
        $this->assertDatabaseMissing('ranks', ['id' => 2]);
    }

    public function test_a_setting_read_by_the_application_cannot_be_deleted(): void
    {
        $this->admin();
        $used = Setting::query()->create(['name' => 'Work coefficient', 'value' => '1', 'type' => 'float']);
        $custom = Setting::query()->create(['name' => 'Custom thing', 'value' => '1', 'type' => 'int']);

        Livewire::test(DeleteSettings::class)
            ->call('setDeleteSettings', $used->id)
            ->assertNotDispatched('deleteSettingsWasSet')
            ->assertDispatched('notify', type: 'error', message: __('services::settings.messages.in_use'));

        Livewire::test(DeleteSettings::class)
            ->call('setDeleteSettings', $custom->id)
            ->call('deleteSetting')
            ->assertDispatched('settingsWasDeleted');

        $this->assertDatabaseHas('settings', ['id' => $used->id]);
        $this->assertDatabaseMissing('settings', ['id' => $custom->id]);
    }

    public function test_editing_a_setting_says_updated_and_adding_says_added(): void
    {
        $this->admin();
        Setting::query()->create(['name' => 'Custom thing', 'value' => '1', 'type' => 'int']);

        Livewire::test(SettingsList::class)
            ->set('setting.0.value', '5')
            ->assertDispatched('settingsUpdated', __('services::settings.messages.saved'));

        $this->assertSame('Tənzimləmə uğurla yeniləndi!', trans('services::settings.messages.saved', [], 'az'));

        Livewire::test(AddSettings::class)
            ->set('settings.name', 'Another')
            ->set('settings.value', '2')
            ->set('settings.type', 'int')
            ->call('store')
            ->assertDispatched('settingsUpdated', __('services::settings.messages.created'));
    }
}
