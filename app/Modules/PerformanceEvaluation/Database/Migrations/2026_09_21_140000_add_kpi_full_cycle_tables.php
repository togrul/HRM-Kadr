<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI phase 2: a template's competency form, a card's linked evaluation form and stage
 * deadline, the card's status history, check-ins and calibration adjustments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('performance_kpi_templates', 'performance_form_template_id')) {
            Schema::table('performance_kpi_templates', function (Blueprint $table): void {
                $table->foreignId('performance_form_template_id')->nullable()->after('competency_weight_share')
                    ->constrained('performance_form_templates', indexName: 'perf_kpi_tpl_form_tpl_fk')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('performance_scorecards', 'performance_form_id')) {
            Schema::table('performance_scorecards', function (Blueprint $table): void {
                $table->foreignId('performance_form_id')->nullable()->after('performance_kpi_template_id')
                    ->constrained('performance_forms', indexName: 'perf_scorecards_form_fk')->nullOnDelete();
                $table->date('stage_due_at')->nullable()->after('status');
                $table->timestamp('reminded_at')->nullable()->after('stage_due_at');
                $table->timestamp('escalated_at')->nullable()->after('reminded_at');
                $table->string('closure_reason', 30)->nullable()->after('locked_at');
            });
        }

        if (! Schema::hasTable('performance_scorecard_events')) {
            Schema::create('performance_scorecard_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_id')->constrained('performance_scorecards', indexName: 'perf_sc_events_scorecard_fk')->cascadeOnDelete();
                $table->string('action', 40);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users', indexName: 'perf_sc_events_user_fk')->nullOnDelete();
                $table->timestamps();

                $table->index(['performance_scorecard_id', 'created_at'], 'perf_sc_events_scorecard_created_idx');
            });
        }

        if (! Schema::hasTable('performance_scorecard_checkins')) {
            Schema::create('performance_scorecard_checkins', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_id')->constrained('performance_scorecards', indexName: 'perf_sc_checkins_scorecard_fk')->cascadeOnDelete();
                $table->date('checkin_date');
                $table->text('progress');
                $table->text('risks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users', indexName: 'perf_sc_checkins_user_fk')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_calibration_adjustments')) {
            Schema::create('performance_calibration_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_id')->constrained('performance_scorecards', indexName: 'perf_calib_scorecard_fk')->cascadeOnDelete();
                $table->decimal('delta', 8, 4);
                $table->text('reason');
                $table->foreignId('adjusted_by')->nullable()->constrained('users', indexName: 'perf_calib_user_fk')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_calibration_adjustments');
        Schema::dropIfExists('performance_scorecard_checkins');
        Schema::dropIfExists('performance_scorecard_events');

        if (Schema::hasColumn('performance_scorecards', 'performance_form_id')) {
            Schema::table('performance_scorecards', function (Blueprint $table): void {
                $table->dropForeign('perf_scorecards_form_fk');
                $table->dropColumn(['performance_form_id', 'stage_due_at', 'reminded_at', 'escalated_at', 'closure_reason']);
            });
        }

        if (Schema::hasColumn('performance_kpi_templates', 'performance_form_template_id')) {
            Schema::table('performance_kpi_templates', function (Blueprint $table): void {
                $table->dropForeign('perf_kpi_tpl_form_tpl_fk');
                $table->dropColumn('performance_form_template_id');
            });
        }
    }
};
