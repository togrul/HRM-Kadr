<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\OrderStatus;
use App\Models\Rank;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GlobalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedOrderStatuses();
        $this->seedMenus();
        $this->seedRolesAndPermissions();
        if($this->appType() === 'military') {
             $this->seedRanks();
             $this->seedSettings();
        }
    }

    private function appType(): string
    {
        return (string) config('app.app_type', env('APP_TYPE', 'public'));
    }

    private function seedOrderStatuses(): void
    {
        $orderStatuses = [
            ['id' => 10, 'locale' => 'az', 'name' => 'Təsdiq gözləyən'],
            ['id' => 20, 'locale' => 'az', 'name' => 'Təsdiqlənmiş'],
            ['id' => 30, 'locale' => 'az', 'name' => 'Ləğv edilmiş'],
        ];

        foreach ($orderStatuses as $status) {
                ['id' => $status['id']],
                Arr::only($status, ['locale', 'name'])
            );
        }
    }

    private function seedMenus(): void
    {
        $appType = $this->appType();
        $menus = (array) config('menus.global', []);

        foreach ($menus as $menu) {
            $types = $menu['types'] ?? [];
            if (! empty($types) && ! in_array($appType, $types, true)) {
                continue;
            }

            Menu::updateOrCreate(
                ['name' => $menu['name']],
                Arr::only($menu, ['icon', 'color', 'order', 'is_active', 'url'])
            );
        }
    }

    private function seedRolesAndPermissions(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'Admin'],
            [
                'guard_name' => 'web',
                'created_at' => '2023-11-03 23:49:10',
                'updated_at' => '2023-11-03 23:50:32',
            ]
        );

        $permissions = [
            ['name' => 'show-staff', 'guard_name' => 'web', 'created_at' => '2023-11-04 23:01:35', 'updated_at' => '2023-11-04 23:01:35'],
            ['name' => 'manage-staff', 'guard_name' => 'web', 'created_at' => '2023-11-04 23:01:35', 'updated_at' => '2023-11-04 23:01:35'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                Arr::only($permission, ['guard_name', 'created_at', 'updated_at'])
            );
        }

        foreach (Permission::all() as $permission) {
            $role->givePermissionTo($permission);
        }

        if (filter_var(env('SEED_ASSIGN_ALL_USERS', false), FILTER_VALIDATE_BOOL)) {
            foreach (User::all() as $user) {
                $user->syncRoles($role);
            }
            return;
        }

        $firstUser = User::query()->orderBy('id')->first();
        if ($firstUser) {
            $firstUser->syncRoles($role);
        }
    }

    private function seedRanks(): void
    {
        $ranks = [
            [
                'id' => 10,
                'name_az' => 'əsgər',
                'name_en' => 'soldier',
                'name_ru' => 'солдат',
                'duration' => null,
                'is_active' => 1,
            ],
            [
                'id' => 80,
                'name_az' => 'Lieutenant',
                'name_en' => 'Lieutenant',
                'name_ru' => 'Лейтенант',
                'duration' => 2.00,
                'is_active' => 1,
            ],
        ];

        foreach ($ranks as $rank) {
            Rank::updateOrCreate(
                ['id' => $rank['id']],
                Arr::only($rank, ['name_az', 'name_en', 'name_ru', 'duration', 'is_active'])
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            ['name' => 'Work coefficient', 'value' => '2', 'type' => 'double'],
            ['name' => 'Education coefficient', 'value' => '0.5', 'type' => 'double'],
            ['name' => 'Chief', 'value' => 'Fərid Əsgərov', 'type' => 'string'],
            ['name' => 'Chief rank', 'value' => 'general-mayor', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['name' => $setting['name']],
                Arr::only($setting, ['value', 'type'])
            );
        }
    }
}
