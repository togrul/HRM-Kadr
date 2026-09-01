<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `/` is now the landing dashboard, so the "Şəxsi işlər" menu entry moves to the
 * personnel list's own route. Only that entry is repointed — other rows still
 * pointing at `home` (the inactive "queries" placeholder) stay as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('name', 'ui::menu.items.personal_affairs')
            ->where('url', 'home')
            ->update(['url' => 'personnel.index']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->where('name', 'ui::menu.items.personal_affairs')
            ->where('url', 'personnel.index')
            ->update(['url' => 'home']);
    }
};
