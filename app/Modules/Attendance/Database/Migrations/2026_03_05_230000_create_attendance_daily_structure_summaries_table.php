<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_daily_structure_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('structure_id')->nullable();
            $table->unsignedInteger('ledger_rows')->default(0);
            $table->unsignedInteger('scheduled_days')->default(0);
            $table->unsignedInteger('present_days')->default(0);
            $table->unsignedInteger('absence_days')->default(0);
            $table->unsignedInteger('compliant_days')->default(0);
            $table->unsignedInteger('scheduled_minutes_sum')->default(0);
            $table->unsignedInteger('worked_minutes_sum')->default(0);
            $table->unsignedInteger('overtime_minutes_sum')->default(0);
            $table->unsignedInteger('late_minutes_sum')->default(0);
            $table->unsignedInteger('early_leave_minutes_sum')->default(0);
            $table->timestamps();

            $table->unique(['date', 'structure_id'], 'attendance_daily_structure_summaries_unique');
            $table->index(['date'], 'attendance_daily_structure_summaries_date_idx');
            $table->index(['structure_id', 'date'], 'attendance_daily_structure_summaries_structure_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_daily_structure_summaries');
    }
};
