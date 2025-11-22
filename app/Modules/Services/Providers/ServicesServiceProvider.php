<?php

namespace App\Modules\Services\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'services');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'services');
    }

    protected function componentMap(): array
    {
        return [
            'service' => \App\Modules\Services\Livewire\Service::class,

            'settings.settings-list' => \App\Modules\Services\Livewire\Settings\SettingsList::class,
            'settings.add-settings' => \App\Modules\Services\Livewire\Settings\AddSettings::class,
            'settings.delete-settings' => \App\Modules\Services\Livewire\Settings\DeleteSettings::class,

            'menus.all-menus' => \App\Modules\Services\Livewire\Menus\AllMenus::class,
            'menus.add-menu' => \App\Modules\Services\Livewire\Menus\AddMenu::class,
            'menus.edit-menu' => \App\Modules\Services\Livewire\Menus\EditMenu::class,
            'menus.delete-menu' => \App\Modules\Services\Livewire\Menus\DeleteMenu::class,

            'users.all-users' => \App\Modules\Services\Livewire\Users\AllUsers::class,
            'users.add-user' => \App\Modules\Services\Livewire\Users\AddUser::class,
            'users.edit-user' => \App\Modules\Services\Livewire\Users\EditUser::class,
            'users.delete-user' => \App\Modules\Services\Livewire\Users\DeleteUser::class,

            'ranks.all-ranks' => \App\Modules\Services\Livewire\Ranks\AllRanks::class,
            'ranks.add-rank' => \App\Modules\Services\Livewire\Ranks\AddRank::class,
            'ranks.edit-rank' => \App\Modules\Services\Livewire\Ranks\EditRank::class,
            'ranks.delete-rank' => \App\Modules\Services\Livewire\Ranks\DeleteRank::class,

            'components.all-components' => \App\Modules\Services\Livewire\Components\AllComponents::class,
            'components.add-component' => \App\Modules\Services\Livewire\Components\AddComponent::class,
            'components.edit-component' => \App\Modules\Services\Livewire\Components\EditComponent::class,
            'components.delete-component' => \App\Modules\Services\Livewire\Components\DeleteComponent::class,

            // roles & permissions
            'roles.manage-roles' => \App\Modules\Services\Livewire\Roles\ManageRoles::class,
            'roles.permissions' => \App\Modules\Services\Livewire\Roles\Permissions::class,
            'roles.delete-role' => \App\Modules\Services\Livewire\Roles\DeleteRole::class,
            'roles.delete-permission' => \App\Modules\Services\Livewire\Roles\DeletePermission::class,
            'roles.set-permission' => \App\Modules\Services\Livewire\Roles\SetPermission::class,
        ];
    }
}
