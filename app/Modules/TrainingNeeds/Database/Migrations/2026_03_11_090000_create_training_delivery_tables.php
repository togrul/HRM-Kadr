<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_sessions')) {
            Schema::create('training_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_annual_plan_id')->nullable()->constrained('training_annual_plans')->nullOnDelete();
                $table->foreignId('training_program_id')->nullable()->constrained('training_programs')->nullOnDelete();
                $table->string('title');
                $table->timestamp('scheduled_start_at')->nullable();
                $table->timestamp('scheduled_end_at')->nullable();
                $table->string('location')->nullable();
                $table->string('trainer_name')->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->decimal('planned_budget', 12, 2)->nullable();
                $table->string('status')->default('scheduled');
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_start_at'], 'training_sessions_status_schedule_idx');
            });
        }

        if (! Schema::hasTable('training_session_participants')) {
            Schema::create('training_session_participants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->foreignId('training_need_item_id')->nullable()->constrained('training_need_items')->nullOnDelete();
                $table->string('attendance_status')->default('planned');
                $table->timestamp('attended_at')->nullable();
                $table->timestamps();
                $table->unique(['training_session_id', 'personnel_id'], 'training_session_participant_unique');
            });
        }

        if (! Schema::hasTable('training_delivery_records')) {
            Schema::create('training_delivery_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->foreignId('training_program_id')->nullable()->constrained('training_programs')->nullOnDelete();
                $table->foreignId('training_competency_id')->nullable()->constrained('training_competencies')->nullOnDelete();
                $table->foreignId('training_need_item_id')->nullable()->constrained('training_need_items')->nullOnDelete();
                $table->decimal('attended_hours', 8, 2)->nullable();
                $table->string('result_status')->default('completed');
                $table->timestamp('completed_at');
                $table->timestamps();
                $table->unique(['training_session_id', 'personnel_id'], 'training_delivery_record_unique');
            });
        }

        if (! Schema::hasTable('training_feedback_forms')) {
            Schema::create('training_feedback_forms', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
                $table->string('title');
                $table->string('status')->default('open');
                $table->json('questions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('training_feedback_responses')) {
            Schema::create('training_feedback_responses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('training_feedback_form_id')->constrained('training_feedback_forms')->cascadeOnDelete();
                $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
                $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
                $table->unsignedTinyInteger('overall_score')->nullable();
                $table->text('comments')->nullable();
                $table->json('answers')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['training_feedback_form_id', 'personnel_id'], 'training_feedback_response_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_feedback_responses');
        Schema::dropIfExists('training_feedback_forms');
        Schema::dropIfExists('training_delivery_records');
        Schema::dropIfExists('training_session_participants');
        Schema::dropIfExists('training_sessions');
    }
};
