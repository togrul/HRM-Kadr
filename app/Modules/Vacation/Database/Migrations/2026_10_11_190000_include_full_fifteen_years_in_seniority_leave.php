<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ƏM m.116.1-in üçüncü pilləsi: standart «15 ildən çox» (15 xaric) əvəzinə «15 il və daha çox» —
 * 15 il tamam olanda 6 gün (əsaslandırma: docs/vacation-legal-basis.md, (b-2)). Məcəllə «X-dan
 * Y-dək» ifadəsini [X, Y) kimi işlədir (m.119.1: «16 yaşından 18 yaşınadək»), ona görə «on ildən
 * on beş ilədək» pilləsi düz 15 ildə bitir; boşluq işçinin xeyrinə doldurulur (m.7.1, 7.3, 145).
 *
 * Yalnız qanuni pillə (`statutory_key` = seniority_15) qanundakı köhnə dəyərlərdədirsə (15, açıq,
 * 15 xaric, 6 gün) dəyişir — qurumun dəyişdirdiyi sətrə toxunulmur. Keçmiş iş illərinə toxunmamaq
 * üçün işləyən sistemdə köhnə sətir dünəndən bağlanır və yeni versiya bu gündən başlayır (pillə iş
 * ilinin başlanğıcına görə seçilir — artıq başlamış iş illəri köhnə versiyanı saxlayır). Hələ
 * heç bir işçi və iş ili olmayan təzə qurulumda sətir yerində dəyişir.
 */
return new class extends Migration
{
    private const KEY = 'seniority_15';

    public function up(): void
    {
        if (! Schema::hasTable('vacation_norms') || ! Schema::hasColumn('vacation_norms', 'statutory_key')) {
            return;
        }

        $today = Carbon::today();
        $fresh = $this->isFreshInstall();

        $rows = DB::table('vacation_norms')
            ->where('group', 'seniority')
            ->where('statutory_key', self::KEY)
            ->whereNull('valid_to')
            ->where('min_value', 15)
            ->whereNull('max_value')
            ->where('min_inclusive', false)
            ->where('days', 6)
            ->get();

        foreach ($rows as $row) {
            $notStarted = $row->valid_from !== null && Carbon::parse((string) $row->valid_from)->gte($today);

            if ($fresh || $notStarted) {
                DB::table('vacation_norms')->where('id', $row->id)->update(['min_inclusive' => true, 'updated_at' => now()]);

                continue;
            }

            DB::transaction(function () use ($row, $today): void {
                DB::table('vacation_norms')->where('id', $row->id)->update([
                    'valid_to' => $today->copy()->subDay()->toDateString(),
                    'updated_at' => now(),
                ]);

                $next = (array) $row;
                unset($next['id']);

                DB::table('vacation_norms')->insert([
                    ...$next,
                    'min_inclusive' => true,
                    'valid_from' => $today->toDateString(),
                    'valid_to' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vacation_norms') || ! Schema::hasColumn('vacation_norms', 'statutory_key')) {
            return;
        }

        $current = DB::table('vacation_norms')
            ->where('group', 'seniority')
            ->where('statutory_key', self::KEY)
            ->whereNull('valid_to')
            ->where('min_value', 15)
            ->whereNull('max_value')
            ->where('min_inclusive', true)
            ->where('days', 6)
            ->get();

        foreach ($current as $row) {
            $previous = $row->valid_from === null ? null : DB::table('vacation_norms')
                ->where('group', 'seniority')
                ->where('statutory_key', self::KEY)
                ->where('valid_to', Carbon::parse((string) $row->valid_from)->subDay()->toDateString())
                ->where('min_inclusive', false)
                ->first();

            if ($previous === null) {
                DB::table('vacation_norms')->where('id', $row->id)->update(['min_inclusive' => false, 'updated_at' => now()]);

                continue;
            }

            DB::transaction(function () use ($row, $previous): void {
                DB::table('vacation_norms')->where('id', $row->id)->delete();
                DB::table('vacation_norms')->where('id', $previous->id)->update(['valid_to' => null, 'updated_at' => now()]);
            });
        }
    }

    /** Hələ heç bir işçi və hesablanmış iş ili yoxdur — keçmiş iş ili də yoxdur. */
    private function isFreshInstall(): bool
    {
        foreach (['personnels', 'vacation_work_years'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                return false;
            }
        }

        return true;
    }
};
