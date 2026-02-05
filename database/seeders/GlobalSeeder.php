<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\OrderStatus;
use App\Models\Rank;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class GlobalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedOrderStatuses();
        $this->seedMenus();
        if ($this->appType() === 'military') {
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
            OrderStatus::updateOrCreate(
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
