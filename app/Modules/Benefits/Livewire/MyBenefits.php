<?php

namespace App\Modules\Benefits\Livewire;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Personnel;
use App\Modules\Benefits\Application\Services\BenefitEligibilityService;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Modules\Benefits\Application\Services\BenefitTypes;
use App\Services\UserPersonnelLinkResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Şəxsi kabinet → «İmtiyazlarım»: işçinin öz paketləri və ailə üzvləri, uyğun olduğu paketlərə
 * özü qoşulması, yenilənmə pəncərəsində qərarı (yenilə / imtina / başqa paketə keç) və total
 * rewards xülasəsi. Əməkdaş HƏMİŞƏ serverdə daxil olmuş istifadəçinin bağından müəyyən olunur
 * (bindOwnPersonnel) — kənardan ötürülən id yalnız yoxlanılır.
 *
 * @property-read Personnel|null $personnel
 * @property-read Collection<int, BenefitEnrollment> $enrollments
 * @property-read Collection<int, BenefitPlan> $availablePlans
 * @property-read Collection<int, BenefitPlan> $switchOptions
 * @property-read list<array{id: int, full_name: string, relation: ?string, birthdate: ?string}> $kinshipOptions
 */
class MyBenefits extends Component
{
    #[Locked]
    public ?int $personnelId = null;

    public string $panel = '';

    /** @var array{plan_id: string, start_date: string, kinship_ids: list<string>} */
    public array $form = ['plan_id' => '', 'start_date' => '', 'kinship_ids' => []];

    #[Locked]
    public ?int $switchFromId = null;

    #[Locked]
    public ?int $dependentsForId = null;

    /** @var list<string> */
    public array $dependentIds = [];

    public function mount(?int $personnelId = null): void
    {
        abort_unless(app(BenefitsAvailability::class)->enabled(), 404);
        abort_unless(Auth::user()?->can('show-my-hr') ?? false, 403);
        $this->bindOwnPersonnel($personnelId);
    }

    public function hydrate(): void
    {
        abort_unless($this->ownPersonnelId() === $this->personnelId, 403);
    }

    #[Computed]
    public function personnel(): ?Personnel
    {
        return Personnel::query()->find($this->personnelId);
    }

    /**
     * @return Collection<int, BenefitEnrollment>
     */
    #[Computed]
    public function enrollments(): Collection
    {
        $tabelNo = $this->personnel?->tabel_no;

        if (! $tabelNo) {
            return collect();
        }

        return BenefitEnrollment::query()
            ->with(['plan', 'dependents', 'renewalWindow'])
            ->where('tabel_no', $tabelNo)
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Özü seçə biləcəyi paketlər (uyğun + özünəxidmət açıq + artıq qoşulmadığı).
     *
     * @return Collection<int, BenefitPlan>
     */
    #[Computed]
    public function availablePlans(): Collection
    {
        $personnel = $this->personnel;

        if ($personnel === null) {
            return collect();
        }

        $taken = $this->enrollments->filter(fn (BenefitEnrollment $e): bool => $e->end_date === null);
        $takenPlans = $taken->pluck('benefit_plan_id')->map(fn ($id): int => (int) $id)->all();
        $takenTypes = $taken->map(fn (BenefitEnrollment $e): string => (string) $e->plan?->type)->filter(fn (string $type): bool => BenefitTypes::exclusive($type))->all();

        return app(BenefitEligibilityService::class)
            ->eligiblePlans($personnel, CarbonImmutable::today(), true)
            ->reject(fn (BenefitPlan $plan): bool => in_array((int) $plan->id, $takenPlans, true) || in_array($plan->type, $takenTypes, true))
            ->values();
    }

    /**
     * @return list<array{id: int, full_name: string, relation: ?string, birthdate: ?string}>
     */
    #[Computed]
    public function kinshipOptions(): array
    {
        $tabelNo = $this->personnel?->tabel_no;

        return $tabelNo ? app(BenefitEnrollmentService::class)->kinshipOptions($tabelNo) : [];
    }

    /**
     * Pəncərədə keçid üçün eyni növdən uyğun paketlər.
     *
     * @return Collection<int, BenefitPlan>
     */
    #[Computed]
    public function switchOptions(): Collection
    {
        $from = $this->switchFromId ? $this->ownEnrollment($this->switchFromId) : null;
        $personnel = $this->personnel;

        if ($from === null || $personnel === null || $from->renewalWindow === null) {
            return collect();
        }

        $start = CarbonImmutable::parse($from->renewalWindow->contract_end)->addDay();

        return app(BenefitEligibilityService::class)
            ->eligiblePlans($personnel, $start, true)
            ->filter(fn (BenefitPlan $plan): bool => $plan->type === $from->plan?->type && (int) $plan->id !== (int) $from->benefit_plan_id)
            ->values();
    }

    public function startEnroll(int $planId): void
    {
        $plan = $this->availablePlans->firstWhere('id', $planId);
        abort_unless($plan !== null, 403);

        $this->switchFromId = null;
        $this->form = [
            'plan_id' => (string) $plan->id,
            'start_date' => CarbonImmutable::today()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'kinship_ids' => [],
        ];
        $this->panel = 'enroll';
        $this->resetValidation();
    }

    public function enroll(BenefitEnrollmentService $service): void
    {
        $tabelNo = (string) $this->personnel?->tabel_no;
        abort_unless($tabelNo !== '', 404);

        $data = $this->validate([
            'form.plan_id' => ['required', 'integer'],
            'form.start_date' => ['required', 'date', 'after_or_equal:today'],
            'form.kinship_ids' => ['array'],
            'form.kinship_ids.*' => ['integer'],
        ], attributes: [
            'form.plan_id' => __('benefits::dashboard.fields.plan'),
            'form.start_date' => __('benefits::dashboard.fields.start_date'),
        ])['form'];

        $plan = $this->switchFromId !== null
            ? $this->switchOptions->firstWhere('id', (int) $data['plan_id'])
            : $this->availablePlans->firstWhere('id', (int) $data['plan_id']);
        abort_unless($plan instanceof BenefitPlan, 403);

        DB::transaction(function () use ($service, $plan, $tabelNo, $data): void {
            $start = (string) $data['start_date'];

            if ($this->switchFromId !== null) {
                $from = $this->ownEnrollment($this->switchFromId);
                abort_unless($from?->renewalWindow !== null && $from->renewalWindow->status === 'open', 403);
                $contractEnd = CarbonImmutable::parse($from->renewalWindow->contract_end);
                $service->decideRenewal($from, 'opt_out');
                $service->end($from, $contractEnd->toDateString(), 'switch');
                $start = $contractEnd->addDay()->toDateString();
            }

            $service->enroll($plan, $tabelNo, $start, array_map('intval', (array) ($data['kinship_ids'] ?? [])), BenefitEnrollmentService::SOURCE_SELF);
        });

        $this->closePanel();
        $this->dispatch('notify', type: 'success', message: __('benefits::dashboard.messages.enrolled'));
    }

    public function startSwitch(int $enrollmentId): void
    {
        $enrollment = $this->ownEnrollment($enrollmentId);
        abort_unless($enrollment?->renewalWindow !== null && $enrollment->renewalWindow->status === 'open', 403);

        $this->switchFromId = $enrollment->id;
        unset($this->switchOptions);
        $firstOption = $this->switchOptions->first();
        $this->form = [
            'plan_id' => $firstOption !== null ? (string) $firstOption->id : '',
            'start_date' => CarbonImmutable::parse($enrollment->renewalWindow->contract_end)->addDay()->toDateString(),
            'kinship_ids' => $enrollment->dependents->pluck('personnel_kinship_id')->filter()->map(fn ($id): string => (string) $id)->values()->all(),
        ];
        $this->panel = 'switch';
        $this->resetValidation();
    }

    public function decide(int $enrollmentId, string $decision, BenefitEnrollmentService $service): void
    {
        $enrollment = $this->ownEnrollment($enrollmentId);
        abort_unless($enrollment !== null, 404);

        $service->decideRenewal($enrollment, $decision);
        unset($this->enrollments);
        $this->dispatch('notify', type: 'success', message: __('benefits::dashboard.messages.decision_saved'));
    }

    public function editDependents(int $enrollmentId): void
    {
        $enrollment = $this->ownEnrollment($enrollmentId);
        abort_unless($enrollment !== null && (bool) $enrollment->plan?->allows_dependents, 403);

        $this->dependentsForId = $enrollment->id;
        $this->dependentIds = $enrollment->dependents->pluck('personnel_kinship_id')->filter()->map(fn ($id): string => (string) $id)->values()->all();
        $this->panel = 'dependents';
        $this->resetValidation();
    }

    public function saveDependents(BenefitEnrollmentService $service): void
    {
        $enrollment = $this->dependentsForId ? $this->ownEnrollment($this->dependentsForId) : null;
        abort_unless($enrollment !== null, 404);

        $this->validate(['dependentIds' => ['array'], 'dependentIds.*' => ['integer']]);
        $service->updateDependents($enrollment, array_map('intval', $this->dependentIds));

        $this->closePanel();
        $this->dispatch('notify', type: 'success', message: __('benefits::dashboard.messages.saved'));
    }

    public function closePanel(): void
    {
        $this->panel = '';
        $this->switchFromId = null;
        $this->dependentsForId = null;
        $this->resetValidation();
        unset($this->enrollments, $this->availablePlans);
    }

    public function render(): View
    {
        return view('benefits::livewire.my-benefits', [
            'money' => fn ($value): string => number_format((float) $value, 2, ',', ' '),
        ]);
    }

    /**
     * Əməkdaşı daxil olmuş istifadəçinin öz bağından müəyyən edir; başqa kartın id-si 403.
     */
    private function bindOwnPersonnel(?int $requested): void
    {
        $own = $this->ownPersonnelId();

        abort_if($requested !== null && $requested !== $own, 403);
        abort_unless($own !== null, 404);

        $this->personnelId = $own;
    }

    private function ownPersonnelId(): ?int
    {
        $id = app(UserPersonnelLinkResolver::class)->resolve(Auth::user());

        return $id ? (int) $id : null;
    }

    private function ownEnrollment(int $id): ?BenefitEnrollment
    {
        $tabelNo = $this->personnel?->tabel_no;

        return $tabelNo
            ? BenefitEnrollment::query()->with(['plan', 'dependents', 'renewalWindow'])->where('tabel_no', $tabelNo)->find($id)
            : null;
    }
}
