<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Əmək şəraitinə görə əlavə məzuniyyətin ayrıca iş ili (NK-nin 95 nömrəli qərarı, b.7: «iş
 * stajı əsas əmək və əlavə məzuniyyətlər üzrə ayrı-ayrılıqda hesablanır»): balansda hər işçi
 * üçün iki sıra iş ili olur — `annual` (ƏM m.113.3, işə qəbul günündən; əsas + staj + uşaq) və
 * `conditions` (şəraitdə işə başlanğıc günündən; yalnız şərait əlavəsi). Sıra nömrəsi hər növ
 * daxilində unikaldır.
 *
 * Artıq dondurulmuş (bitmiş) mülki iş illərində şərait günləri ümumi iş ilinin içində idi — onlar
 * oradan çıxarılır ki, ayrıca şərait ili ilə ikiqat sayılmasın.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vacation_work_years', 'kind')) {
            Schema::table('vacation_work_years', function (Blueprint $table): void {
                $table->string('kind', 20)->default('annual')->after('tabel_no');
            });

            Schema::table('vacation_work_years', function (Blueprint $table): void {
                $table->unique(['tabel_no', 'kind', 'sequence']);
            });

            Schema::table('vacation_work_years', function (Blueprint $table): void {
                $table->dropUnique(['tabel_no', 'sequence']);
            });
        }

        DB::table('vacation_work_years')
            ->where('kind', 'annual')
            ->where('strategy', 'civil')
            ->whereNotNull('breakdown')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $breakdown = json_decode((string) $row->breakdown, true);
                    $conditions = is_array($breakdown) ? (int) ($breakdown['conditions'] ?? 0) : 0;

                    if ($conditions <= 0) {
                        continue;
                    }

                    $breakdown['conditions'] = 0;
                    $breakdown['total'] = max(0, (int) ($breakdown['total'] ?? $row->entitled_days) - $conditions);

                    DB::table('vacation_work_years')->where('id', $row->id)->update([
                        'entitled_days' => max(0, (int) $row->entitled_days - $conditions),
                        'breakdown' => json_encode($breakdown),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vacation_work_years', 'kind')) {
            return;
        }

        DB::table('vacation_work_years')->where('kind', '!=', 'annual')->delete();

        Schema::table('vacation_work_years', function (Blueprint $table): void {
            $table->unique(['tabel_no', 'sequence']);
        });

        Schema::table('vacation_work_years', function (Blueprint $table): void {
            $table->dropUnique(['tabel_no', 'kind', 'sequence']);
            $table->dropColumn('kind');
        });
    }
};
