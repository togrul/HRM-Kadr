<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('compensation_regimes')) {
            $regimes = [
                ['code' => 'military', 'name' => 'Hərbi / uniformlu xidmət', 'sort' => 1],
                ['code' => 'state', 'name' => 'Dövlət qulluğu', 'sort' => 2],
                ['code' => 'private', 'name' => 'Mülki / özəl', 'sort' => 3],
            ];

            foreach ($regimes as $regime) {
                DB::table('compensation_regimes')->updateOrInsert(
                    ['code' => $regime['code']],
                    ['name' => $regime['name'], 'is_active' => true, 'sort' => $regime['sort'], 'updated_at' => $now, 'created_at' => $now],
                );
            }
        }

        if (Schema::hasTable('compensation_components')) {
            // [code, name, type, calc_type, taxable, affects_social, is_statutory]
            $components = [
                ['base', 'Baza maaş', 'earning', 'fixed', true, true, false],
                ['rank_supplement', 'Rütbə əlavəsi', 'earning', 'fixed', true, true, false],
                ['seniority', 'Staj əlavəsi', 'earning', 'percent', true, true, false],
                ['hazard', 'Zərərlilik əlavəsi', 'earning', 'percent', true, true, false],
                ['secrecy', 'Məxfilik əlavəsi', 'earning', 'percent', true, true, false],
                ['language', 'Dil bilik əlavəsi', 'earning', 'fixed', true, true, false],
                ['income_tax', 'Gəlir vergisi', 'deduction', 'percent', false, false, true],
                ['dsmf_ee', 'DSMF (işçi payı)', 'deduction', 'percent', false, false, true],
                ['unemployment_ee', 'İşsizlikdən sığorta (işçi payı)', 'deduction', 'percent', false, false, true],
                ['medical_ee', 'İcbari tibbi sığorta (işçi payı)', 'deduction', 'percent', false, false, true],
                ['union', 'Həmkarlar ittifaqı', 'deduction', 'percent', false, false, false],
            ];

            foreach ($components as $i => [$code, $name, $type, $calcType, $taxable, $affectsSocial, $isStatutory]) {
                DB::table('compensation_components')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'type' => $type,
                        'calc_type' => $calcType,
                        'taxable' => $taxable,
                        'affects_social' => $affectsSocial,
                        'is_statutory' => $isStatutory,
                        'sort' => $i + 1,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compensation_components')) {
            DB::table('compensation_components')->whereIn('code', [
                'base', 'rank_supplement', 'seniority', 'hazard', 'secrecy', 'language',
                'income_tax', 'dsmf_ee', 'unemployment_ee', 'medical_ee', 'union',
            ])->delete();
        }

        if (Schema::hasTable('compensation_regimes')) {
            DB::table('compensation_regimes')->whereIn('code', ['military', 'state', 'private'])->delete();
        }
    }
};
