<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\PayrollOneOffEarning;
use App\Models\Payslip;
use App\Modules\Benefits\Contracts\ImputedIncomeRestatement;
use App\Modules\Compensation\Domain\Contracts\TaxReliefReadRepository;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use App\Services\StructureScope;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «Vergi düzəliş hesabatı» (docs/payroll-legal-basis.md §11).
 *
 * Kilidlənmiş (ödənilmiş) payslip-lər yaddaşda, YALNIZ OXUMAQLA, cari qaydalarla yenidən hesablanır və
 * işçi/ay üzrə tutulmuş ilə düzgün məbləğ arasındakı fərq göstərilir: gəlir vergisi, DSMF (işçi/işəgötürən),
 * işsizlikdən sığorta, icbari tibbi sığorta. Heç bir payslip, run və ya ledger dəyişmir.
 *
 * «Cari qaydalar»:
 *  - qazanc sətirləri — payslip-in özündən (ödənilmiş fakt; bayraqları — vergi/sosial — ilə); imtiyazın
 *    natura formasında gəlir sətri isə Benefits-in indiki qaydası ilə (yemək kartının gündəlik norması);
 *  - rejim — işçinin bu günkü aktiv təyinatının rejimi (rejim düzəlişindən sonra düzgün olan), yoxdursa
 *    ödəniş anındakı rejim;
 *  - dərəcə və azadolmalar — «qayda tarixi» = min(bu gün, həmin ilin 31 dekabrı), amma ayın sonundan tez
 *    deyil. Seed sətirlərinin effective_from-u kilidli aylardan sonraya çəkildiyi üçün (retro yaranmasın)
 *    ayın öz tarixində onlar görünmür; ilin sonundakı qayda dəsti isə həmin il qüvvədə olan qanunu əks
 *    etdirir (fərziyyə: il ərzində qanun dəyişməyib — ⚠️ mühasib yoxlamalıdır);
 *  - işçi güzəşt kartları — qanuni başlanğıcdan (`legal_from`, VM m.102.8).
 *
 * Mühasib nəticə ilə VM m.72.5 üzrə dəqiqləşdirilmiş hesabat verir; istəsə artıq tutulmuş məbləği
 * növbəti run-a ayrıca birdəfəlik ödəniş kimi əlavə edir (recordRefund — açıq təsdiq, audit).
 */
class TaxAdjustmentReportService
{
    /** payslip sətir kodları → hesabat sütunu. */
    public const COMPONENTS = ['income_tax_ee', 'dsmf_ee', 'dsmf_er', 'unemployment_ee', 'unemployment_er', 'medical_ee', 'medical_er'];

    /** İşçidən tutulan komponentlər — yalnız bunların artığı işçiyə qaytarıla bilər. */
    public const EMPLOYEE_SIDE = ['income_tax_ee', 'dsmf_ee', 'unemployment_ee', 'medical_ee'];

    public const REFUND_CODE = 'tax_adjustment_refund';

    public function __construct(
        private readonly StatutoryEngine $engine,
        private readonly PayrollOneOffEarnings $oneOffs,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function build(string $fromMonth, string $toMonth, ?StructureScope $scope = null, ?string $today = null): array
    {
        $from = Carbon::parse($fromMonth.'-01')->startOfMonth();
        $to = Carbon::parse($toMonth.'-01')->endOfMonth();
        $today = Carbon::parse($today ?? now()->toDateString());

        $query = Payslip::query()
            ->where('status', 'locked')
            ->whereHas('run', fn ($q) => $q->where('status', 'locked')
                ->whereHas('period', fn ($p) => $p->whereDate('ends_on', '>=', $from->toDateString())->whereDate('starts_on', '<=', $to->toDateString())))
            ->with(['run.period', 'lines', 'personnel:id,tabel_no,surname,name,patronymic,structure_id,workplace_type'])
            ->orderBy('tabel_no')
            ->orderBy('payroll_run_id');

        if ($scope !== null) {
            $query = $scope->constrainThrough($query, 'personnel');
        }

        $regimeNames = CompensationRegime::query()->pluck('name', 'id')->map(fn ($name): string => (string) $name)->all();
        $refunded = $this->recordedRefunds();
        $rows = [];

        foreach ($query->get() as $payslip) {
            $period = $payslip->run->period;

            if ($period === null) {
                continue;
            }

            $row = $this->recompute($payslip, $period->ends_on->toDateString(), (int) $period->year, (string) $payslip->run->run_type, $today);
            $row['period'] = (string) $period->code;
            $row['full_name'] = trim(($payslip->personnel->surname ?? '').' '.($payslip->personnel->name ?? '').' '.($payslip->personnel->patronymic ?? ''));
            $row['paid_regime'] = $regimeNames[$row['paid_regime_id']] ?? '—';
            $row['target_regime'] = $regimeNames[$row['target_regime_id']] ?? '—';
            $row['refund_recorded'] = isset($refunded[$this->sourceKey($payslip)]);

            if ($row['has_difference']) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Bir payslip-in yaddaşda yenidən hesablanması.
     *
     * @return array<string, mixed>
     */
    public function recompute(Payslip $payslip, string $periodEnd, int $year, string $runType, Carbon $today): array
    {
        $tabelNo = (string) $payslip->tabel_no;
        $lines = $payslip->lines
            ->filter(fn ($line): bool => $line->kind === 'earning' && $line->code !== RetroService::RETRO)
            ->map(fn ($line): array => [
                'code' => (string) $line->code,
                'kind' => 'earning',
                'amount' => (float) $line->amount,
                'taxable' => (bool) $line->taxable,
                'affects_social' => (bool) $line->affects_social,
            ])
            ->values()
            ->all();
        $lines = $this->restateImputedIncome($payslip, $lines);

        $paidRegimeId = $this->regimeOn($tabelNo, $periodEnd) ?? ($payslip->getAttribute('regime_id') === null ? null : (int) $payslip->getAttribute('regime_id'));
        $targetRegimeId = $this->regimeOn($tabelNo, $today->toDateString(), true) ?? $paidRegimeId;
        $rulesDate = $this->rulesDate($periodEnd, $year, $today);
        $monthlyRelief = $runType !== 'off_cycle';
        $mainWorkplace = ($payslip->personnel->workplace_type ?? null) !== 'secondary';

        $periodStart = Carbon::parse($periodEnd)->startOfMonth()->toDateString();
        $personal = $monthlyRelief && app()->bound(TaxReliefReadRepository::class)
            ? app(TaxReliefReadRepository::class)->codesFor($tabelNo, $periodStart, $periodEnd, true)
            : [];

        $taxableBase = $this->engine->taxableBase($lines, $targetRegimeId, $rulesDate);
        $socialBase = $this->engine->socialBase($lines);

        $recomputed = collect($this->engine->compute($targetRegimeId, $rulesDate, $taxableBase, $socialBase, $mainWorkplace, $monthlyRelief, $personal, $this->engine->socialBases($lines, $targetRegimeId, $rulesDate)))
            ->keyBy('code');
        $paid = $payslip->lines->whereIn('code', self::COMPONENTS)->groupBy('code')->map(fn ($group): float => round((float) $group->sum('amount'), 2));

        $row = [
            'payslip_id' => (int) $payslip->getKey(),
            'tabel_no' => $tabelNo,
            'run_type' => $runType,
            'paid_regime_id' => $paidRegimeId,
            'target_regime_id' => $targetRegimeId,
            'reliefs' => implode(', ', $personal),
            'has_difference' => false,
            'refundable' => 0.0,
        ];

        foreach (self::COMPONENTS as $code) {
            $was = (float) ($paid[$code] ?? 0.0);
            $now = round((float) ($recomputed[$code]['amount'] ?? 0.0), 2);
            $diff = round($was - $now, 2);
            $row[$code.'_paid'] = $was;
            $row[$code.'_now'] = $now;
            $row[$code.'_diff'] = $diff;

            if (abs($diff) >= 0.01) {
                $row['has_difference'] = true;
            }
        }

        return $row;
    }

    /**
     * Artıq tutulmuş məbləği növbəti açıq ayın adi run-una birdəfəlik (vergidən və sosial bazadan kənar)
     * ödəniş kimi əlavə edir — yalnız seçilmiş işçi tərəfi komponentlərin MÜSBƏT fərqi (artıq tutulub).
     * Az tutulmuş məbləğ avtomatik tutulmur. Eyni payslip üçün təkrar çağırış ödənilməmiş sətri yeniləyir.
     *
     * @param  list<int>  $payslipIds
     * @param  list<string>  $components
     * @return array{recorded: int, total: float}
     *
     * @throws ValidationException
     */
    public function recordRefunds(array $payslipIds, array $components, int $payYear, int $payMonth, string $reason, ?StructureScope $scope = null): array
    {
        $reason = trim($reason);
        $components = array_values(array_intersect($components, self::EMPLOYEE_SIDE));

        if ($reason === '') {
            throw ValidationException::withMessages(['refundReason' => __('payroll::tax_adjustment.messages.reason_required')]);
        }

        if ($components === []) {
            throw ValidationException::withMessages(['refundComponents' => __('payroll::tax_adjustment.messages.components_required')]);
        }

        $query = Payslip::query()
            ->whereKey($payslipIds)
            ->where('status', 'locked')
            ->with(['run.period', 'lines', 'personnel:id,tabel_no,structure_id,workplace_type']);

        if ($scope !== null) {
            $query = $scope->constrainThrough($query, 'personnel');
        }

        $recorded = 0;
        $total = 0.0;
        $today = Carbon::now();

        foreach ($query->get() as $payslip) {
            $period = $payslip->run->period;

            if ($period === null) {
                continue;
            }

            $row = $this->recompute($payslip, $period->ends_on->toDateString(), (int) $period->year, (string) $payslip->run->run_type, $today);
            $amount = 0.0;

            foreach ($components as $code) {
                $amount += max(0.0, (float) $row[$code.'_diff']);
            }

            $amount = round($amount, 2);

            if ($amount < 0.01) {
                continue;
            }

            $sourceKey = $this->sourceKey($payslip);
            $name = __('payroll::tax_adjustment.refund_line', ['period' => $period->code]);

            if (! $this->oneOffs->record((string) $payslip->tabel_no, self::REFUND_CODE, $name, $amount, $payYear, $payMonth, $sourceKey, false, false)) {
                throw ValidationException::withMessages(['refundReason' => __('payroll::tax_adjustment.messages.not_ours')]);
            }

            activity('payroll')
                ->causedBy(auth()->user())
                ->withProperties([
                    'tabel_no' => (string) $payslip->tabel_no,
                    'source_payslip_id' => (int) $payslip->getKey(),
                    'period' => (string) $period->code,
                    'components' => $components,
                    'amount' => $amount,
                    'pay_year' => $payYear,
                    'pay_month' => $payMonth,
                    'reason' => $reason,
                ])
                ->event('tax_adjustment_refund_recorded')
                ->log('tax_adjustment_refund_recorded');

            $recorded++;
            $total += $amount;
        }

        return ['recorded' => $recorded, 'total' => round($total, 2)];
    }

    /**
     * İmtiyazın natura formasında gəlir sətirləri Benefits-in indiki qaydası ilə (məs. yemək kartının
     * gündəlik norması — DVX izahı 10.07.2026): norma tətbiq olunmazdan əvvəl ödənilmiş aylarda bütün
     * məbləğ DSMF, işsizlik və tibbi bazasına düşüb; hesabat artıq tutulanı göstərir, qaytarma
     * `recordRefunds` ilə mühasibin açıq qərarıdır.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function restateImputedIncome(Payslip $payslip, array $lines): array
    {
        if (! app()->bound(ImputedIncomeRestatement::class) || ! InstalledTables::has('payroll_one_off_earnings')) {
            return $lines;
        }

        $restatement = app(ImputedIncomeRestatement::class);
        $paid = PayrollOneOffEarning::query()
            ->where('tabel_no', $payslip->tabel_no)
            ->where('paid_payroll_run_id', $payslip->payroll_run_id)
            ->where('source_key', 'like', 'benefits:%')
            ->get(['source_key', 'code', 'amount']);

        foreach ($paid as $earning) {
            $restated = $restatement->restate((string) $earning->source_key, (string) $earning->code, (float) $earning->amount);
            $index = collect($lines)->search(fn (array $line): bool => $line['code'] === $earning->code && abs($line['amount'] - (float) $earning->amount) < 0.005);

            if ($restated === null || $index === false) {
                continue;
            }

            array_splice($lines, $index, 1, array_map(fn (array $line): array => ['kind' => 'earning'] + $line, $restated));
        }

        return $lines;
    }

    public function sourceKey(Payslip $payslip): string
    {
        return 'tax_adjustment:payslip:'.$payslip->getKey();
    }

    /**
     * Qayda tarixi: min(bu gün, ilin 31 dekabrı), ayın sonundan tez olmayaraq.
     */
    public function rulesDate(string $periodEnd, int $year, Carbon $today): string
    {
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $date = $today->lessThan($yearEnd) ? $today->copy()->startOfDay() : $yearEnd;
        $end = Carbon::parse($periodEnd)->startOfDay();

        return ($date->lessThan($end) ? $end : $date)->toDateString();
    }

    /**
     * İşçinin həmin tarixdəki təyinatının rejimi (bağlanmış təyinatlar da daxil, kilidli aylar üçün).
     */
    private function regimeOn(string $tabelNo, string $date, bool $activeOnly = false): ?int
    {
        $id = EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->whereIn('status', $activeOnly ? ['active'] : ['active', 'ended'])
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('regime_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array<string, bool>
     */
    private function recordedRefunds(): array
    {
        if (! InstalledTables::has('payroll_one_off_earnings')) {
            return [];
        }

        return DB::table('payroll_one_off_earnings')
            ->where('code', self::REFUND_CODE)
            ->pluck('source_key')
            ->mapWithKeys(fn ($key): array => [(string) $key => true])
            ->all();
    }

    /**
     * Hesabatın cəmi: komponent üzrə tutulmuş / düzgün / fərq.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, float>
     */
    public static function totals(array $rows): array
    {
        return collect(self::COMPONENTS)
            ->flatMap(fn (string $code): array => [
                $code.'_paid' => round((float) collect($rows)->sum($code.'_paid'), 2),
                $code.'_now' => round((float) collect($rows)->sum($code.'_now'), 2),
                $code.'_diff' => round((float) collect($rows)->sum($code.'_diff'), 2),
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public static function exportRows(array $rows): Collection
    {
        return collect($rows)->map(fn (array $row): array => array_map(
            fn ($value) => is_float($value) ? number_format($value, 2, '.', '') : $value,
            $row,
        ));
    }
}
