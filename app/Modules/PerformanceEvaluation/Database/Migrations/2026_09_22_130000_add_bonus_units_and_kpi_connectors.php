<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bonus: per-unit results (M_bölmə) and per-position target %. KPIs: an external REST
 * source (credentials encrypted) with its last sync state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('performance_bonus_rules', 'unit_results')) {
            Schema::table('performance_bonus_rules', function (Blueprint $table): void {
                $table->json('unit_results')->nullable()->after('company_multipliers');
                $table->json('position_targets')->nullable()->after('target_pct');
            });
        }

        if (! Schema::hasColumn('performance_bonus_calculations', 'unit_mult')) {
            Schema::table('performance_bonus_calculations', function (Blueprint $table): void {
                $table->decimal('unit_mult', 6, 4)->default(1)->after('company_mult');
            });
        }

        if (! Schema::hasColumn('performance_kpis', 'integration_config')) {
            Schema::table('performance_kpis', function (Blueprint $table): void {
                $table->text('integration_config')->nullable()->after('source_metric');
                $table->timestamp('integration_synced_at')->nullable()->after('integration_config');
                $table->text('integration_error')->nullable()->after('integration_synced_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('performance_bonus_rules', fn (Blueprint $table) => $table->dropColumn(['unit_results', 'position_targets']));
        Schema::table('performance_bonus_calculations', fn (Blueprint $table) => $table->dropColumn('unit_mult'));
        Schema::table('performance_kpis', fn (Blueprint $table) => $table->dropColumn(['integration_config', 'integration_synced_at', 'integration_error']));
    }
};
