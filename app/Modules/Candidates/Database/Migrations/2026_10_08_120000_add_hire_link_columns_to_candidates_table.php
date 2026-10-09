<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a candidate to the employee and the "İşə qəbul" order created when the hire
 * order is approved. Indexed only (no foreign keys) so the candidates table does not
 * take a schema dependency on the Personnel / Orders tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table): void {
            if (! Schema::hasColumn('candidates', 'hired_personnel_id')) {
                $table->unsignedBigInteger('hired_personnel_id')->nullable()->index();
            }

            if (! Schema::hasColumn('candidates', 'hire_order_id')) {
                $table->unsignedBigInteger('hire_order_id')->nullable()->index();
            }

            if (! Schema::hasColumn('candidates', 'hire_order_no')) {
                $table->string('hire_order_no')->nullable();
            }

            if (! Schema::hasColumn('candidates', 'hired_at')) {
                $table->timestamp('hired_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table): void {
            foreach (['hired_personnel_id', 'hire_order_id'] as $column) {
                if (Schema::hasColumn('candidates', $column)) {
                    $table->dropIndex([$column]);
                }
            }
        });

        Schema::table('candidates', function (Blueprint $table): void {
            $table->dropColumn(array_values(array_filter(
                ['hired_personnel_id', 'hire_order_id', 'hire_order_no', 'hired_at'],
                fn (string $column): bool => Schema::hasColumn('candidates', $column),
            )));
        });
    }
};
