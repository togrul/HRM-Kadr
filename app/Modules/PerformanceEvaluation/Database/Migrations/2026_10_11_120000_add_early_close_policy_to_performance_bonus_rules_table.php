<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vaxtından əvvəl bağlanan (işdən çıxma) kartın bonus siyasəti: `prorata` (işlənmiş günlərə
 * mütənasib, standart), `full` (dövr sonuna qədər işləmiş kimi) və ya `none` (bonus yoxdur).
 * Mövcud qaydalar əvvəlki davranışı (pro-rata) saxlayır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_bonus_rules') || Schema::hasColumn('performance_bonus_rules', 'early_close_policy')) {
            return;
        }

        Schema::table('performance_bonus_rules', function (Blueprint $table): void {
            $table->string('early_close_policy', 16)->default('prorata')->after('pay_in_probation');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('performance_bonus_rules', 'early_close_policy')) {
            Schema::table('performance_bonus_rules', fn (Blueprint $table) => $table->dropColumn('early_close_policy'));
        }
    }
};
