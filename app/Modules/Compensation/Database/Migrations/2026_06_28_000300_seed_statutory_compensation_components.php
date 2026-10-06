<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compensation_components')) {
            return;
        }

        $now = now();

        // Statutory employee deductions missing from the initial catalog.
        // Rates (unemployment 0.5%; medical 2% up to 2500 AZN, 0.5% above) live in the
        // Phase 3 statutory_rates table — here we only register the deduction types.
        $components = [
            ['unemployment_ee', 'İşsizlikdən sığorta (işçi payı)', 10],
            ['medical_ee', 'İcbari tibbi sığorta (işçi payı)', 11],
        ];

        foreach ($components as [$code, $name, $sort]) {
            DB::table('compensation_components')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'deduction',
                    'calc_type' => 'percent',
                    'taxable' => false,
                    'affects_social' => false,
                    'is_statutory' => true,
                    'sort' => $sort,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compensation_components')) {
            DB::table('compensation_components')->whereIn('code', ['unemployment_ee', 'medical_ee'])->delete();
        }
    }
};
