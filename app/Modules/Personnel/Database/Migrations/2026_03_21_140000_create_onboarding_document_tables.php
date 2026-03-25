<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_document_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('document_type')->default('other');
            $table->string('version')->default('1.0');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('requires_acknowledgement')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('onboarding_document_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('onboarding_document_templates')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('assigned');
            $table->timestamps();
        });

        Schema::create('onboarding_document_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained('onboarding_document_assignments')->cascadeOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_ip', 64)->nullable();
            $table->text('acknowledged_user_agent')->nullable();
            $table->timestamps();

            $table->unique('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_document_receipts');
        Schema::dropIfExists('onboarding_document_assignments');
        Schema::dropIfExists('onboarding_document_templates');
    }
};
