<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_test_banks')) {
            Schema::create('performance_test_banks', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->decimal('pass_score', 5, 2)->default(60);
                $table->unsignedInteger('duration_minutes')->default(30);
                $table->unsignedSmallInteger('max_attempts')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_test_questions')) {
            Schema::create('performance_test_questions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_test_bank_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->string('question_type')->default('multiple_choice');
                $table->text('prompt');
                $table->text('description')->nullable();
                $table->decimal('max_score', 6, 2)->default(100);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->foreign('performance_test_bank_id', 'performance_test_questions_bank_fk')
                    ->references('id')
                    ->on('performance_test_banks')
                    ->cascadeOnDelete();
                $table->foreign('training_competency_id', 'performance_test_questions_competency_fk')
                    ->references('id')
                    ->on('training_competencies')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_test_question_options')) {
            Schema::create('performance_test_question_options', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_test_question_id');
                $table->string('label');
                $table->boolean('is_correct')->default(false);
                $table->decimal('score_value', 6, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->foreign('performance_test_question_id', 'performance_test_options_question_fk')
                    ->references('id')
                    ->on('performance_test_questions')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('performance_test_sessions')) {
            Schema::create('performance_test_sessions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_cycle_id')->nullable();
                $table->unsignedBigInteger('performance_test_bank_id');
                $table->unsignedBigInteger('personnel_id');
                $table->unsignedBigInteger('reviewer_id')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('available_until')->nullable();
                $table->decimal('pass_score', 5, 2)->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->unsignedSmallInteger('max_attempts')->nullable();
                $table->string('status')->default('assigned');
                $table->timestamps();
                $table->foreign('performance_cycle_id', 'performance_test_sessions_cycle_fk')
                    ->references('id')
                    ->on('performance_cycles')
                    ->nullOnDelete();
                $table->foreign('performance_test_bank_id', 'performance_test_sessions_bank_fk')
                    ->references('id')
                    ->on('performance_test_banks')
                    ->cascadeOnDelete();
                $table->foreign('personnel_id', 'performance_test_sessions_personnel_fk')
                    ->references('id')
                    ->on('personnels')
                    ->cascadeOnDelete();
                $table->foreign('reviewer_id', 'performance_test_sessions_reviewer_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->foreign('assigned_by', 'performance_test_sessions_assigned_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('performance_test_attempts')) {
            Schema::create('performance_test_attempts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_test_session_id');
                $table->unsignedSmallInteger('attempt_no')->default(1);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->decimal('score', 8, 2)->nullable();
                $table->decimal('percentage', 6, 2)->nullable();
                $table->boolean('passed')->nullable();
                $table->string('status')->default('draft');
                $table->timestamp('auto_scored_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('weak_area_synced_at')->nullable();
                $table->timestamps();
                $table->foreign('performance_test_session_id', 'performance_test_attempts_session_fk')
                    ->references('id')
                    ->on('performance_test_sessions')
                    ->cascadeOnDelete();
                $table->foreign('reviewed_by', 'performance_test_attempts_reviewed_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->unique(['performance_test_session_id', 'attempt_no'], 'performance_test_attempts_unique');
            });
        }

        if (! Schema::hasTable('performance_test_attempt_answers')) {
            Schema::create('performance_test_attempt_answers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_test_attempt_id');
                $table->unsignedBigInteger('performance_test_question_id');
                $table->unsignedBigInteger('selected_option_id')->nullable();
                $table->text('answer_text')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->decimal('auto_score', 8, 2)->nullable();
                $table->decimal('review_score', 8, 2)->nullable();
                $table->decimal('final_score', 8, 2)->nullable();
                $table->string('review_status')->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamps();
                $table->foreign('performance_test_attempt_id', 'performance_test_answers_attempt_fk')
                    ->references('id')
                    ->on('performance_test_attempts')
                    ->cascadeOnDelete();
                $table->foreign('performance_test_question_id', 'performance_test_answers_question_fk')
                    ->references('id')
                    ->on('performance_test_questions')
                    ->cascadeOnDelete();
                $table->foreign('selected_option_id', 'performance_test_answers_option_fk')
                    ->references('id')
                    ->on('performance_test_question_options')
                    ->nullOnDelete();
                $table->foreign('reviewed_by', 'performance_test_answers_reviewed_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->unique(
                    ['performance_test_attempt_id', 'performance_test_question_id'],
                    'performance_test_attempt_answers_unique'
                );
            });
        }

        if (! Schema::hasTable('performance_test_training_need_links')) {
            Schema::create('performance_test_training_need_links', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('performance_test_attempt_id');
                $table->unsignedBigInteger('training_need_item_id');
                $table->unsignedBigInteger('training_competency_id')->nullable();
                $table->string('source')->default('test_gap');
                $table->timestamps();
                $table->foreign('performance_test_attempt_id', 'performance_test_need_links_attempt_fk')
                    ->references('id')
                    ->on('performance_test_attempts')
                    ->cascadeOnDelete();
                $table->foreign('training_need_item_id', 'performance_test_need_links_training_need_fk')
                    ->references('id')
                    ->on('training_need_items')
                    ->cascadeOnDelete();
                $table->foreign('training_competency_id', 'performance_test_need_links_competency_fk')
                    ->references('id')
                    ->on('training_competencies')
                    ->nullOnDelete();
                $table->unique(
                    ['performance_test_attempt_id', 'training_competency_id'],
                    'performance_test_need_links_attempt_competency_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_test_training_need_links');
        Schema::dropIfExists('performance_test_attempt_answers');
        Schema::dropIfExists('performance_test_attempts');
        Schema::dropIfExists('performance_test_sessions');
        Schema::dropIfExists('performance_test_question_options');
        Schema::dropIfExists('performance_test_questions');
        Schema::dropIfExists('performance_test_banks');
    }
};
