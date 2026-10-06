<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Long leave (spec §5.1): the leave days a card absorbed and each item's target before
 * it was scaled down for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('performance_scorecards', 'leave_days')) {
            Schema::table('performance_scorecards', function (Blueprint $table): void {
                $table->unsignedInteger('leave_days')->default(0)->after('prorata_factor');
            });
        }

        if (! Schema::hasColumn('performance_scorecard_items', 'original_target')) {
            Schema::table('performance_scorecard_items', function (Blueprint $table): void {
                $table->decimal('original_target', 14, 4)->nullable()->after('target');
            });
        }
    }

    public function down(): void
    {
        Schema::table('performance_scorecards', fn (Blueprint $table) => $table->dropColumn('leave_days'));
        Schema::table('performance_scorecard_items', fn (Blueprint $table) => $table->dropColumn('original_target'));
    }
};
