<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planlı hesabatlar: sahibin (user_id) icazə və strukturları ilə qurulan, gündəlik / həftəlik /
 * aylıq dövriyyə ilə yalnız sistem istifadəçilərinə (report_schedule_recipients) email ilə
 * göndərilən hesabat. Filtrlərdə dövr saxlanmır — `period` icra anına görə hesablanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('tab', 20);
            $table->json('filters');
            $table->string('period', 20)->default('current_month');
            $table->string('frequency', 10);
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->unsignedTinyInteger('month_day')->nullable();
            $table->string('send_time', 5)->default('08:00');
            $table->string('format', 10)->default('xlsx');
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });

        Schema::create('report_schedule_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_schedule_id')->constrained('report_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->unique(['report_schedule_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedule_recipients');
        Schema::dropIfExists('report_schedules');
    }
};
