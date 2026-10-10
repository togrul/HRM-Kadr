<?php

use App\Modules\Vacation\Application\Services\VacationNormDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sonradan əlavə olunmuş qanuni normalar (ƏM m.114.3 "b": VTİSK üzrə rəhbər və mütəxəssis —
 * 30 gün) mövcud cədvələ yazılır. Artıq olan sətir (qrup, sahə, şərt, min) toxunulmur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vacation_norms')) {
            return;
        }

        $now = now();

        foreach (VacationNormDefaults::rows() as $row) {
            $exists = DB::table('vacation_norms')
                ->where('group', $row['group'])
                ->where('scope', $row['scope'])
                ->where('is_statutory', true)
                ->when($row['condition'] === null, fn ($q) => $q->whereNull('condition'), fn ($q) => $q->where('condition', $row['condition']))
                ->when($row['min_value'] === null, fn ($q) => $q->whereNull('min_value'), fn ($q) => $q->where('min_value', $row['min_value']))
                ->exists();

            if (! $exists) {
                DB::table('vacation_norms')->insert($row + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('vacation_norms')->where('scope', 'vtisk_category')->where('is_statutory', true)->delete();
    }
};
