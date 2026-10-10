<?php

use App\Support\VtiskCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vəzifənin VTİSK kateqoriyası (ƏM m.114.3 "b": rəhbər və mütəxəssis — 30 gün).
 * Mövcud vəzifələr sıralama səviyyəsindən təxmin edilir; köməkçi heyət boş qalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'vtisk_category')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->string('vtisk_category', 20)->nullable()->after('level');
            });
        }

        DB::table('positions')->whereNull('vtisk_category')->get(['id', 'level'])
            ->each(function (object $position): void {
                $category = VtiskCategory::guessFromLevel($position->level !== null ? (int) $position->level : null);

                if ($category !== null) {
                    DB::table('positions')->where('id', $position->id)->update(['vtisk_category' => $category]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('positions', 'vtisk_category')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->dropColumn('vtisk_category');
            });
        }
    }
};
