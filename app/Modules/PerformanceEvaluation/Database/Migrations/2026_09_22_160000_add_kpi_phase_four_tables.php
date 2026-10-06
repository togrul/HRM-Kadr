<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI phase 4: probation rule, calculated (formula) KPIs, target change requests, cards
 * for an additional position (FTE share), forecasts and reminder marks, and the user's
 * notification channel settings plus HR-editable notification templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('performance_bonus_rules', 'pay_in_probation')) {
            Schema::table('performance_bonus_rules', function (Blueprint $table): void {
                $table->boolean('pay_in_probation')->default(false)->after('scale_to_fund');
            });
        }

        if (! Schema::hasColumn('performance_kpis', 'formula')) {
            Schema::table('performance_kpis', function (Blueprint $table): void {
                $table->text('formula')->nullable()->after('source_metric');
            });
        }

        if (! Schema::hasTable('performance_scorecard_change_requests')) {
            Schema::create('performance_scorecard_change_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('performance_scorecard_item_id')->constrained('performance_scorecard_items', indexName: 'perf_sc_change_item_fk')->cascadeOnDelete();
                $table->decimal('current_target', 14, 4)->nullable();
                $table->decimal('proposed_target', 14, 4);
                $table->text('reason');
                $table->string('status', 20)->default('pending');
                $table->foreignId('requested_by')->nullable()->constrained('users', indexName: 'perf_sc_change_requester_fk')->nullOnDelete();
                $table->foreignId('decided_by')->nullable()->constrained('users', indexName: 'perf_sc_change_decider_fk')->nullOnDelete();
                $table->timestamp('decided_at')->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at'], 'perf_sc_change_status_idx');
            });
        }

        if (! Schema::hasColumn('performance_scorecards', 'fte')) {
            Schema::table('performance_scorecards', function (Blueprint $table): void {
                $table->decimal('fte', 4, 2)->default(1)->after('position_id');
                $table->boolean('is_additional')->default(false)->after('fte');
                $table->timestamp('checkin_reminded_at')->nullable()->after('escalated_at');
                $table->timestamp('actuals_reminded_at')->nullable()->after('checkin_reminded_at');
                $table->unique(['performance_cycle_id', 'personnel_id', 'position_id', 'valid_from'], 'perf_scorecards_position_unique');
            });

            Schema::table('performance_scorecards', function (Blueprint $table): void {
                $table->dropUnique('perf_scorecards_unique');
            });
        }

        if (! Schema::hasColumn('performance_scorecard_items', 'forecast')) {
            Schema::table('performance_scorecard_items', function (Blueprint $table): void {
                $table->decimal('forecast', 14, 4)->nullable()->after('actual');
                $table->decimal('forecast_achievement', 8, 4)->nullable()->after('forecast');
                $table->timestamp('red_notified_at')->nullable()->after('forecast_achievement');
            });
        }

        if (! Schema::hasTable('performance_notification_settings')) {
            Schema::create('performance_notification_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique('perf_notif_settings_user_uq')->constrained('users', indexName: 'perf_notif_settings_user_fk')->cascadeOnDelete();
                $table->boolean('email')->default(true);
                $table->boolean('digest')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_notification_templates')) {
            Schema::create('performance_notification_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 60);
                $table->string('locale', 5);
                $table->string('subject');
                $table->text('body');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['key', 'locale'], 'perf_notif_templates_key_locale_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_notification_templates');
        Schema::dropIfExists('performance_notification_settings');
        Schema::dropIfExists('performance_scorecard_change_requests');
        Schema::table('performance_scorecard_items', fn (Blueprint $table) => $table->dropColumn(['forecast', 'forecast_achievement', 'red_notified_at']));
        Schema::table('performance_scorecards', function (Blueprint $table): void {
            $table->unique(['performance_cycle_id', 'personnel_id', 'valid_from'], 'perf_scorecards_unique');
        });
        Schema::table('performance_scorecards', function (Blueprint $table): void {
            $table->dropUnique('perf_scorecards_position_unique');
            $table->dropColumn(['fte', 'is_additional', 'checkin_reminded_at', 'actuals_reminded_at']);
        });
        Schema::table('performance_kpis', fn (Blueprint $table) => $table->dropColumn('formula'));
        Schema::table('performance_bonus_rules', fn (Blueprint $table) => $table->dropColumn('pay_in_probation'));
    }
};
