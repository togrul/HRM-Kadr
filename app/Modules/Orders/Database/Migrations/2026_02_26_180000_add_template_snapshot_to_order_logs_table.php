<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_logs', function (Blueprint $table) {
            $table->foreignId('order_template_version_id')
                ->nullable()
                ->after('order_type_id')
                ->constrained('order_template_versions')
                ->nullOnDelete();

            $table->string('template_render_mode', 32)
                ->default('legacy')
                ->after('description');

            $table->json('template_snapshot')
                ->nullable()
                ->after('template_render_mode');

            $table->index(
                ['order_type_id', 'order_template_version_id'],
                'order_logs_type_template_version_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('order_logs', function (Blueprint $table) {
            $table->dropIndex('order_logs_type_template_version_idx');
            $table->dropConstrainedForeignId('order_template_version_id');
            $table->dropColumn(['template_render_mode', 'template_snapshot']);
        });
    }
};
