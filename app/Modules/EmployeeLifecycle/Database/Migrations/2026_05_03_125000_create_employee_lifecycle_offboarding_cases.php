<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_lifecycle_offboarding_cases')) {
            Schema::create('employee_lifecycle_offboarding_cases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('employee_lifecycle_events')->cascadeOnDelete();
                $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
                $table->string('tabel_no')->nullable()->index();
                $table->date('last_working_date')->index();
                $table->string('status')->default('open')->index();
                $table->string('reason')->nullable();
                $table->text('exit_interview_summary')->nullable();
                $table->timestamp('exit_interview_completed_at')->nullable();
                $table->timestamp('checklist_completed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'last_working_date'], 'el_offboarding_status_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_offboarding_cases');
    }
};
