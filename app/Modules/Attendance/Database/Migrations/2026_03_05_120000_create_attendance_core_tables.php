<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 50)->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('timezone', 64)->default('Asia/Baku');
            $table->unsignedBigInteger('default_shift_id')->nullable();
            $table->unsignedSmallInteger('late_grace_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_grace_minutes')->default(0);
            $table->string('rounding_policy', 32)->default('none');
            $table->unsignedSmallInteger('rounding_step_minutes')->default(5);
            $table->string('overtime_policy', 32)->default('by_approval');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id'], 'attendance_settings_scope_unique');
            $table->index('is_active');
        });

        Schema::create('attendance_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->boolean('is_night_shift')->default(false);
            $table->unsignedSmallInteger('in_flex_before_minutes')->default(0);
            $table->unsignedSmallInteger('in_flex_after_minutes')->default(0);
            $table->unsignedSmallInteger('out_flex_before_minutes')->default(0);
            $table->unsignedSmallInteger('out_flex_after_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'deleted_at']);
        });

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->foreign('default_shift_id')
                ->references('id')
                ->on('attendance_shifts')
                ->nullOnDelete();
        });

        Schema::create('attendance_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreignId('shift_id')->constrained('attendance_shifts')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('assignment_source', 32)->default('manual');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['tabel_no', 'effective_from', 'effective_to'], 'attendance_shift_assignments_tabel_range_idx');
            $table->index(['shift_id', 'is_active'], 'attendance_shift_assignments_shift_active_idx');
        });

        Schema::create('attendance_calendars', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('day_type', 24)->default('workday');
            $table->string('name')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->string('scope_type', 50)->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['date', 'scope_type', 'scope_id'], 'attendance_calendars_scope_date_unique');
            $table->index(['day_type', 'date'], 'attendance_calendars_day_type_date_idx');
        });

        Schema::create('attendance_raw_punches', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->dateTime('punched_at');
            $table->string('direction', 8)->nullable();
            $table->string('source', 24)->default('manual');
            $table->string('device_ref')->nullable();
            $table->string('external_id')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['tabel_no', 'punched_at'], 'attendance_raw_punches_tabel_time_idx');
            $table->index(['is_processed', 'punched_at'], 'attendance_raw_punches_processed_idx');
            $table->unique(['source', 'external_id'], 'attendance_raw_punches_source_external_unique');
            $table->unique(['source', 'payload_hash'], 'attendance_raw_punches_source_hash_unique');
        });

        Schema::create('attendance_manual_entries', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->date('date');
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->string('absence_code', 32)->nullable();
            $table->text('reason')->nullable();
            $table->string('approval_status', 24)->default('pending');
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['tabel_no', 'date'], 'attendance_manual_entries_tabel_date_idx');
            $table->index(['approval_status', 'date'], 'attendance_manual_entries_status_date_idx');
        });

        Schema::create('attendance_daily_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->date('date');
            $table->foreignId('shift_id')->nullable()->constrained('attendance_shifts')->nullOnDelete();
            $table->unsignedSmallInteger('scheduled_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->string('attendance_status', 32)->default('present');
            $table->string('absence_code', 32)->nullable();
            $table->string('source_summary', 24)->default('system');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->unique(['tabel_no', 'date'], 'attendance_daily_ledgers_tabel_date_unique');
            $table->index(['date', 'attendance_status'], 'attendance_daily_ledgers_date_status_idx');
            $table->index(['tabel_no', 'date', 'is_locked'], 'attendance_daily_ledgers_tabel_date_lock_idx');
        });

        Schema::create('attendance_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->date('date');
            $table->string('type', 32);
            $table->string('status', 24)->default('open');
            $table->text('message')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'date'], 'attendance_exceptions_status_date_idx');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['tabel_no', 'date'], 'attendance_exceptions_tabel_date_idx');
        });

        Schema::create('attendance_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->date('date');
            $table->unsignedSmallInteger('requested_minutes');
            $table->unsignedSmallInteger('approved_minutes')->default(0);
            $table->string('status', 24)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'date'], 'attendance_overtime_requests_status_date_idx');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['tabel_no', 'date'], 'attendance_overtime_requests_tabel_date_idx');
        });

        Schema::create('attendance_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('total_scheduled_minutes')->default(0);
            $table->unsignedInteger('total_worked_minutes')->default(0);
            $table->unsignedInteger('total_overtime_minutes')->default(0);
            $table->unsignedInteger('total_absence_minutes')->default(0);
            $table->unsignedSmallInteger('total_workdays')->default(0);
            $table->unsignedSmallInteger('total_present_days')->default(0);
            $table->unsignedSmallInteger('total_absence_days')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->dateTime('calculated_at')->nullable();
            $table->timestamps();

            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->unique(['tabel_no', 'year', 'month'], 'attendance_monthly_summaries_tabel_period_unique');
            $table->index(['year', 'month', 'is_locked'], 'attendance_monthly_summaries_period_lock_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_monthly_summaries');
        Schema::dropIfExists('attendance_overtime_requests');
        Schema::dropIfExists('attendance_exceptions');
        Schema::dropIfExists('attendance_daily_ledgers');
        Schema::dropIfExists('attendance_manual_entries');
        Schema::dropIfExists('attendance_raw_punches');
        Schema::dropIfExists('attendance_calendars');
        Schema::dropIfExists('attendance_shift_assignments');

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropForeign(['default_shift_id']);
        });

        Schema::dropIfExists('attendance_shifts');
        Schema::dropIfExists('attendance_settings');
    }
};
