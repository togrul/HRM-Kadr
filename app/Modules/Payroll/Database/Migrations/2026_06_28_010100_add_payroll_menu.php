<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        $permissionId = Permission::query()
            ->where('name', 'show-payroll')
            ->where('guard_name', 'web')
            ->value('id');

        Menu::updateOrCreate(
            ['url' => 'payroll'],
            [
                'name' => 'ui::menu.items.payroll',
                'icon' => 'payroll-icon',
                'color' => 'zinc',
                'order' => 17,
                'is_active' => 1,
                'permission_id' => $permissionId,
            ],
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            Menu::where('url', 'payroll')->delete();
        }
    }
};
