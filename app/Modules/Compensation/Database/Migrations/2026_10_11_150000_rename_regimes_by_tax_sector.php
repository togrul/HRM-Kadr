<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rejim adları vergi/sosial sığorta sektoruna görə aydınlaşdırılır (docs/payroll-legal-basis.md §8):
 * «state» rejimi neft-qaz sahəsində fəaliyyəti olan şirkətləri də əhatə edir (VM m.101.1-1, 101.1-4;
 * Nazirlər Kabinetinin 18.02.2019 tarixli 56 nömrəli Qərarı), «private» isə yalnız neft-qaz sahəsində
 * fəaliyyəti olmayan qeyri-dövlət sektorudur. Yalnız seed adı dəyişdirilməmiş sətrə toxunulur.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> code => [köhnə seed adı, yeni ad] */
    private const NAMES = [
        'state' => ['Dövlət qulluğu', 'Dövlət və neft-qaz sektoru'],
        'private' => ['Mülki / özəl', 'Özəl qeyri-neft-qaz sektoru'],
    ];

    public function up(): void
    {
        $this->rename(0, 1);
    }

    public function down(): void
    {
        $this->rename(1, 0);
    }

    private function rename(int $from, int $to): void
    {
        if (! Schema::hasTable('compensation_regimes')) {
            return;
        }

        foreach (self::NAMES as $code => $names) {
            DB::table('compensation_regimes')
                ->where('code', $code)
                ->where('name', $names[$from])
                ->update(['name' => $names[$to], 'updated_at' => now()]);
        }
    }
};
