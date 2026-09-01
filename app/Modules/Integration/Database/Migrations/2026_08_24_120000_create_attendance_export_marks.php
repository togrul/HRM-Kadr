<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records that a locked month has been handed to the finance system.
 *
 * ## The failure this prevents
 *
 * `unlockMonth()` reopens a month for editing. On its own that is reasonable —
 * corrections happen. But once the finance system has consumed the month, it has
 * computed payroll from it, posted journal entries and quite possibly closed the
 * accounting period. Editing it here afterwards changes nothing over there; the
 * two simply stop agreeing, and nobody is told.
 *
 * So a month that was exported cannot be silently reopened. It can still be
 * reopened deliberately — corrections are real — but that becomes an explicit
 * act with a recorded reason, not a side effect of a button.
 *
 * ## Why a mark and not a question
 *
 * Asking the finance system "is this period closed?" would make the answer
 * depend on the network. Behind a firewall, or during an outage, the safe answer
 * would have to be "assume closed", which means the check has to work offline
 * anyway. A local mark works offline by construction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_export_marks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('consumer', 40)->default('finance');
            $table->timestamp('exported_at');
            $table->timestamps();

            $table->unique(['year', 'month', 'consumer'], 'attendance_export_marks_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_export_marks');
    }
};
