<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI phase 3: the bonus rule of a cycle, one bonus line per closed/approved card, and
 * the HRM-internal metric a KPI may be filled from automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_bonus_rules')) {
            Schema::create('performance_bonus_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_cycle_id')->unique('perf_bonus_rules_cycle_uq')
                    ->constrained('performance_cycles', indexName: 'perf_bonus_rules_cycle_fk')->cascadeOnDelete();
                $table->string('mode', 10)->default('company');
                $table->decimal('target_pct', 6, 2)->default(15);
                $table->decimal('reward_months', 5, 2)->default(1);
                $table->json('payout_bands');
                $table->decimal('company_result', 6, 2)->nullable();
                $table->decimal('company_gate', 6, 2)->default(85);
                $table->decimal('gate_floor_pct', 6, 2)->default(0);
                $table->json('company_multipliers');
                $table->decimal('cap_pct', 6, 2)->default(150);
                $table->decimal('fund', 14, 2)->nullable();
                $table->boolean('scale_to_fund')->default(false);
                $table->string('currency', 3)->default('AZN');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_bonus_calculations')) {
            Schema::create('performance_bonus_calculations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_id')->unique('perf_bonus_calc_scorecard_uq')
                    ->constrained('performance_scorecards', indexName: 'perf_bonus_calc_scorecard_fk')->cascadeOnDelete();
                $table->foreignId('performance_cycle_id')->constrained('performance_cycles', indexName: 'perf_bonus_calc_cycle_fk')->cascadeOnDelete();
                $table->foreignId('personnel_id')->constrained('personnels', indexName: 'perf_bonus_calc_personnel_fk')->cascadeOnDelete();
                $table->string('mode', 10);
                $table->decimal('score', 8, 4)->nullable();
                $table->decimal('base_salary', 14, 2)->nullable();
                $table->decimal('period_months', 6, 2);
                $table->decimal('target_pct', 6, 2);
                $table->decimal('payout_pct', 6, 2);
                $table->decimal('company_mult', 6, 4);
                $table->decimal('prorata', 8, 4);
                $table->decimal('scale_factor', 8, 4)->default(1);
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3);
                $table->string('status', 20)->default('calculated');
                $table->unsignedBigInteger('order_log_id')->nullable();
                $table->timestamp('exported_at')->nullable();
                $table->string('export_batch', 40)->nullable();
                $table->timestamps();

                $table->index(['performance_cycle_id', 'status'], 'perf_bonus_calc_cycle_status_idx');
            });
        }

        if (! Schema::hasColumn('performance_kpis', 'source_metric')) {
            Schema::table('performance_kpis', function (Blueprint $table): void {
                $table->string('source_metric', 40)->nullable()->after('data_source');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_bonus_calculations');
        Schema::dropIfExists('performance_bonus_rules');

        if (Schema::hasColumn('performance_kpis', 'source_metric')) {
            Schema::table('performance_kpis', function (Blueprint $table): void {
                $table->dropColumn('source_metric');
            });
        }
    }
};
