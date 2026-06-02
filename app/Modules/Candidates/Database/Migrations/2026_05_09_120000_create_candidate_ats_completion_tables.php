<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_interviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_application_id')->constrained('candidate_applications')->cascadeOnDelete();
            $table->string('stage_key')->nullable()->index();
            $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['candidate_application_id', 'status']);
        });

        Schema::create('candidate_scorecards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_interview_id')->constrained('candidate_interviews')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('criterion');
            $table->unsignedTinyInteger('score')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['candidate_interview_id', 'criterion']);
        });

        Schema::create('candidate_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_application_id')->constrained('candidate_applications')->cascadeOnDelete();
            $table->decimal('salary_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('AZN');
            $table->date('start_date')->nullable()->index();
            $table->date('expires_at')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['candidate_application_id', 'status']);
        });

        Schema::create('candidate_talent_pool_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('candidate_application_id')->nullable()->constrained('candidate_applications')->nullOnDelete();
            $table->string('pool_name')->default('default')->index();
            $table->string('status')->default('active')->index();
            $table->date('valid_until')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['candidate_id', 'pool_name'], 'candidate_talent_pool_candidate_pool_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_talent_pool_entries');
        Schema::dropIfExists('candidate_offers');
        Schema::dropIfExists('candidate_scorecards');
        Schema::dropIfExists('candidate_interviews');
    }
};
