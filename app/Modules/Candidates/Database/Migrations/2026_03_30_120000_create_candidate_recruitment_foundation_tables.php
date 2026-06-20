<?php

use App\Models\Candidate;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_sources')) {
            Schema::create('candidate_sources', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('channel')->default('internal');
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->foreignIdFor(User::class, 'creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_rejection_reasons')) {
            Schema::create('candidate_rejection_reasons', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('profile_pack')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_requisitions')) {
            Schema::create('job_requisitions', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->foreignIdFor(Structure::class)->nullable()->constrained()->nullOnDelete();
                $table->integer('position_id')->nullable();
                $table->string('profile_pack')->default('military');
                $table->string('employment_type')->default('full_time');
                $table->string('hiring_reason')->nullable();
                $table->unsignedInteger('headcount')->default(1);
                $table->string('status')->default('draft');
                $table->date('opens_at')->nullable();
                $table->date('closes_at')->nullable();
                $table->foreignIdFor(User::class, 'requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignIdFor(User::class, 'owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['profile_pack', 'status']);
                $table->index(['structure_id', 'position_id']);
                $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('job_openings')) {
            Schema::create('job_openings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('job_requisition_id')->nullable()->constrained('job_requisitions')->nullOnDelete();
                $table->string('title');
                $table->foreignIdFor(Structure::class)->nullable()->constrained()->nullOnDelete();
                $table->integer('position_id')->nullable();
                $table->string('profile_pack')->default('military');
                $table->string('opening_type')->default('standard');
                $table->unsignedInteger('headcount')->default(1);
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->date('closes_at')->nullable();
                $table->foreignIdFor(User::class, 'owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['profile_pack', 'status']);
                $table->index(['structure_id', 'position_id']);
                $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('candidate_applications')) {
            Schema::create('candidate_applications', function (Blueprint $table): void {
                $table->id();
                $table->foreignIdFor(Candidate::class)->constrained()->cascadeOnDelete();
                $table->foreignId('job_opening_id')->constrained('job_openings')->cascadeOnDelete();
                $table->foreignId('candidate_source_id')->nullable()->constrained('candidate_sources')->nullOnDelete();
                $table->foreignId('rejection_reason_id')->nullable()->constrained('candidate_rejection_reasons')->nullOnDelete();
                $table->string('current_stage')->default('applied');
                $table->string('status')->default('active');
                $table->foreignIdFor(User::class, 'assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('final_decision')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamp('moved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('withdrawn_at')->nullable();
                $table->timestamp('hired_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['candidate_id', 'job_opening_id']);
                $table->index(['current_stage', 'status']);
            });
        }

        if (! Schema::hasTable('candidate_stage_events')) {
            Schema::create('candidate_stage_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('candidate_application_id')->constrained('candidate_applications')->cascadeOnDelete();
                $table->string('stage_key');
                $table->string('action')->nullable();
                $table->string('decision')->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['candidate_application_id', 'stage_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_stage_events');
        Schema::dropIfExists('candidate_applications');
        Schema::dropIfExists('job_openings');
        Schema::dropIfExists('job_requisitions');
        Schema::dropIfExists('candidate_rejection_reasons');
        Schema::dropIfExists('candidate_sources');
    }
};
