<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statutory_rates')) {
            return;
        }

        $now = now();
        $from = '2026-01-01';

        // AZ 2026 non-oil private sector marginal brackets (plan EK A).
        // regime_id = null → default for every regime until a regime-specific row overrides it.
        // [component_code, payer, base, brackets]
        $rates = [
            ['income_tax', 'ee', 'taxable', [['up_to' => 2500, 'rate' => 3], ['up_to' => 8000, 'rate' => 10], ['up_to' => null, 'rate' => 14]]],
            ['dsmf', 'ee', 'social', [['up_to' => 200, 'rate' => 3], ['up_to' => null, 'rate' => 10]]],
            ['dsmf', 'er', 'social', [['up_to' => 200, 'rate' => 22], ['up_to' => null, 'rate' => 15]]],
            ['unemployment', 'ee', 'social', [['up_to' => null, 'rate' => 0.5]]],
            ['unemployment', 'er', 'social', [['up_to' => null, 'rate' => 0.5]]],
            ['medical', 'ee', 'social', [['up_to' => 2500, 'rate' => 2], ['up_to' => null, 'rate' => 0.5]]],
            ['medical', 'er', 'social', [['up_to' => 2500, 'rate' => 2], ['up_to' => null, 'rate' => 0.5]]],
        ];

        foreach ($rates as [$code, $payer, $base, $brackets]) {
            DB::table('statutory_rates')->updateOrInsert(
                ['regime_id' => null, 'component_code' => $code, 'payer' => $payer, 'effective_from' => $from],
                ['base' => $base, 'brackets' => json_encode($brackets), 'is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('statutory_rates')) {
            DB::table('statutory_rates')->whereNull('regime_id')->where('effective_from', '2026-01-01')->delete();
        }
    }
};
