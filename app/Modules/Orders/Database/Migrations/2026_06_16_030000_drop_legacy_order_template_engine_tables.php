<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the legacy order-template engine tables. The dynamic order generation,
 * editing and template design now run entirely on the block engine
 * (order_block_templates + order_logs.template_snapshot), so these tables and
 * their models/services no longer exist in the codebase.
 *
 * Dropped FK-child-first so foreign keys never block the drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The block engine never populates order_logs.order_template_version_id;
        // drop its foreign key + the now-dead column so the parent table can go.
        if (Schema::hasColumn('order_logs', 'order_template_version_id')) {
            Schema::table('order_logs', function (Blueprint $table) {
                // Drop the composite index that includes the column first, otherwise
                // SQLite's drop-column table rebuild fails on the dangling index.
                $table->dropIndex('order_logs_type_template_version_idx');
                // Column-array form: derives the conventional FK name on MySQL and
                // is handled via table rebuild on SQLite (test driver).
                $table->dropForeign(['order_template_version_id']);
                $table->dropColumn('order_template_version_id');
            });
        }

        Schema::dropIfExists('order_generation_logs');
        Schema::dropIfExists('order_template_version_audits');
        Schema::dropIfExists('order_template_block_variables');
        Schema::dropIfExists('order_template_blocks');
        Schema::dropIfExists('order_template_mappings');
        Schema::dropIfExists('order_template_fields');
        Schema::dropIfExists('order_template_versions');
        Schema::dropIfExists('order_template_sets');
    }

    public function down(): void
    {
        // Irreversible cleanup: the legacy order-template engine has been retired
        // and its schema is intentionally not recreated.
    }
};
