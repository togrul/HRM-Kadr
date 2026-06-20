<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for hot filter/sort paths identified in the 2026-06-16 audit.
 *
 * Defensive on purpose: each change is guarded by hasTable/hasColumn/hasIndex so it
 * is safe on both the MySQL app database and the sqlite test database, and tolerant
 * of schema drift (e.g. structures.parent_id, which exists in production but is not
 * created by any migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        // order_logs: every orders list sorts by given_date and filters it by range.
        $this->addIndex('order_logs', ['given_date'], 'order_logs_given_date_index');

        // personnels: the default "current" status filter hits these on every list.
        $this->addIndex('personnels', ['is_pending', 'leave_work_date'], 'personnels_is_pending_leave_work_date_index');

        // Drop the redundant composite — its leading column (tabel_no) is already unique.
        $this->dropIndexIfExists('personnels', 'personnels_tabel_no_id_position_id_index');

        // structures: recursive parent walk on every structure selection.
        $this->addIndex('structures', ['parent_id'], 'structures_parent_id_index');

        // personnel_* history children: latestOfMany('given_date') / orderByDesc('given_date').
        foreach ([
            'personnel_ranks',
            'personnel_awards',
            'personnel_punishments',
            'personnel_weapons',
            'personnel_master_degrees',
            'personnel_scientific_degree_and_names',
        ] as $table) {
            $this->addIndex($table, ['tabel_no', 'given_date'], $table.'_tabel_no_given_date_index');
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('order_logs', 'order_logs_given_date_index');
        $this->dropIndexIfExists('personnels', 'personnels_is_pending_leave_work_date_index');
        $this->dropIndexIfExists('structures', 'structures_parent_id_index');

        foreach ([
            'personnel_ranks',
            'personnel_awards',
            'personnel_punishments',
            'personnel_weapons',
            'personnel_master_degrees',
            'personnel_scientific_degree_and_names',
        ] as $table) {
            $this->dropIndexIfExists($table, $table.'_tabel_no_given_date_index');
        }

        // Restore the original redundant index so the migration is reversible.
        if (Schema::hasTable('personnels')
            && Schema::hasColumn('personnels', 'position_id')
            && ! Schema::hasIndex('personnels', 'personnels_tabel_no_id_position_id_index')) {
            Schema::table('personnels', function (Blueprint $table) {
                $table->index(['tabel_no', 'id', 'position_id']);
            });
        }
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if (Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        }
    }
};
