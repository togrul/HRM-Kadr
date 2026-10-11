<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staja görə əlavə məzuniyyət pillələrinin (ƏM m.116.1) qurum tərəfindən redaktəsi:
 *   - `min_inclusive` — aşağı hədd daxildirmi: «X il və daha çox» (true) və ya «X ildən çox»
 *     (false). Mövcud davranış saxlanılır: bağlı aralıqlarda hədd daxil idi, açıq aralıqda
 *     («15 ildən çox») xaric idi;
 *   - `statutory_key` — qanuni pillənin sabit açarı (seniority_5 / seniority_10 / seniority_15):
 *     qurum həddi dəyişsə də sətrin hansı qanuni pillədən gəldiyi və «qanuni dəyərə qaytar»
 *     üçün standart dəyəri bilinir.
 * Pillə dəyişiklikləri tarixlə versiyalanır (`valid_from` / `valid_to` — iş ilinin başlanğıcına
 * görə), ona görə artıq başlamış iş illərinin hüququ dəyişmir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vacation_norms')) {
            return;
        }

        Schema::table('vacation_norms', function (Blueprint $table): void {
            if (! Schema::hasColumn('vacation_norms', 'min_inclusive')) {
                $table->boolean('min_inclusive')->default(true)->after('max_value');
            }

            if (! Schema::hasColumn('vacation_norms', 'statutory_key')) {
                $table->string('statutory_key', 40)->nullable()->after('is_statutory');
            }
        });

        // Açıq aralıq əvvəl həmişə «X ildən çox» (hədd xaric) sayılırdı.
        DB::table('vacation_norms')
            ->where('group', 'seniority')
            ->whereNull('max_value')
            ->update(['min_inclusive' => false]);

        foreach ([5 => 10, 10 => 15, 15 => null] as $min => $max) {
            DB::table('vacation_norms')
                ->where('group', 'seniority')
                ->where('is_statutory', true)
                ->whereNull('statutory_key')
                ->where('min_value', $min)
                ->when($max === null, fn ($query) => $query->whereNull('max_value'), fn ($query) => $query->where('max_value', $max))
                ->update(['statutory_key' => 'seniority_'.$min]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vacation_norms')) {
            return;
        }

        Schema::table('vacation_norms', function (Blueprint $table): void {
            if (Schema::hasColumn('vacation_norms', 'statutory_key')) {
                $table->dropColumn('statutory_key');
            }

            if (Schema::hasColumn('vacation_norms', 'min_inclusive')) {
                $table->dropColumn('min_inclusive');
            }
        });
    }
};
