<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An explicit weekly working-time norm (hours) for an employee whose week is shorter than
 * the standard 40 hours (ƏM m.89.3): the reduced working time of m.91–93 (pregnant
 * women, mothers of a child under 1.5, single parents of a child under 3, harmful or
 * special-character work…) or an agreed part-time week. Age and disability are applied
 * from the employee's own data; this column covers what the record does not show.
 * Null means the standard week.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('personnels', 'weekly_hours_norm')) {
            return;
        }

        Schema::table('personnels', function (Blueprint $table): void {
            $table->decimal('weekly_hours_norm', 4, 1)->nullable()->after('working_time_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('personnels', 'weekly_hours_norm')) {
            return;
        }

        Schema::table('personnels', function (Blueprint $table): void {
            $table->dropColumn('weekly_hours_norm');
        });
    }
};
