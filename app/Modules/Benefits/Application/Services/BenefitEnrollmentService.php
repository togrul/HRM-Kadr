<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Models\BenefitEnrollmentDependent;
use App\Models\BenefitPlan;
use App\Models\Personnel;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Qoşulmanın həyat dövrü: HR və ya işçinin özü (Şəxsi kabinet) paket seçir, ailə üzvlərini
 * (qohumluq qeydlərindən) əlavə edir, qoşulmanı bitirir və ya səhv qoşulmanı ləğv edir.
 * Hər dəyişiklikdən sonra Payroll girişləri yenilənir (BenefitPayrollSync).
 */
class BenefitEnrollmentService
{
    public const SOURCE_HR = 'hr';

    public const SOURCE_SELF = 'self_service';

    public const SOURCE_RENEWAL = 'renewal';

    public function __construct(
        private readonly BenefitEligibilityService $eligibility,
        private readonly BenefitPayrollSync $payroll,
    ) {}

    /**
     * @param  list<int>  $kinshipIds  əməkdaşın personnel_kinships qeydləri
     *
     * @throws ValidationException
     */
    public function enroll(
        BenefitPlan $plan,
        string $tabelNo,
        string $startDate,
        array $kinshipIds = [],
        string $source = self::SOURCE_HR,
        bool $overrideEligibility = false,
        ?string $note = null,
        ?int $renewalOfId = null,
    ): BenefitEnrollment {
        $personnel = Personnel::query()->where('tabel_no', $tabelNo)->first();
        $start = CarbonImmutable::parse($startDate)->startOfDay();

        if ($personnel === null) {
            throw ValidationException::withMessages(['tabel_no' => __('benefits::dashboard.messages.personnel_missing')]);
        }

        if (! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.plan_inactive')]);
        }

        if ($source === self::SOURCE_SELF && ! $plan->self_service) {
            throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.plan_not_self_service')]);
        }

        $reasons = $this->eligibility->reasons($plan, $personnel, $start);
        if ($reasons !== [] && ! $overrideEligibility) {
            throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.not_eligible', [
                'reasons' => collect($reasons)->map(fn (string $reason): string => __('benefits::dashboard.eligibility.reasons.'.$reason))->implode(', '),
            ])]);
        }

        if ($overrideEligibility && $reasons !== [] && trim((string) $note) === '') {
            throw ValidationException::withMessages(['note' => __('benefits::dashboard.messages.override_needs_note')]);
        }

        $this->guardOverlap($plan, $tabelNo, $start);

        $dependents = $this->dependentsFor($plan, $tabelNo, $kinshipIds);
        $amounts = $this->amounts($plan, count($dependents));

        $enrollment = DB::transaction(function () use ($plan, $tabelNo, $start, $source, $overrideEligibility, $reasons, $note, $renewalOfId, $dependents, $amounts): BenefitEnrollment {
            $enrollment = BenefitEnrollment::query()->create([
                'benefit_plan_id' => $plan->id,
                'tabel_no' => $tabelNo,
                'status' => BenefitEnrollment::STATUS_ACTIVE,
                'start_date' => $start->toDateString(),
                'end_date' => null,
                'source' => $source,
                'employee_amount' => $amounts['employee'],
                'employer_amount' => $amounts['employer'],
                'currency' => $plan->currency,
                'eligibility_overridden' => $overrideEligibility && $reasons !== [],
                'renewal_of_id' => $renewalOfId,
                'enrolled_by' => auth()->id(),
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            foreach ($dependents as $dependent) {
                $enrollment->dependents()->create($dependent);
            }

            return $enrollment;
        });

        $this->payroll->syncEmployee($tabelNo);

        return $enrollment->load(['plan', 'dependents']);
    }

    /**
     * Ailə üzvlərinin siyahısını dəyişir və məbləği yenidən hesablayır (dəyişiklik növbəti
     * Payroll sinxronundan qüvvədədir).
     *
     * @param  list<int>  $kinshipIds
     *
     * @throws ValidationException
     */
    public function updateDependents(BenefitEnrollment $enrollment, array $kinshipIds): BenefitEnrollment
    {
        if ($enrollment->status !== BenefitEnrollment::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['enrollment' => __('benefits::dashboard.messages.enrollment_closed')]);
        }

        $plan = $enrollment->plan ?? BenefitPlan::query()->findOrFail($enrollment->benefit_plan_id);
        $dependents = $this->dependentsFor($plan, $enrollment->tabel_no, $kinshipIds);
        $amounts = $this->amounts($plan, count($dependents));

        DB::transaction(function () use ($enrollment, $dependents, $amounts): void {
            $keep = array_filter(array_column($dependents, 'personnel_kinship_id'));

            $enrollment->dependents()
                ->where(fn ($query) => $query->whereNull('personnel_kinship_id')->orWhereNotIn('personnel_kinship_id', $keep === [] ? [0] : $keep))
                ->get()
                ->each(fn (BenefitEnrollmentDependent $dependent) => $dependent->delete());

            $existing = $enrollment->dependents()->pluck('personnel_kinship_id')->map(fn ($id): int => (int) $id)->all();

            foreach ($dependents as $dependent) {
                if (! in_array((int) $dependent['personnel_kinship_id'], $existing, true)) {
                    $enrollment->dependents()->create($dependent);
                }
            }

            $enrollment->update(['employee_amount' => $amounts['employee'], 'employer_amount' => $amounts['employer']]);
        });

        $this->payroll->syncEmployee($enrollment->tabel_no);

        return $enrollment->refresh()->load(['plan', 'dependents']);
    }

    /**
     * Qoşulmanı tarixdən bitirir (həmin gün daxil). Tarix bu gündən əvvəldirsə dərhal «ended» olur.
     *
     * @throws ValidationException
     */
    public function end(BenefitEnrollment $enrollment, string $endDate, string $reason = 'manual'): BenefitEnrollment
    {
        if ($enrollment->status !== BenefitEnrollment::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['enrollment' => __('benefits::dashboard.messages.enrollment_closed')]);
        }

        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lt(CarbonImmutable::parse($enrollment->start_date))) {
            throw ValidationException::withMessages(['end_date' => __('benefits::dashboard.messages.end_before_start')]);
        }

        $enrollment->update([
            'end_date' => $end->toDateString(),
            'end_reason' => $reason,
            'status' => $end->lt(CarbonImmutable::today()) ? BenefitEnrollment::STATUS_ENDED : BenefitEnrollment::STATUS_ACTIVE,
        ]);

        $this->payroll->syncEmployee($enrollment->tabel_no);

        return $enrollment->refresh();
    }

    /**
     * Səhvən yaradılmış qoşulma: heç vaxt qüvvədə olmamış sayılır, ödənilməmiş sətirlər geri çəkilir.
     */
    public function cancel(BenefitEnrollment $enrollment, ?string $note = null): BenefitEnrollment
    {
        $enrollment->update([
            'status' => BenefitEnrollment::STATUS_CANCELLED,
            'end_reason' => 'cancelled',
            'note' => trim(implode(' — ', array_filter([(string) $enrollment->note, trim((string) $note)]))) ?: null,
        ]);

        $this->payroll->withdrawImputed($enrollment);
        $this->payroll->syncEmployee($enrollment->tabel_no);

        return $enrollment->refresh();
    }

    /**
     * İşçinin seçim pəncərəsindəki qərarı (yenilə / imtina et).
     *
     * @throws ValidationException
     */
    public function decideRenewal(BenefitEnrollment $enrollment, string $decision): BenefitEnrollment
    {
        if (! in_array($decision, ['renew', 'opt_out'], true)) {
            throw ValidationException::withMessages(['decision' => __('benefits::dashboard.messages.invalid_decision')]);
        }

        $window = $enrollment->renewalWindow;

        if ($window === null || $window->status !== 'open') {
            throw ValidationException::withMessages(['decision' => __('benefits::dashboard.messages.window_not_open')]);
        }

        $enrollment->update(['renewal_decision' => $decision]);

        return $enrollment->refresh();
    }

    /**
     * Aylıq məbləğ: işçinin özü + ailə üzvləri, hər biri işəgötürən/işçi payına bölünür.
     *
     * @return array{employee: string, employer: string, total: string}
     */
    public function amounts(BenefitPlan $plan, int $dependentCount): array
    {
        $hundred = BigDecimal::of(100);
        $own = BigDecimal::of((string) $plan->monthly_cost);
        $ownEmployee = $own->multipliedBy($hundred->minus(BigDecimal::of((string) $plan->employer_share_percent)))->dividedBy($hundred, 4, RoundingMode::HalfUp);

        $dependentTotal = BigDecimal::of((string) $plan->dependent_monthly_cost)->multipliedBy($dependentCount);
        $dependentEmployee = $dependentTotal->multipliedBy($hundred->minus(BigDecimal::of((string) $plan->dependent_employer_share_percent)))->dividedBy($hundred, 4, RoundingMode::HalfUp);

        $total = $own->plus($dependentTotal)->toScale(2, RoundingMode::HalfUp);
        $employee = $ownEmployee->plus($dependentEmployee)->toScale(2, RoundingMode::HalfUp);

        return [
            'employee' => (string) $employee,
            'employer' => (string) $total->minus($employee),
            'total' => (string) $total,
        ];
    }

    /**
     * Əməkdaşın qohumluq qeydləri (ailə üzvü seçimi üçün).
     *
     * @return list<array{id: int, full_name: string, relation: ?string, birthdate: ?string}>
     */
    public function kinshipOptions(string $tabelNo): array
    {
        $locale = in_array(app()->getLocale(), ['az', 'en', 'ru'], true) ? app()->getLocale() : 'az';

        return DB::table('personnel_kinships')
            ->leftJoin('kinships', 'kinships.id', '=', 'personnel_kinships.kinship_id')
            ->where('personnel_kinships.tabel_no', $tabelNo)
            ->orderBy('personnel_kinships.id')
            ->get(['personnel_kinships.id', 'personnel_kinships.fullname', 'personnel_kinships.birthdate', 'kinships.name_'.$locale.' as relation'])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'full_name' => (string) $row->fullname,
                'relation' => $row->relation !== null ? (string) $row->relation : null,
                'birthdate' => $row->birthdate !== null ? (string) $row->birthdate : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $kinshipIds
     * @return list<array{personnel_kinship_id: int, full_name: string, relation: ?string, birthdate: ?string}>
     *
     * @throws ValidationException
     */
    private function dependentsFor(BenefitPlan $plan, string $tabelNo, array $kinshipIds): array
    {
        $kinshipIds = array_values(array_unique(array_filter(array_map('intval', $kinshipIds), static fn (int $id): bool => $id > 0)));

        if ($kinshipIds === []) {
            return [];
        }

        if (! $plan->allows_dependents) {
            throw ValidationException::withMessages(['dependents' => __('benefits::dashboard.messages.dependents_not_allowed')]);
        }

        if ($plan->max_dependents !== null && count($kinshipIds) > $plan->max_dependents) {
            throw ValidationException::withMessages(['dependents' => __('benefits::dashboard.messages.too_many_dependents', ['max' => $plan->max_dependents])]);
        }

        $options = collect($this->kinshipOptions($tabelNo))->keyBy('id');

        return array_map(function (int $id) use ($options): array {
            $kin = $options->get($id);

            // Başqa əməkdaşın qohumu seçilə bilməz.
            if ($kin === null) {
                throw ValidationException::withMessages(['dependents' => __('benefits::dashboard.messages.dependent_unknown')]);
            }

            return [
                'personnel_kinship_id' => $id,
                'full_name' => $kin['full_name'],
                'relation' => $kin['relation'],
                'birthdate' => $kin['birthdate'] !== null ? CarbonImmutable::parse($kin['birthdate'])->toDateString() : null,
            ];
        }, $kinshipIds);
    }

    /**
     * Eyni paketdə (və «tək seçimli» növdə — məs. tibbi A və B) üst-üstə düşən aktiv qoşulma olmaz.
     *
     * @throws ValidationException
     */
    private function guardOverlap(BenefitPlan $plan, string $tabelNo, CarbonImmutable $start): void
    {
        $conflict = BenefitEnrollment::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $start->toDateString()))
            ->where(function ($query) use ($plan): void {
                $query->where('benefit_plan_id', $plan->id);

                if (BenefitTypes::exclusive($plan->type)) {
                    $query->orWhereHas('plan', fn ($plans) => $plans->where('type', $plan->type));
                }
            })
            ->with('plan:id,name')
            ->first();

        if ($conflict !== null) {
            throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.overlap', ['plan' => (string) $conflict->plan?->name])]);
        }
    }
}
