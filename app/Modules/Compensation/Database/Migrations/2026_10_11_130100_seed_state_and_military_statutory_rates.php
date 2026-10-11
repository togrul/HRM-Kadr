<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dövlət qulluğu («state») və hərbi («military») rejimləri üçün qanuni dərəcələr. Bu sətirlər
 * olmayanda həmin rejimlərə standart (regime_id = null) — qeyri-neft-qaz özəl sektor — dərəcələri
 * tətbiq olunurdu. Mənbələr (11.10.2026 oxunub, docs/payroll-compensation-plan.md EK A.2):
 *
 *  - gəlir vergisi: Vergi Məcəlləsi m.101.1, 1-ci cədvəl — 2500 manatadək 14%, 2500-dən çox
 *    350 + 25% (m.101.1-1 güzəşt cədvəli yalnız neft-qaz sahəsində fəaliyyəti olmayan və
 *    qeyri-dövlət sektoruna aiddir);
 *  - DSMF: «Sosial sığorta haqqında» Qanun m.14.3 — işəgötürən 22%, işçi 3% (m.14.4 pilləsi yalnız
 *    qeyri-dövlət, qeyri-neft-qaz sektorunadır); hərbi qulluqçular m.12 ilə əhatə olunur;
 *  - icbari tibbi sığorta: «Tibbi sığorta haqqında» Qanun m.15-10.1.2 — hər tərəf 8000 manatadək
 *    hissədən 2%, yuxarı hissədən 0,5% (hərbi qulluqçular mətndə adı ilə daxildir);
 *  - işsizlikdən sığorta: «İşsizlikdən sığorta haqqında» Qanun m.9.0 — 0,5% / 0,5%; sığortaolunan
 *    «əmək müqaviləsi bağlamış» işçidir (m.2.0.3), Əmək Məcəlləsi isə hərbi qulluqçulara şamil
 *    edilmir (ƏM m.6.a) — hərbi rejimdə 0% sətri standart 0,5%-i əvəz edir (mühasib təsdiqi).
 *
 * Yalnız həmin rejim + komponent + ödəyən üçün heç bir sətir yoxdursa yazılır — mühasibin ekranda
 * yaratdığı və ya dəyişdiyi sətirlərə toxunulmur. effective_from kilidlənmiş ayları dəyişmir (bax
 * 2026_10_11_130000 miqrasiyası).
 */
return new class extends Migration
{
    private const LAW_FROM = '2026-01-01';

    public function up(): void
    {
        if (! Schema::hasTable('statutory_rates') || ! Schema::hasTable('compensation_regimes')) {
            return;
        }

        $from = $this->safeStart();
        $now = now();

        foreach ($this->rates() as $regimeCode => $rows) {
            $regimeId = DB::table('compensation_regimes')->where('code', $regimeCode)->value('id');

            if ($regimeId === null) {
                continue;
            }

            foreach ($rows as [$code, $payer, $base, $brackets]) {
                $exists = DB::table('statutory_rates')
                    ->where('regime_id', $regimeId)
                    ->where('component_code', $code)
                    ->where('payer', $payer)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('statutory_rates')->insert([
                    'regime_id' => $regimeId,
                    'component_code' => $code,
                    'payer' => $payer,
                    'base' => $base,
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
        if (! Schema::hasTable('statutory_rates') || ! Schema::hasTable('compensation_regimes')) {
            return;
        }

        foreach ($this->rates() as $regimeCode => $rows) {
            $regimeId = DB::table('compensation_regimes')->where('code', $regimeCode)->value('id');

            if ($regimeId === null) {
                continue;
            }

            foreach ($rows as [$code, $payer, , $brackets]) {
                // Yalnız seed dəyəri dəyişdirilməmiş sətir silinir.
                DB::table('statutory_rates')
                    ->where('regime_id', $regimeId)
                    ->where('component_code', $code)
                    ->where('payer', $payer)
                    ->get(['id', 'brackets'])
                    ->filter(fn ($row): bool => $this->normalize(json_decode((string) $row->brackets, true)) === $this->normalize($brackets))
                    ->each(fn ($row) => DB::table('statutory_rates')->where('id', $row->id)->delete());
            }
        }
    }

    /**
     * @return array<string, list<array{0: string, 1: string, 2: string, 3: list<array{up_to: int|null, rate: int|float}>}>>
     */
    private function rates(): array
    {
        $publicSector = [
            ['income_tax', 'ee', 'taxable', [['up_to' => 2500, 'rate' => 14], ['up_to' => null, 'rate' => 25]]],
            ['dsmf', 'ee', 'social', [['up_to' => null, 'rate' => 3]]],
            ['dsmf', 'er', 'social', [['up_to' => null, 'rate' => 22]]],
            ['medical', 'ee', 'social', [['up_to' => 8000, 'rate' => 2], ['up_to' => null, 'rate' => 0.5]]],
            ['medical', 'er', 'social', [['up_to' => 8000, 'rate' => 2], ['up_to' => null, 'rate' => 0.5]]],
        ];

        return [
            'state' => array_merge($publicSector, [
                ['unemployment', 'ee', 'social', [['up_to' => null, 'rate' => 0.5]]],
                ['unemployment', 'er', 'social', [['up_to' => null, 'rate' => 0.5]]],
            ]),
            'military' => array_merge($publicSector, [
                ['unemployment', 'ee', 'social', [['up_to' => null, 'rate' => 0]]],
                ['unemployment', 'er', 'social', [['up_to' => null, 'rate' => 0]]],
            ]),
        ];
    }

    /** 01.01.2026, amma ən son kilidlənmiş adi hesablamanın ayından sonra. */
    private function safeStart(): string
    {
        if (! Schema::hasTable('payroll_runs') || ! Schema::hasTable('payroll_periods')) {
            return self::LAW_FROM;
        }

        $lastLocked = DB::table('payroll_runs')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payroll_runs.status', 'locked')
            ->where('payroll_runs.run_type', 'regular')
            ->max('payroll_periods.ends_on');

        if ($lastLocked === null) {
            return self::LAW_FROM;
        }

        return max(self::LAW_FROM, Carbon::parse((string) $lastLocked)->addDay()->startOfMonth()->toDateString());
    }

    /**
     * @return list<array{0: float|null, 1: float}>
     */
    private function normalize(mixed $brackets): array
    {
        return array_values(array_map(fn ($bracket): array => [
            is_array($bracket) && isset($bracket['up_to']) ? (float) $bracket['up_to'] : null,
            is_array($bracket) ? (float) ($bracket['rate'] ?? 0) : 0.0,
        ], is_array($brackets) ? $brackets : []));
    }
};
