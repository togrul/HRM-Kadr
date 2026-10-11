<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İşçi münasibətləri (şikayət və intizam araşdırması) modulunun cədvəlləri: iş növləri
 * soraqçası, işlər, şahidlər, araşdırıcı təyinatları, sübutlar, tarixçə və apellyasiyalar.
 *
 * Əmrə bağ (`order_log_id`) xarici açar deyil: əmr Orders modulunun daxili qeydidir və
 * yalnız OrderDrafter kontraktı ilə yaradılır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_relation_case_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->unsignedSmallInteger('investigation_sla_days')->nullable();
            $table->boolean('is_disciplinary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('employee_relation_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 30)->nullable()->unique();
            $table->foreignId('case_type_id')->constrained('employee_relation_case_types')->restrictOnDelete();
            $table->string('status', 30)->default('open');
            $table->string('channel', 20)->default('hr');
            $table->string('title', 255);
            $table->text('description');
            $table->date('occurred_at')->nullable();
            $table->date('discovered_at')->nullable();
            $table->boolean('discovered_by_audit')->default(false);
            $table->foreignId('subject_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->string('subject_description', 255)->nullable();
            $table->foreignId('complainant_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->dateTime('stage_entered_at')->nullable();
            $table->dateTime('stage_due_at')->nullable();
            $table->unsignedInteger('sla_breach_count')->default(0);
            $table->dateTime('last_reminder_at')->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->string('decision_outcome', 30)->nullable();
            $table->text('decision_summary')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('order_log_id')->nullable();
            $table->dateTime('decision_notified_at')->nullable();
            $table->date('appeal_deadline')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'stage_due_at'], 'er_cases_status_due_idx');
            $table->index(['structure_id', 'status'], 'er_cases_structure_status_idx');
            $table->index('subject_personnel_id', 'er_cases_subject_idx');
            $table->index('reporter_user_id', 'er_cases_reporter_idx');
        });

        Schema::create('employee_relation_case_witnesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('employee_relation_cases')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->string('external_name', 150)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['case_id', 'personnel_id'], 'er_witnesses_case_personnel_unique');
        });

        Schema::create('employee_relation_case_investigators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('employee_relation_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['case_id', 'user_id'], 'er_investigators_case_user_unique');
            $table->index('user_id', 'er_investigators_user_idx');
        });

        Schema::create('employee_relation_case_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('employee_relation_cases')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_mime', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('provided_by_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_relation_case_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('employee_relation_cases')->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['case_id', 'id'], 'er_events_case_idx');
        });

        Schema::create('employee_relation_case_appeals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('employee_relation_cases')->cascadeOnDelete();
            $table->foreignId('appellant_personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->foreignId('appellant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('appellant_role', 20);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->string('review_outcome', 20)->nullable();
            $table->text('review_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'status'], 'er_appeals_case_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_relation_case_appeals');
        Schema::dropIfExists('employee_relation_case_events');
        Schema::dropIfExists('employee_relation_case_evidence');
        Schema::dropIfExists('employee_relation_case_investigators');
        Schema::dropIfExists('employee_relation_case_witnesses');
        Schema::dropIfExists('employee_relation_cases');
        Schema::dropIfExists('employee_relation_case_types');
    }
};
