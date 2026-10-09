<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a trip is domestic or abroad (ölkədaxili / xarici — per-diem norms differ) and who
 * pays for it (maliyyələşmə mənbəyi). Filled by the business-trip order on approval; trips
 * recorded before stay null (unknown) rather than being guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_business_trips', function (Blueprint $table): void {
            if (! Schema::hasColumn('personnel_business_trips', 'trip_type')) {
                $table->string('trip_type', 16)->nullable()->index();
            }

            if (! Schema::hasColumn('personnel_business_trips', 'funding_source')) {
                $table->string('funding_source')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personnel_business_trips', function (Blueprint $table): void {
            $table->dropIndex(['trip_type']);
            $table->dropColumn(['trip_type', 'funding_source']);
        });
    }
};
