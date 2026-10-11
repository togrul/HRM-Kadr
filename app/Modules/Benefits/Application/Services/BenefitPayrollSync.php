<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\CompensationComponent;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeCompensationLine;
use App\Modules\Attendance\Contracts\PayrollWorkedDays;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use App\Modules\Benefits\Contracts\ImputedIncomeRestatement;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * İmtiyaz qoşulmalarını Payroll-un mövcud girişlərinə yazır — PayrollCalculator / StatutoryEngine
 * dəyişdirilmədən:
 *
 *  1. İşçinin payı → işçinin cari aktiv kompensasiyasında `benefit_employee_share` komponenti ilə
 *     təkrarlanan tutulma sətri (hər qoşulmaya bir sətir; qeyd `benefits#<id>` ilə tanınır).
 *     Payroll bu sətri digər könüllü tutulmalar kimi hər ay çıxır (davamiyyətə görə azaldılmır).
 *  2. İşəgötürənin ödədiyi və vergiyə və/və ya sosial sığortaya cəlb olunan hissə (VM m.98.2,
 *     «Sosial sığorta haqqında» Qanun m.15) → həmin ay üçün birdəfəlik gəlir (PayrollOneOffEarnings)
 *     və eyni məbləğdə mənfi, vergisiz əvəzləmə sətri. Nəticə: brüt/xalis nağd məbləğ dəyişmir,
 *     yalnız vergi və sığorta bazası imtiyazın dəyəri qədər artır. Aylıq qeyd mənbə açarı ilə
 *     idempotentdir; ay kilidlidirsə Payroll onu növbəti aya keçirir (hər iki sətir birlikdə).
 *
 *  3. Gündəlik norması olan paket (yemək kartı — VM m.119.2, DVX izahı 10.07.2026): işəgötürən payının
 *     norma × işçinin ay üzrə iş yerində olduğu gün sayı daxilindəki hissəsi heç yerə yazılmır; artıq hissə gəlir
 *     vergisinə və YALNIZ DSMF bazasına (`benefit_in_kind_dsmf` kodu — işsizlik və icbari tibbi sığorta
 *     bazasına düşmür) cəlb olunur. «Qaimə-fakturalı» paketdə və ya qoşulmada (`invoice_based` — eyni
 *     paketdə yalnız bəzi işçilər qaimə ilə alırsa) məbləğdən asılı olmayaraq heç nə yazılmır.
 *     Norması olmayan paketlər (məs. idman zalı) əvvəlki kimi `taxable` / `affects_social` bayraqlarına
 *     görə bütün sosial bazaya daxil olur. Ödənilmiş sətirlərə toxunulmur (PayrollOneOffEarnings), ona
 *     görə norma dəyişikliyi yalnız hələ ödənilməmiş cari ay üçün qüvvəyə minir.
 *
 * Bütün yollar idempotentdir: qoşulma yaranan/dəyişən/bitən anda və gündəlik `benefits:daily`
 * əmrində eyni nəticəni verir (HR kompensasiya sətirlərini əl ilə dəyişsə belə bərpa olunur).
 */
class BenefitPayrollSync implements ImputedIncomeRestatement
{
    public const LINE_MARKER = 'benefits#';

    /**
     * @return array{lines: int, imputed: int}
     */
    public function syncEmployee(string $tabelNo, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $enrollments = BenefitEnrollment::query()
            ->with('plan:id,name,taxable,affects_social,exempt_daily_norm,norm_is_invoice_based')
            ->where('tabel_no', $tabelNo)
            ->get();

        return [
            'lines' => $this->syncEmployeeShare($tabelNo, $enrollments, $today),
            'imputed' => $this->syncImputedIncome($tabelNo, $enrollments, $today),
        ];
    }

    /**
     * Ləğv edilmiş/geri alınmış qoşulmanın hələ ödənilməmiş aylıq gəlir sətirlərini geri çəkir.
     */
    public function withdrawImputed(BenefitEnrollment $enrollment, ?CarbonImmutable $today = null): void
    {
        $payroll = $this->payroll();

        if ($payroll === null) {
            return;
        }

        $today ??= CarbonImmutable::today();
        $month = CarbonImmutable::parse($enrollment->start_date)->startOfMonth();
        $last = $today->startOfMonth()->addMonth();

        for ($i = 0; $i < 36 && $month->lte($last); $i++, $month = $month->addMonth()) {
            $payroll->withdraw($this->sourceKey($enrollment, $month, 'value'));
            $payroll->withdraw($this->sourceKey($enrollment, $month, 'offset'));
        }
    }

    /**
     * Hər qoşulma üçün qüvvədə olan işçi payı = aktiv kompensasiyadakı bir sətir.
     *
     * @param  Collection<int, BenefitEnrollment>  $enrollments
     */
    private function syncEmployeeShare(string $tabelNo, Collection $enrollments, CarbonImmutable $today): int
    {
        if (! InstalledTables::has('employee_compensation_lines')) {
            return 0;
        }

        $on = $today->toDateString();
        $compensation = EmployeeCompensation::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $on)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $on))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        // Köhnə (bitmiş) kompensasiyalarda qalmış sətirlər tarixçədir — onlara toxunulmur.
        if ($compensation === null) {
            return 0;
        }

        /** @var array<int, float> $desired enrollment id → amount */
        $desired = [];
        $labels = [];
        foreach ($enrollments as $enrollment) {
            $amount = round((float) $enrollment->employee_amount, 2);

            if ($amount > 0 && $this->inForce($enrollment, $today)) {
                $desired[(int) $enrollment->id] = $amount;
                $labels[(int) $enrollment->id] = (string) $enrollment->plan?->name;
            }
        }

        $componentId = $this->employeeShareComponentId($desired !== []);

        if ($componentId === null) {
            return 0;
        }

        $existing = EmployeeCompensationLine::query()
            ->where('employee_compensation_id', $compensation->id)
            ->where('component_id', $componentId)
            ->where('note', 'like', self::LINE_MARKER.'%')
            ->get();

        foreach ($existing as $line) {
            $enrollmentId = $this->enrollmentIdFromNote((string) $line->getAttribute('note'));

            if ($enrollmentId === null || ! isset($desired[$enrollmentId])) {
                $line->delete();

                continue;
            }

            if (round((float) $line->getAttribute('amount'), 2) !== $desired[$enrollmentId] || $line->getAttribute('percent') !== null) {
                $line->forceFill(['amount' => $desired[$enrollmentId], 'percent' => null])->save();
            }

            unset($desired[$enrollmentId]);
        }

        foreach ($desired as $enrollmentId => $amount) {
            EmployeeCompensationLine::query()->create([
                'employee_compensation_id' => $compensation->id,
                'component_id' => $componentId,
                'amount' => $amount,
                'percent' => null,
                'note' => self::LINE_MARKER.$enrollmentId.' · '.$labels[$enrollmentId],
            ]);
        }

        return EmployeeCompensationLine::query()
            ->where('employee_compensation_id', $compensation->id)
            ->where('component_id', $componentId)
            ->where('note', 'like', self::LINE_MARKER.'%')
            ->count();
    }

    /**
     * Cari ay üçün natura formasında gəlir + əvəzləmə sətirləri.
     *
     * @param  Collection<int, BenefitEnrollment>  $enrollments
     */
    private function syncImputedIncome(string $tabelNo, Collection $enrollments, CarbonImmutable $today): int
    {
        $payroll = $this->payroll();

        if ($payroll === null) {
            return 0;
        }

        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $recorded = 0;

        foreach ($enrollments as $enrollment) {
            // Ayla kəsişməyən (məs. geriyə tarixlə bitirilmiş) qoşulmanın bu ay üçün əvvəl yazılmış
            // sətri də aşağıda geri çəkilir.
            $touchesMonth = CarbonImmutable::parse($enrollment->start_date)->lte($monthEnd)
                && ($enrollment->end_date === null || CarbonImmutable::parse($enrollment->end_date)->gte($monthStart));

            $plan = $enrollment->plan;
            $amount = round((float) $enrollment->employer_amount, 2);
            $treatment = $touchesMonth
                && $enrollment->status !== BenefitEnrollment::STATUS_CANCELLED
                && $plan !== null
                && $amount > 0
                ? $this->treatment($plan, $enrollment, $amount, $tabelNo, $monthStart)
                : null;

            $valueKey = $this->sourceKey($enrollment, $monthStart, 'value');
            $offsetKey = $this->sourceKey($enrollment, $monthStart, 'offset');

            if ($treatment === null || $plan === null) {
                $payroll->withdraw($valueKey);
                $payroll->withdraw($offsetKey);

                continue;
            }

            $name = (string) $plan->name;
            $amount = $treatment['amount'];
            $stored = $payroll->record(
                $tabelNo,
                $treatment['code'],
                __($treatment['line'], ['plan' => $name], 'az'),
                $amount,
                (int) $monthStart->year,
                (int) $monthStart->month,
                $valueKey,
                $treatment['taxable'],
                $treatment['affects_social'],
            );

            if (! $stored) {
                continue;
            }

            $payroll->record(
                $tabelNo,
                (string) config('benefits.payroll.offset_code', 'benefit_in_kind_offset'),
                __('benefits::dashboard.payroll.offset_line', ['plan' => $name], 'az'),
                -$amount,
                (int) $monthStart->year,
                (int) $monthStart->month,
                $offsetKey,
                false,
                false,
            );

            $recorded++;
        }

        return $recorded;
    }

    /**
     * Qoşulmanın bu ay üçün vergi/sığorta rejimi; null — heç nə yazılmır.
     *
     * @return array{amount: float, code: string, line: string, taxable: bool, affects_social: bool}|null
     */
    private function treatment(BenefitPlan $plan, BenefitEnrollment $enrollment, float $amount, string $tabelNo, CarbonImmutable $month): ?array
    {
        // Qaimə-fakturalı iaşə paket üzrə (hamı) və ya yalnız bu işçi üçün qeyd oluna bilər.
        if ($plan->norm_is_invoice_based || ($plan->exempt_daily_norm !== null && $enrollment->invoice_based)) {
            return null;
        }

        $norm = $plan->exempt_daily_norm;

        if ($norm === null) {
            return ($plan->taxable || $plan->affects_social) ? [
                'amount' => $amount,
                'code' => (string) config('benefits.payroll.imputed_code', 'benefit_in_kind'),
                'line' => 'benefits::dashboard.payroll.imputed_line',
                'taxable' => (bool) $plan->taxable,
                'affects_social' => (bool) $plan->affects_social,
            ] : null;
        }

        $exempt = round((float) $norm * $this->workingDays($tabelNo, $month), 2);
        $excess = round($amount - min($amount, $exempt), 2);

        if ($excess <= 0) {
            return null;
        }

        return [
            'amount' => $excess,
            'code' => (string) config('benefits.payroll.imputed_dsmf_code', 'benefit_in_kind_dsmf'),
            'line' => 'benefits::dashboard.payroll.imputed_norm_line',
            'taxable' => true,
            'affects_social' => true,
        ];
    }

    /**
     * Normanın vurulduğu gün sayı — işçinin iş yerində olduğu (yemək aldığı) iş günləri: ayın iş
     * günlərindən məzuniyyət, buraxılış/xəstəlik və ezamiyyət (gündəlik xərc yeməyi əhatə edir) günləri
     * çıxılır (Attendance `PayrollWorkedDays`). Ayın iş günləri Payroll-un proratasiyada işlətdiyi eyni
     * mənbədəndir (aylıq davamiyyət xülasəsi — `total_workdays`); xülasə yoxdursa — qlobal davamiyyət
     * təqvimi (qeydsiz günlər üçün B–C iş günü sayılır).
     */
    private function workingDays(string $tabelNo, CarbonImmutable $month): int
    {
        $away = app()->bound(PayrollWorkedDays::class)
            ? count(app(PayrollWorkedDays::class)->workdays($tabelNo, $month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString())['absent'])
            : 0;

        return max(0, $this->monthWorkdays($tabelNo, $month) - $away);
    }

    private function monthWorkdays(string $tabelNo, CarbonImmutable $month): int
    {
        if (app()->bound(PayrollAttendanceReadRepository::class)) {
            $summary = app(PayrollAttendanceReadRepository::class)->monthlyAbsence($tabelNo, (int) $month->year, (int) $month->month);

            if ($summary !== null && $summary['working_days'] > 0) {
                return (int) $summary['working_days'];
            }
        }

        $types = InstalledTables::has('attendance_calendars')
            ? AttendanceCalendar::query()
                ->where('scope_type', 'global')
                ->whereBetween('date', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()])
                ->get(['date', 'day_type'])
                ->mapWithKeys(fn (AttendanceCalendar $day): array => [$day->date->toDateString() => $day->day_type])
                ->all()
            : [];

        $count = 0;

        for ($day = $month->startOfMonth(); $day->lte($month->endOfMonth()); $day = $day->addDay()) {
            $count += match ($types[$day->toDateString()] ?? null) {
                'workday' => 1,
                'holiday', 'weekend' => 0,
                default => $day->isWeekend() ? 0 : 1,
            };
        }

        return $count;
    }

    /**
     * Compensation kataloqunda işçi payı komponenti. Kataloq Compensation modulunundur; bu sətir
     * yalnız ilk dəfə işçi payı yazılanda yaranır (tutulma, vergi bazasını azaltmır — işçi öz
     * payını vergidən sonrakı məbləğdən ödəyir). Mühasib onu kataloqda görür və GL kodu verə bilər.
     */
    private function employeeShareComponentId(bool $create): ?int
    {
        $code = (string) config('benefits.payroll.employee_share_component', 'benefit_employee_share');
        $id = CompensationComponent::query()->where('code', $code)->value('id');

        if ($id !== null || ! $create) {
            return $id !== null ? (int) $id : null;
        }

        return (int) CompensationComponent::query()->create([
            'code' => $code,
            'name' => 'İmtiyaz — işçi payı',
            'type' => 'deduction',
            'calc_type' => 'fixed',
            'taxable' => false,
            'affects_social' => false,
            'is_statutory' => false,
            'sort' => 900,
            'is_active' => true,
        ])->getKey();
    }

    /**
     * Yalnız köhnə qayda ilə yazılmış (`benefit_in_kind`, bütün məbləğ) dəyər sətirləri yenidən ifadə
     * olunur; norma ilə yazılmış sətir (`benefit_in_kind_dsmf`) artıq indiki qaydadadır.
     */
    public function restate(string $sourceKey, string $code, float $amount): ?array
    {
        if ($code !== (string) config('benefits.payroll.imputed_code', 'benefit_in_kind')
            || preg_match('/^benefits:(\d+):(\d{4})-(\d{2}):value$/', $sourceKey, $matches) !== 1) {
            return null;
        }

        $enrollment = BenefitEnrollment::query()->with('plan')->find((int) $matches[1]);

        if ($enrollment === null || $enrollment->plan === null) {
            return null;
        }

        $treatment = $this->treatment($enrollment->plan, $enrollment, $amount, (string) $enrollment->tabel_no, CarbonImmutable::create((int) $matches[2], (int) $matches[3], 1));

        return $treatment === null ? [] : [[
            'code' => $treatment['code'],
            'amount' => $treatment['amount'],
            'taxable' => $treatment['taxable'],
            'affects_social' => $treatment['affects_social'],
        ]];
    }

    public function inForce(BenefitEnrollment $enrollment, CarbonImmutable $on): bool
    {
        return $enrollment->status === BenefitEnrollment::STATUS_ACTIVE
            && CarbonImmutable::parse($enrollment->start_date)->lte($on)
            && ($enrollment->end_date === null || CarbonImmutable::parse($enrollment->end_date)->gte($on));
    }

    public function sourceKey(BenefitEnrollment $enrollment, CarbonImmutable $month, string $part): string
    {
        return 'benefits:'.$enrollment->id.':'.$month->format('Y-m').':'.$part;
    }

    private function enrollmentIdFromNote(string $note): ?int
    {
        if (preg_match('/^'.preg_quote(self::LINE_MARKER, '/').'(\d+)/', $note, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function payroll(): ?PayrollOneOffEarnings
    {
        return app()->bound(PayrollOneOffEarnings::class) && InstalledTables::has('payroll_one_off_earnings')
            ? app(PayrollOneOffEarnings::class)
            : null;
    }
}
