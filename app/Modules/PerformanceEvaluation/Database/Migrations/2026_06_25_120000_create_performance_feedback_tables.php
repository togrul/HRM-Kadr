<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_feedback_requests')) {
            Schema::create('performance_feedback_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_cycle_id');
                $table->unsignedBigInteger('performance_form_template_id');
                $table->unsignedBigInteger('subject_personnel_id');
                $table->boolean('is_anonymous')->default(true);
                $table->date('due_date')->nullable();
                // collecting -> calibrating -> closed
                $table->string('status')->default('collecting');
                $table->decimal('final_score', 6, 2)->nullable();
                // Per-item calibrated scores keyed by template item id.
                $table->json('calibrated_scores')->nullable();
                // pending -> approved
                $table->string('calibration_status')->default('pending');
                $table->unsignedBigInteger('calibrated_by')->nullable();
                $table->text('calibration_note')->nullable();
                $table->timestamps();

                $table->foreign('performance_cycle_id', 'pf_feedback_requests_cycle_fk')
                    ->references('id')->on('performance_cycles')->cascadeOnDelete();
                $table->foreign('performance_form_template_id', 'pf_feedback_requests_template_fk')
                    ->references('id')->on('performance_form_templates')->cascadeOnDelete();
                $table->foreign('subject_personnel_id', 'pf_feedback_requests_subject_fk')
                    ->references('id')->on('personnels')->cascadeOnDelete();
                $table->foreign('calibrated_by', 'pf_feedback_requests_calibrator_fk')
                    ->references('id')->on('users')->nullOnDelete();
                $table->unique(
                    ['performance_cycle_id', 'performance_form_template_id', 'subject_personnel_id'],
                    'pf_feedback_requests_unique'
                );
            });
        }

        if (! Schema::hasTable('performance_feedback_raters')) {
            Schema::create('performance_feedback_raters', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_feedback_request_id');
                $table->unsignedBigInteger('rater_personnel_id')->nullable();
                $table->unsignedBigInteger('rater_user_id')->nullable();
                // manager | peer | subordinate | self
                $table->string('rater_type');
                // pending -> submitted
                $table->string('status')->default('pending');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->foreign('performance_feedback_request_id', 'pf_feedback_raters_request_fk')
                    ->references('id')->on('performance_feedback_requests')->cascadeOnDelete();
                $table->foreign('rater_personnel_id', 'pf_feedback_raters_personnel_fk')
                    ->references('id')->on('personnels')->nullOnDelete();
                $table->foreign('rater_user_id', 'pf_feedback_raters_user_fk')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_feedback_scores')) {
            Schema::create('performance_feedback_scores', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_feedback_rater_id');
                $table->unsignedBigInteger('performance_form_template_item_id');
                $table->decimal('score', 5, 2);
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->foreign('performance_feedback_rater_id', 'pf_feedback_scores_rater_fk')
                    ->references('id')->on('performance_feedback_raters')->cascadeOnDelete();
                $table->foreign('performance_form_template_item_id', 'pf_feedback_scores_item_fk')
                    ->references('id')->on('performance_form_template_items')->cascadeOnDelete();
                $table->unique(
                    ['performance_feedback_rater_id', 'performance_form_template_item_id'],
                    'pf_feedback_scores_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_feedback_scores');
        Schema::dropIfExists('performance_feedback_raters');
        Schema::dropIfExists('performance_feedback_requests');
    }
};
