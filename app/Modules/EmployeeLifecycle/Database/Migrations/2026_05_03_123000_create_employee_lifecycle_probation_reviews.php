<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_lifecycle_probation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('employee_lifecycle_events')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->string('tabel_no')->nullable()->index();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hr_reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_due_at')->index();
            $table->string('status')->default('pending')->index();
            $table->string('decision')->nullable()->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('manager_note')->nullable();
            $table->text('hr_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'review_due_at'], 'el_probation_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_probation_reviews');
    }
};
