<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_application_stage_profiles')) {
            Schema::create('candidate_application_stage_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('candidate_application_id');
                $table->string('stage_key');
                $table->string('profile_pack');
                $table->json('payload')->nullable();
                $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();

                $table->unique(['candidate_application_id', 'stage_key'], 'cand_app_stage_profile_unique');
                $table->index(['candidate_application_id', 'stage_key'], 'cand_app_stage_profile_stage_idx');
                $table->foreign('candidate_application_id', 'cand_app_stage_profile_fk')->references('id')->on('candidate_applications')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('candidate_documents')) {
            Schema::table('candidate_documents', function (Blueprint $table): void {
                if (! Schema::hasColumn('candidate_documents', 'candidate_application_id')) {
                    $table->unsignedBigInteger('candidate_application_id')->nullable()->after('candidate_id');
                    $table->foreign('candidate_application_id', 'cand_docs_application_fk')->references('id')->on('candidate_applications')->nullOnDelete();
                }

                if (! Schema::hasColumn('candidate_documents', 'stage_key')) {
                    $table->string('stage_key')->nullable()->after('category');
                }

                if (! Schema::hasColumn('candidate_documents', 'document_key')) {
                    $table->string('document_key')->nullable()->after('stage_key');
                }
            });

            Schema::table('candidate_documents', function (Blueprint $table): void {
                $hasApplication = Schema::hasColumn('candidate_documents', 'candidate_application_id');
                $hasDocumentKey = Schema::hasColumn('candidate_documents', 'document_key');

                if ($hasApplication && $hasDocumentKey) {
                    $table->index(['candidate_application_id', 'document_key'], 'cand_docs_application_doc_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_application_stage_profiles');

        if (Schema::hasTable('candidate_documents')) {
            Schema::table('candidate_documents', function (Blueprint $table): void {
                if (Schema::hasColumn('candidate_documents', 'candidate_application_id')) {
                    $table->dropForeign('cand_docs_application_fk');
                    $table->dropIndex('cand_docs_application_doc_idx');
                    $table->dropColumn('candidate_application_id');
                }

                if (Schema::hasColumn('candidate_documents', 'stage_key')) {
                    $table->dropColumn('stage_key');
                }

                if (Schema::hasColumn('candidate_documents', 'document_key')) {
                    $table->dropColumn('document_key');
                }
            });
        }
    }
};
