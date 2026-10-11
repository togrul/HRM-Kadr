<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Models\CompensationComponent;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeCompensationLine;
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
 * Bütün yollar idempotentdir: qoşulma yaranan/dəyişən/bitən anda və gündəlik `benefits:daily`
 * əmrində eyni nəticəni verir (HR kompensasiya sətirlərini əl ilə dəyişsə belə bərpa olunur).
 */
class BenefitPayrollSync
{
    public const LINE_MARKER = 'benefits#';

    /**
     * @return array{lines: int, imputed: int}
     */
    public function syncEmployee(string $tabelNo, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $enrollments = BenefitEnrollment::query()
            ->with('plan:id,name,taxable,affects_social')
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
            $qualifies = $touchesMonth
                && $enrollment->status !== BenefitEnrollment::STATUS_CANCELLED
                && $plan !== null
                && $amount > 0
                && ($plan->taxable || $plan->affects_social);

            $valueKey = $this->sourceKey($enrollment, $monthStart, 'value');
            $offsetKey = $this->sourceKey($enrollment, $monthStart, 'offset');

            if (! $qualifies) {
                $payroll->withdraw($valueKey);
                $payroll->withdraw($offsetKey);

                continue;
            }

            $name = (string) $plan->name;
            $stored = $payroll->record(
                $tabelNo,
                (string) config('benefits.payroll.imputed_code', 'benefit_in_kind'),
                __('benefits::dashboard.payroll.imputed_line', ['plan' => $name], 'az'),
                $amount,
                (int) $monthStart->year,
                (int) $monthStart->month,
                $valueKey,
                (bool) $plan->taxable,
                (bool) $plan->affects_social,
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
