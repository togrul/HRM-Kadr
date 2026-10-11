<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Qeyri-neft-qaz özəl sektor dərəcələrinin rəsmi mətnlə uzlaşdırılması (09.12.2025 tarixli
 * 297-VIIQD nömrəli Qanun, 01.01.2026-dan; mənbələr docs/payroll-compensation-plan.md EK A):
 *   - «Sosial sığorta haqqında» Qanun m.14.4: işəgötürən payı 8000 manatdan çox hissədən 11%
 *     (1214 manat + 11%), əvvəlki seed-də həddsiz 15% idi. İşçi payı 8000-dən yuxarı da 10%-dir —
 *     dəyişmir, ayrıca pillə yalnız aydınlıq üçün yazılır;
 *   - Vergi Məcəlləsi m.101.1-1.2 və 101.1-1.3: gəlir vergisinin 2027 (5% / 125 + 10% /
 *     675 + 14%) və 2028-ci ildən (7% / 175 + 10% / 725 + 14%) cədvəlləri effective-dated sətir
 *     kimi əlavə olunur.
 * Yalnız standart (regime_id = null) sətirlərə və yalnız seed dəyəri dəyişdirilməyibsə toxunulur —
 * mühasibin ekranda düzəltdiyi sətir üstündən yazılmır.
 */
return new class extends Migration
{
    /** @var list<array{up_to: int|float|null, rate: int|float}> */
    private const DSMF_ER_SEEDED = [['up_to' => 200, 'rate' => 22], ['up_to' => null, 'rate' => 15]];

    /** @var list<array{up_to: int|float|null, rate: int|float}> */
    private const DSMF_ER_LAW = [['up_to' => 200, 'rate' => 22], ['up_to' => 8000, 'rate' => 15], ['up_to' => null, 'rate' => 11]];

    /** @var list<array{up_to: int|float|null, rate: int|float}> */
    private const DSMF_EE_SEEDED = [['up_to' => 200, 'rate' => 3], ['up_to' => null, 'rate' => 10]];

    /** @var list<array{up_to: int|float|null, rate: int|float}> */
    private const DSMF_EE_LAW = [['up_to' => 200, 'rate' => 3], ['up_to' => 8000, 'rate' => 10], ['up_to' => null, 'rate' => 10]];

    public function up(): void
    {
        if (! Schema::hasTable('statutory_rates')) {
            return;
        }

        $this->replaceSeeded('dsmf', 'er', self::DSMF_ER_SEEDED, self::DSMF_ER_LAW);
        $this->replaceSeeded('dsmf', 'ee', self::DSMF_EE_SEEDED, self::DSMF_EE_LAW);

        $now = now();

        foreach ([
            '2027-01-01' => [['up_to' => 2500, 'rate' => 5], ['up_to' => 8000, 'rate' => 10], ['up_to' => null, 'rate' => 14]],
            '2028-01-01' => [['up_to' => 2500, 'rate' => 7], ['up_to' => 8000, 'rate' => 10], ['up_to' => null, 'rate' => 14]],
        ] as $from => $brackets) {
            $exists = DB::table('statutory_rates')
                ->whereNull('regime_id')
                ->where('component_code', 'income_tax')
                ->where('payer', 'ee')
                ->whereDate('effective_from', $from)
                ->exists();

            if (! $exists) {
                DB::table('statutory_rates')->insert([
                    'regime_id' => null,
                    'component_code' => 'income_tax',
                    'payer' => 'ee',
                    'base' => 'taxable',
                    'brackets' => json_encode($brackets),
                    'effective_from' => $from,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('statutory_rates')) {
            return;
        }

        $this->replaceSeeded('dsmf', 'er', self::DSMF_ER_LAW, self::DSMF_ER_SEEDED);
        $this->replaceSeeded('dsmf', 'ee', self::DSMF_EE_LAW, self::DSMF_EE_SEEDED);

        DB::table('statutory_rates')
            ->whereNull('regime_id')
            ->where('component_code', 'income_tax')
            ->where('payer', 'ee')
            ->where(fn ($query) => $query->whereDate('effective_from', '2027-01-01')->orWhereDate('effective_from', '2028-01-01'))
            ->delete();
    }

    /**
     * @param  list<array{up_to: int|float|null, rate: int|float}>  $expected
     * @param  list<array{up_to: int|float|null, rate: int|float}>  $replacement
     */
    private function replaceSeeded(string $code, string $payer, array $expected, array $replacement): void
    {
        $rows = DB::table('statutory_rates')
            ->whereNull('regime_id')
            ->where('component_code', $code)
            ->where('payer', $payer)
            ->whereDate('effective_from', '2026-01-01')
            ->get(['id', 'brackets']);

        foreach ($rows as $row) {
            $brackets = json_decode((string) $row->brackets, true);

            if (! is_array($brackets) || $this->normalize($brackets) !== $this->normalize($expected)) {
                continue;
            }

            DB::table('statutory_rates')->where('id', $row->id)->update([
                'brackets' => json_encode($replacement),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $brackets
     * @return list<array{0: float|null, 1: float}>
     */
    private function normalize(array $brackets): array
    {
        return array_values(array_map(fn ($bracket): array => [
            is_array($bracket) && isset($bracket['up_to']) ? (float) $bracket['up_to'] : null,
            is_array($bracket) ? (float) ($bracket['rate'] ?? 0) : 0.0,
        ], $brackets));
    }
};
