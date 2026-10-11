<?php

namespace App\Modules\Benefits\Livewire\Tabs;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Personnel;
use App\Modules\Benefits\Application\Services\BenefitEligibilityService;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Support\Livewire\SearchesPersonnel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

/**
 * Qoşulmalar: HR işçini paketə qoşur (uyğunluq yoxlaması, əsaslandırma ilə istisna), ailə
 * üzvlərini dəyişir, qoşulmanı bitirir və ya səhv qoşulmanı ləğv edir. Siyahı və hər əməliyyat
 * istifadəçinin struktur görünürlüyü ilə məhdudlaşır.
 */
class EnrollmentsTab extends BenefitsTab
{
    use SearchesPersonnel;
    use WithPagination;

    public string $search = '';

    public string $planFilter = '';

    public string $statusFilter = 'active';

    /** @var array{plan_id: string, start_date: string, kinship_ids: list<string>, override: bool, note: string} */
    public array $enrollForm = ['plan_id' => '', 'start_date' => '', 'kinship_ids' => [], 'override' => false, 'note' => ''];

    #[Locked]
    public ?int $editingEnrollmentId = null;

    /** @var list<string> */
    public array $dependentIds = [];

    public string $endDate = '';

    protected function viewName(): string
    {
        return 'enrollments';
    }

    protected function panels(): array
    {
        return ['enroll'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->clearPersonnel();
        $this->editingEnrollmentId = null;
        $this->enrollForm = [
            'plan_id' => '',
            'start_date' => CarbonImmutable::today()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            'kinship_ids' => [],
            'override' => false,
            'note' => '',
        ];
        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function enrollments(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return $this->scopeByPersonnel(BenefitEnrollment::query())
            ->with(['plan:id,name,type,currency,allows_dependents', 'personnel:id,tabel_no,surname,name,patronymic,structure_id'])
            ->withCount('dependents')
            ->when(in_array($this->statusFilter, ['active', 'ended', 'cancelled'], true), fn ($query) => $query->where('status', $this->statusFilter))
            ->when((int) $this->planFilter > 0, fn ($query) => $query->where('benefit_plan_id', (int) $this->planFilter))
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('tabel_no', 'like', "%{$term}%")
                ->orWhereHas('personnel', fn ($people) => $people->where('surname', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"))))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * @return Collection<int, BenefitPlan>
     */
    #[Computed]
    public function planOptions(): Collection
    {
        return BenefitPlan::query()->orderBy('sort')->orderBy('name')->get(['id', 'name', 'type', 'is_active', 'allows_dependents', 'max_dependents']);
    }

    /**
     * @return list<array{id: int, full_name: string, relation: ?string, birthdate: ?string}>
     */
    #[Computed]
    public function kinshipOptions(): array
    {
        $tabelNo = $this->editingEnrollmentId !== null
            ? $this->findEnrollment($this->editingEnrollmentId)->tabel_no
            : $this->visibleSelectedTabel();

        return $tabelNo ? app(BenefitEnrollmentService::class)->kinshipOptions($tabelNo) : [];
    }

    /**
     * Seçilmiş işçi + paket üçün uyğunsuzluq səbəbləri (ekranda xəbərdarlıq).
     *
     * @return list<string>|null null — hələ seçilməyib
     */
    #[Computed]
    public function eligibilityReasons(): ?array
    {
        $tabelNo = $this->visibleSelectedTabel();
        $plan = (int) $this->enrollForm['plan_id'] > 0 ? BenefitPlan::query()->find((int) $this->enrollForm['plan_id']) : null;

        if ($tabelNo === null || $plan === null) {
            return null;
        }

        $personnel = Personnel::query()->where('tabel_no', $tabelNo)->first();
        $start = $this->enrollForm['start_date'] !== '' ? CarbonImmutable::parse($this->enrollForm['start_date']) : null;

        return $personnel ? app(BenefitEligibilityService::class)->reasons($plan, $personnel, $start) : null;
    }

    public function saveEnrollment(BenefitEnrollmentService $service): void
    {
        $this->guardManage();
        $tabelNo = $this->visibleSelectedTabel();
        abort_unless($tabelNo !== null, 422);

        $data = $this->validate([
            'enrollForm.plan_id' => ['required', 'integer', Rule::exists('benefit_plans', 'id')],
            'enrollForm.start_date' => ['required', 'date'],
            'enrollForm.kinship_ids' => ['array'],
            'enrollForm.kinship_ids.*' => ['integer'],
            'enrollForm.override' => ['boolean'],
            'enrollForm.note' => ['nullable', 'string', 'max:1000'],
        ], attributes: $this->fieldLabels([
            'enrollForm.plan_id' => 'plan',
            'enrollForm.start_date' => 'start_date',
            'enrollForm.note' => 'note',
        ]))['enrollForm'];

        $service->enroll(
            BenefitPlan::query()->findOrFail((int) $data['plan_id']),
            $tabelNo,
            (string) $data['start_date'],
            array_map('intval', (array) ($data['kinship_ids'] ?? [])),
            BenefitEnrollmentService::SOURCE_HR,
            (bool) ($data['override'] ?? false),
            $data['note'] ?? null,
        );

        $this->closePanel();
        $this->clearPersonnel();
        unset($this->enrollments);
        $this->announce('enrolled');
    }

    public function editDependents(int $id): void
    {
        $this->guardManage();
        $enrollment = $this->findEnrollment($id);

        $this->editingEnrollmentId = $enrollment->id;
        $this->dependentIds = $enrollment->dependents()->pluck('personnel_kinship_id')->filter()->map(fn ($kin): string => (string) $kin)->values()->all();
        unset($this->kinshipOptions);
        $this->panel = 'dependents';
        $this->resetValidation();
    }

    public function saveDependents(BenefitEnrollmentService $service): void
    {
        $this->guardManage();
        abort_unless($this->editingEnrollmentId !== null, 422);

        $this->validate(['dependentIds' => ['array'], 'dependentIds.*' => ['integer']]);
        $service->updateDependents($this->findEnrollment($this->editingEnrollmentId), array_map('intval', $this->dependentIds));

        $this->editingEnrollmentId = null;
        $this->closePanel();
        unset($this->enrollments);
        $this->announce('saved');
    }

    public function startEnd(int $id): void
    {
        $this->guardManage();
        $enrollment = $this->findEnrollment($id);

        $this->editingEnrollmentId = $enrollment->id;
        $this->endDate = CarbonImmutable::today()->endOfMonth()->toDateString();
        $this->panel = 'end';
        $this->resetValidation();
    }

    public function saveEnd(BenefitEnrollmentService $service): void
    {
        $this->guardManage();
        abort_unless($this->editingEnrollmentId !== null, 422);

        $this->validate(['endDate' => ['required', 'date']], attributes: $this->fieldLabels(['endDate' => 'end_date']));
        $service->end($this->findEnrollment($this->editingEnrollmentId), $this->endDate);

        $this->editingEnrollmentId = null;
        $this->closePanel();
        unset($this->enrollments);
        $this->announce('ended');
    }

    public function cancelEnrollment(int $id, BenefitEnrollmentService $service): void
    {
        $this->guardManage();
        $service->cancel($this->findEnrollment($id));
        unset($this->enrollments);
        $this->announce('cancelled');
    }

    public function closePanel(): void
    {
        parent::closePanel();
        $this->editingEnrollmentId = null;
    }

    private function findEnrollment(int $id): BenefitEnrollment
    {
        /** @var BenefitEnrollment */
        return $this->scopeByPersonnel(BenefitEnrollment::query())->with('plan')->findOrFail($id);
    }

    private function visibleSelectedTabel(): ?string
    {
        return $this->tabelInScope($this->selectedTabelNo) ? $this->selectedTabelNo : null;
    }
}
