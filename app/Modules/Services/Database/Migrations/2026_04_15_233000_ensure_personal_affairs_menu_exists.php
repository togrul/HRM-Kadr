<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole() || ! Schema::hasTable('menus')) {
            return;
        }

        $permissionId = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'show-personnels')
            ->value('id');

        $existing = Menu::query()
            ->where('name', 'ui::menu.items.personal_affairs')
            ->orderBy('id')
            ->first();

        if ($existing) {
            $existing->update([
                'icon' => 'personal-affair-icon',
                'color' => 'zinc',
                'order' => 3,
                'is_active' => 1,
                'url' => 'home',
                'permission_id' => $permissionId,
            ]);

            return;
        }

        Menu::query()->create([
            'name' => 'ui::menu.items.personal_affairs',
            'icon' => 'personal-affair-icon',
            'color' => 'zinc',
            'order' => 3,
            'is_active' => 1,
            'url' => 'home',
            'permission_id' => $permissionId,
        ]);
    }

    public function down(): void
    {
        // Intentional no-op.
    }
};
