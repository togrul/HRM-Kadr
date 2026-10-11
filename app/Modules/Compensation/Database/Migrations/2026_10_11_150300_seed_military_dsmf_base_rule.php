<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hərbi qulluqçunun DSMF bazası — «pul təminatı» (docs/payroll-legal-basis.md §6.3, 11.10.2026 oxunub):
 *
 *  - «Sosial sığorta haqqında» Qanun m.15: haqq «əmək ödənişinin və gəlirlərin bütün növləri»ndən hesablanır,
 *    istisna — «hərbi qulluqçulara və xüsusi rütbəli şəxslərə verilən və «Əmək pensiyaları haqqında» Qanunu ilə
 *    müəyyən edilən təminat xərcliyinə (pul təminatına) daxil edilməyən ödənişlər»;
 *  - «Əmək pensiyaları haqqında» Qanun m.1.0.5: pul təminatı — aylıq vəzifə maaşı, rütbə maaşı, xidmət illərinə
 *    görə əlavə, ərzaq payı əvəzinə pul kompensasiyası, vəzifə maaşından hesablanan digər əlavələr (büdcədənkənar
 *    vəsait hesabına olanlar istisna), artımlar.
 *
 * Qayda `statutory_exemptions`-da (növ `taxable_components`, komponent `dsmf`, rejim `military`): DSMF bazasına
 * yalnız sadalanan komponent kodları daxildir — birdəfəlik mükafatlar və əmr ödənişləri düşmür. Kodlar kataloqun
 * mövcud qazanc komponentləridir; mühasib siyahını «Vergi/sığorta dərəcələri» ekranında yoxlamalıdır ⚠️.
 * Yalnız eyni kodlu sətir yoxdursa yazılır; effective_from kilidli aylardan sonradır.
 */
return new class extends Migration
{
    private const CODE = 'military_dsmf_pay_allowance';

    public function up(): void
    {
        if (! Schema::hasTable('statutory_exemptions') || ! Schema::hasTable('compensation_regimes')) {
            return;
        }

        $militaryId = DB::table('compensation_regimes')->where('code', 'military')->value('id');

        if ($militaryId === null || DB::table('statutory_exemptions')->where('code', self::CODE)->exists()) {
            return;
        }

        DB::table('statutory_exemptions')->insert([
            'regime_id' => $militaryId,
            'code' => self::CODE,
            'legal_basis' => 'SS m.15; ƏP m.1.0.5',
            'kind' => 'taxable_components',
            'component_code' => 'dsmf',
            'amount' => null,
            'income_up_to' => null,
            'main_workplace_only' => false,
            'component_codes' => json_encode(['base', 'rank_supplement', 'seniority', 'hazard', 'secrecy', 'language']),
            'effective_from' => $this->safeStart('2026-01-01'),
            'effective_to' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('statutory_exemptions')) {
            DB::table('statutory_exemptions')->where('code', self::CODE)->delete();
        }
    }

    private function safeStart(string $lawFrom): string
    {
        if (! Schema::hasTable('payroll_runs') || ! Schema::hasTable('payroll_periods')) {
            return $lawFrom;
        }

        $lastLocked = DB::table('payroll_runs')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payroll_runs.status', 'locked')
            ->where('payroll_runs.run_type', 'regular')
            ->max('payroll_periods.ends_on');

        return $lastLocked === null ? $lawFrom : max($lawFrom, Carbon::parse((string) $lastLocked)->addDay()->startOfMonth()->toDateString());
    }
};
