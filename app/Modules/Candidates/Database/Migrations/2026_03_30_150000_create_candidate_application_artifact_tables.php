<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_application_assessments')) {
            Schema::create('candidate_application_assessments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('candidate_application_id');
                $table->string('stage_key');
                $table->string('assessment_key');
                $table->string('status')->default('pending');
                $table->text('note')->nullable();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->unique(['candidate_application_id', 'stage_key', 'assessment_key'], 'candidate_app_assessments_unique');
                $table->index(['candidate_application_id', 'stage_key'], 'candidate_app_assessments_stage_idx');
                $table->foreign('candidate_application_id', 'cand_app_assess_app_fk')->references('id')->on('candidate_applications')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('candidate_application_document_checks')) {
            Schema::create('candidate_application_document_checks', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('candidate_application_id');
                $table->string('stage_key');
                $table->string('document_key');
                $table->boolean('is_provided')->default(false);
                $table->text('note')->nullable();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->unique(['candidate_application_id', 'stage_key', 'document_key'], 'candidate_app_documents_unique');
                $table->index(['candidate_application_id', 'stage_key'], 'candidate_app_documents_stage_idx');
                $table->foreign('candidate_application_id', 'cand_app_docs_app_fk')->references('id')->on('candidate_applications')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_application_document_checks');
        Schema::dropIfExists('candidate_application_assessments');
    }
};
