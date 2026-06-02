<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $payload = ['icon' => 'refresh-icon'];

        if (Schema::hasColumn('menus', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('menus')
            ->where('url', 'employee-lifecycle')
            ->orWhere('name', 'ui::menu.items.employee_lifecycle')
            ->update($payload);
    }

    public function down(): void
    {
        $payload = ['icon' => 'profile-outline-icon'];

        if (Schema::hasColumn('menus', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('menus')
            ->where('url', 'employee-lifecycle')
            ->orWhere('name', 'ui::menu.items.employee_lifecycle')
            ->update($payload);
    }
};
