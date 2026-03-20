<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_record_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('original_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('kind');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('personnel_event_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('participation_role');
            $table->string('title');
            $table->string('topic')->nullable();
            $table->string('organizer_name')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('attendance_format')->default('offline');
            $table->string('strategic_level')->default('informational');
            $table->text('hr_value_reason')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('impact_summary')->nullable();
            $table->string('source_url')->nullable();
            $table->string('visibility')->default('internal');
            $table->foreignId('certificate_attachment_id')->nullable()->constrained('professional_record_attachments')->nullOnDelete();
            $table->foreignId('agenda_attachment_id')->nullable()->constrained('professional_record_attachments')->nullOnDelete();
            $table->string('verification_status')->default('pending');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_id', 'verification_status']);
            $table->index(['start_date', 'end_date']);
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });

        Schema::create('personnel_media_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->string('headline');
            $table->string('publisher_name');
            $table->string('publisher_type');
            $table->string('mention_type');
            $table->timestamp('published_at');
            $table->string('url')->nullable();
            $table->text('summary');
            $table->string('sentiment')->default('neutral');
            $table->string('language')->nullable();
            $table->foreignId('archive_attachment_id')->constrained('professional_record_attachments')->cascadeOnDelete();
            $table->foreignId('screenshot_attachment_id')->nullable()->constrained('professional_record_attachments')->nullOnDelete();
            $table->string('visibility')->default('internal');
            $table->string('verification_status')->default('pending');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_id', 'verification_status']);
            $table->index(['published_at']);
        });

        Schema::create('personnel_project_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->string('project_name');
            $table->string('project_code')->nullable();
            $table->string('project_type')->default('internal');
            $table->string('role_title');
            $table->text('responsibility_summary');
            $table->string('team_name')->nullable();
            $table->foreignId('sponsor_unit_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->text('partner_organizations')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_ongoing')->default(false);
            $table->text('outcome_summary')->nullable();
            $table->text('impact_summary')->nullable();
            $table->string('reference_url')->nullable();
            $table->foreignId('evidence_attachment_id')->nullable()->constrained('professional_record_attachments')->nullOnDelete();
            $table->string('verification_status')->default('pending');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_id', 'verification_status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_project_records');
        Schema::dropIfExists('personnel_media_mentions');
        Schema::dropIfExists('personnel_event_records');
        Schema::dropIfExists('professional_record_attachments');
    }
};
