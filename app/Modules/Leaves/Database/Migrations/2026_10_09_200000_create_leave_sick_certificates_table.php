<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sick-leave certificate register (Xəstəlik vərəqələri): the 1:1 document details of a sick
 * leave. Dates and the day count stay on the leave itself (the puantaj and the presence
 * resolver read them there); this table holds series/number, institution, doctor, the
 * encrypted diagnosis, the open/closed/cancelled status and the continuation chain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_sick_certificates')) {
            return;
        }

        Schema::create('leave_sick_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_id')->unique()->constrained('leaves')->cascadeOnDelete();
            $table->string('series', 20)->default('');
            $table->string('number', 40);
            $table->string('medical_institution')->nullable();
            $table->string('doctor_name')->nullable();
            // Encrypted cast; never listed, exported, serialised or written to the activity log.
            $table->text('diagnosis')->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('continuation_of_id')->nullable()->constrained('leave_sick_certificates')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['series', 'number'], 'leave_sick_certificates_series_number_unique');
            $table->index(['status', 'created_at'], 'leave_sick_certificates_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_sick_certificates');
    }
};
