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
            // The foreign key goes first: MySQL refuses to drop an index a key still
            // depends on (errno 1553), and the composite index is the one backing it.
            // Names are discovered, never assumed — this cleanup meets databases built
            // by different paths, and one absent name aborts the whole drop.
            foreach ($this->foreignKeysOn('order_logs', 'order_template_version_id') as $key) {
                Schema::table('order_logs', function (Blueprint $table) use ($key) {
                    // SQLite reports its keys unnamed; there the column form is what
                    // triggers the table rebuild that actually removes the constraint.
                    $table->dropForeign($key['name'] ?: $key['columns']);
                });
            }

            // The index is composite — (order_type_id, order_template_version_id) — and
            // MySQL uses its leading column to back the order_type_id foreign key, so
            // dropping it outright is refused with errno 1553. MySQL does not need the
            // drop at all: it strips the column from the index itself and leaves a
            // single-column index behind. Only SQLite, whose drop-column rebuilds the
            // table, trips over an index that still names the column.
            if (Schema::getConnection()->getDriverName() === 'sqlite'
                && Schema::hasIndex('order_logs', 'order_logs_type_template_version_idx')) {
                Schema::table('order_logs', function (Blueprint $table) {
                    $table->dropIndex('order_logs_type_template_version_idx');
                });
            }

            Schema::table('order_logs', function (Blueprint $table) {
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

    /**
     * The foreign keys actually defined on a column, as the schema reports them.
     *
     * @return list<array{name: string|null, columns: list<string>}>
     */
    private function foreignKeysOn(string $table, string $column): array
    {
        return collect(Schema::getForeignKeys($table))
            ->filter(fn (array $key): bool => in_array($column, $key['columns'], true))
            ->values()
            ->all();
    }

    public function down(): void
    {
        // Irreversible cleanup: the legacy order-template engine has been retired
        // and its schema is intentionally not recreated.
    }
};
