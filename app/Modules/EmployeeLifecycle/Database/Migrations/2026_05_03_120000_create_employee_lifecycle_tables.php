<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels')->nullOnDelete();
            $table->string('tabel_no')->nullable()->index();
            $table->string('type')->index();
            $table->string('status')->default('planned')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('effective_date')->nullable()->index();
            $table->date('deadline_at')->nullable()->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'el_events_source_idx');
            $table->index(['type', 'status', 'deadline_at'], 'el_events_type_status_deadline_idx');
        });

        Schema::create('employee_lifecycle_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('employee_lifecycle_events')->cascadeOnDelete();
            $table->string('title');
            $table->string('owner_type')->default('hr')->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_at')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'due_at'], 'el_tasks_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_tasks');
        Schema::dropIfExists('employee_lifecycle_events');
    }
};
