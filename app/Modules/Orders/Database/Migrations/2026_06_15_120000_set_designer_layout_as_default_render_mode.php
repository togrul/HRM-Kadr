<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_template_versions')
            || ! Schema::hasColumn('order_template_versions', 'render_mode')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('order_template_versions', function (Blueprint $table): void {
            $table->string('render_mode', 32)->default('designer_layout')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_template_versions')
            || ! Schema::hasColumn('order_template_versions', 'render_mode')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('order_template_versions', function (Blueprint $table): void {
            $table->string('render_mode', 32)->default('metadata')->change();
        });
    }
};
