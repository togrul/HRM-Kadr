<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI phase 1: library (+ versions), position templates, scorecards and manual actuals.
 * Achievement, threshold, stretch and cap are percentages; target and range bounds are
 * in the KPI's own unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_kpis')) {
            Schema::create('performance_kpis', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type', 20)->default('quantitative');
                $table->string('direction', 20)->default('higher_better');
                $table->string('unit', 20)->default('percent');
                $table->string('data_source', 20)->default('manual');
                $table->string('frequency', 20)->default('quarterly');
                $table->string('aggregation', 10)->default('last');
                $table->string('perspective', 20)->default('process');
                $table->string('indicator_kind', 10)->nullable();
                $table->json('qualitative_scale')->nullable();
                $table->boolean('evidence_required')->default(false);
                $table->string('status', 20)->default('draft');
                $table->unsignedInteger('current_version')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status', 'perf_kpis_status_idx');
            });
        }

        if (! Schema::hasTable('performance_kpi_versions')) {
            Schema::create('performance_kpi_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_kpi_id')->constrained('performance_kpis', indexName: 'perf_kpi_versions_kpi_fk')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('snapshot');
                $table->timestamp('effective_from');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['performance_kpi_id', 'version'], 'perf_kpi_versions_unique');
            });
        }

        if (! Schema::hasTable('performance_kpi_templates')) {
            Schema::create('performance_kpi_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 40)->nullable();
                $table->string('period_type', 20)->default('quarterly');
                $table->decimal('kpi_weight_share', 5, 2)->default(100);
                $table->decimal('competency_weight_share', 5, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('performance_kpi_template_positions')) {
            Schema::create('performance_kpi_template_positions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_kpi_template_id')->constrained('performance_kpi_templates', indexName: 'perf_kpi_tpl_positions_tpl_fk')->cascadeOnDelete();
                $table->integer('position_id');
                $table->timestamps();

                $table->unique('position_id', 'perf_kpi_tpl_positions_position_unique');
                $table->foreign('position_id', 'perf_kpi_tpl_positions_position_fk')->references('id')->on('positions')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('performance_kpi_template_items')) {
            Schema::create('performance_kpi_template_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_kpi_template_id')->constrained('performance_kpi_templates', indexName: 'perf_kpi_tpl_items_tpl_fk')->cascadeOnDelete();
                $table->foreignId('performance_kpi_id')->constrained('performance_kpis', indexName: 'perf_kpi_tpl_items_kpi_fk')->restrictOnDelete();
                $this->targetColumns($table);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['performance_kpi_template_id', 'performance_kpi_id'], 'perf_kpi_tpl_items_unique');
            });
        }

        if (! Schema::hasTable('performance_scorecards')) {
            Schema::create('performance_scorecards', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_cycle_id')->constrained('performance_cycles', indexName: 'perf_scorecards_cycle_fk')->cascadeOnDelete();
                $table->foreignId('personnel_id')->constrained('personnels', indexName: 'perf_scorecards_personnel_fk')->cascadeOnDelete();
                $table->integer('position_id')->nullable();
                $table->foreignId('performance_kpi_template_id')->nullable()->constrained('performance_kpi_templates', indexName: 'perf_scorecards_tpl_fk')->nullOnDelete();
                $table->foreignId('manager_personnel_id')->nullable()->constrained('personnels', indexName: 'perf_scorecards_manager_fk')->nullOnDelete();
                $table->string('status', 30)->default('draft');
                $table->date('valid_from');
                $table->date('valid_to');
                $table->decimal('prorata_factor', 8, 4)->default(1);
                $table->decimal('kpi_weight_share', 5, 2)->default(100);
                $table->decimal('competency_weight_share', 5, 2)->default(0);
                $table->decimal('kpi_score', 8, 4)->nullable();
                $table->decimal('competency_score', 8, 4)->nullable();
                $table->decimal('final_score', 8, 4)->nullable();
                $table->decimal('calibrated_score', 8, 4)->nullable();
                $table->string('rating_category', 30)->nullable();
                $table->json('snapshot')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['performance_cycle_id', 'personnel_id', 'valid_from'], 'perf_scorecards_unique');
                $table->index(['performance_cycle_id', 'status'], 'perf_scorecards_cycle_status_idx');
            });
        }

        if (! Schema::hasTable('performance_scorecard_items')) {
            Schema::create('performance_scorecard_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_id')->constrained('performance_scorecards', indexName: 'perf_sc_items_scorecard_fk')->cascadeOnDelete();
                $table->foreignId('performance_kpi_id')->constrained('performance_kpis', indexName: 'perf_sc_items_kpi_fk')->restrictOnDelete();
                $table->foreignId('performance_kpi_version_id')->nullable()->constrained('performance_kpi_versions', indexName: 'perf_sc_items_version_fk')->nullOnDelete();
                $table->foreignId('performance_goal_id')->nullable()->constrained('performance_goals', indexName: 'perf_sc_items_goal_fk')->nullOnDelete();
                $this->targetColumns($table);
                $table->unsignedInteger('sort_order')->default(0);
                $table->decimal('actual', 14, 4)->nullable();
                $table->decimal('achievement', 8, 4)->nullable();
                $table->decimal('score', 8, 4)->nullable();
                $table->text('comment_employee')->nullable();
                $table->text('comment_manager')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_kpi_actuals')) {
            Schema::create('performance_kpi_actuals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_item_id')->constrained('performance_scorecard_items', indexName: 'perf_kpi_actuals_item_fk')->cascadeOnDelete();
                $table->decimal('value', 14, 4);
                $table->string('source', 20)->default('manual');
                $table->string('evidence_path')->nullable();
                $table->string('evidence_name')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('entered_by')->nullable()->constrained('users', indexName: 'perf_kpi_actuals_entered_fk')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users', indexName: 'perf_kpi_actuals_approved_fk')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['performance_scorecard_item_id', 'created_at'], 'perf_kpi_actuals_item_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_kpi_actuals');
        Schema::dropIfExists('performance_scorecard_items');
        Schema::dropIfExists('performance_scorecards');
        Schema::dropIfExists('performance_kpi_template_items');
        Schema::dropIfExists('performance_kpi_template_positions');
        Schema::dropIfExists('performance_kpi_templates');
        Schema::dropIfExists('performance_kpi_versions');
        Schema::dropIfExists('performance_kpis');
    }

    /**
     * Weight and target band shared by template items and the scorecard items copied from them.
     */
    private function targetColumns(Blueprint $table): void
    {
        $table->decimal('weight', 5, 2);
        $table->decimal('target', 14, 4)->nullable();
        $table->decimal('range_min', 14, 4)->nullable();
        $table->decimal('range_max', 14, 4)->nullable();
        $table->decimal('threshold', 8, 4)->nullable();
        $table->decimal('stretch', 8, 4)->nullable();
        $table->decimal('cap', 8, 4)->nullable();
        $table->boolean('target_editable')->default(false);
    }
};
