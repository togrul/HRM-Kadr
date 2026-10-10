<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A disciplinary sanction holds until its expiry date; the daily
 * `personnel:lift-expired-sanctions` run stamps lifted_at once that date has passed, so
 * the personnel file can tell a live sanction from one that no longer counts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('personnel_punishments', 'lifted_at')) {
            return;
        }

        Schema::table('personnel_punishments', function (Blueprint $table): void {
            $table->date('lifted_at')->nullable();
            $table->index(['expired_date', 'lifted_at'], 'personnel_punishments_expiry_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('personnel_punishments', 'lifted_at')) {
            return;
        }

        Schema::table('personnel_punishments', function (Blueprint $table): void {
            $table->dropIndex('personnel_punishments_expiry_idx');
            $table->dropColumn('lifted_at');
        });
    }
};
