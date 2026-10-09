<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ailə üzvünün əlilliyi: əlilliyi olan uşağa görə əlavə məzuniyyət (ƏM m.117.1) üçün lazımdır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personnel_kinships', 'is_disabled')) {
            Schema::table('personnel_kinships', function (Blueprint $table): void {
                $table->boolean('is_disabled')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personnel_kinships', 'is_disabled')) {
            Schema::table('personnel_kinships', function (Blueprint $table): void {
                $table->dropColumn('is_disabled');
            });
        }
    }
};
