<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $menuDefinitions = [
            [
                'name' => 'ui::menu.items.attendance',
                'icon' => 'attendance-icon',
                'color' => 'zinc',
                'order' => 9,
                'is_active' => 1,
                'url' => 'attendance',
                'permission_name' => 'show-attendance',
            ],
            [
                'name' => 'ui::menu.items.training',
                'icon' => 'training-icon',
                'color' => 'zinc',
                'order' => 10,
                'is_active' => 1,
                'url' => 'training-needs',
                'permission_name' => 'show-training-needs',
            ],
            [
                'name' => 'ui::menu.items.performance',
                'icon' => 'performance-icon',
                'color' => 'zinc',
                'order' => 11,
                'is_active' => 1,
                'url' => 'performance-evaluation',
                'permission_name' => 'show-performance-evaluation',
            ],
        ];

        foreach ($menuDefinitions as $menu) {
            $permissionId = Permission::query()
                ->where('name', $menu['permission_name'])
                ->where('guard_name', 'web')
                ->value('id');

            Menu::query()->updateOrCreate(
                ['name' => $menu['name']],
                [
                    'icon' => $menu['icon'],
                    'color' => $menu['color'],
                    'order' => $menu['order'],
                    'is_active' => $menu['is_active'],
                    'url' => $menu['url'],
                    'permission_id' => $permissionId,
                ]
            );
        }

        $orderUpdates = [
            'ui::menu.items.reports' => 12,
            'ui::menu.items.onboarding_library' => 13,
            'ui::menu.items.learning_library' => 14,
            'ui::menu.items.self_service_reviews' => 15,
        ];

        foreach ($orderUpdates as $name => $order) {
            Menu::query()->where('name', $name)->update(['order' => $order]);
        }
    }

    public function down(): void
    {
        Menu::query()
            ->whereIn('name', [
                'ui::menu.items.attendance',
                'ui::menu.items.training',
                'ui::menu.items.performance',
            ])
            ->delete();

        $orderUpdates = [
            'ui::menu.items.reports' => 9,
            'ui::menu.items.onboarding_library' => 11,
            'ui::menu.items.learning_library' => 12,
            'ui::menu.items.self_service_reviews' => 13,
        ];

        foreach ($orderUpdates as $name => $order) {
            Menu::query()->where('name', $name)->update(['order' => $order]);
        }
    }
};
