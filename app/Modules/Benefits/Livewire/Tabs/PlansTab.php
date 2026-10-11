<?php

namespace App\Modules\Benefits\Livewire\Tabs;

use App\Models\BenefitPlan;
use App\Modules\Benefits\Application\Services\BenefitPlanService;
use App\Modules\Benefits\Application\Services\BenefitTypes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

/**
 * Paket kataloqu: növ, aylıq dəyər, işəgötürən/işçi payı, vergi rejimi, provayder, müqavilə
 * dövrü və uyğunluq qaydaları.
 */
class PlansTab extends BenefitsTab
{
    use WithPagination;

    public string $planSearch = '';

    #[Locked]
    public ?int $editingPlanId = null;

    /** @var array<string, mixed> */
    public array $planForm = [];

    public function mount(): void
    {
        parent::mount();
        $this->planForm = $this->blankForm('medical_insurance');
    }

    protected function viewName(): string
    {
        return 'plans';
    }

    protected function panels(): array
    {
        return ['plan'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->editingPlanId = null;
        $this->planForm = $this->blankForm('medical_insurance');
        $this->resetValidation();
    }

    public function updatedPlanSearch(): void
    {
        $this->resetPage();
    }

    /** Yeni paketdə növ dəyişəndə standart vergi rejimi və ailə üzvü seçimi doldurulur. */
    public function updatedPlanFormType(string $type): void
    {
        if ($this->editingPlanId !== null || ! BenefitTypes::exists($type)) {
            return;
        }

        $defaults = BenefitTypes::defaults($type);
        $this->planForm['taxable'] = $defaults['taxable'];
        $this->planForm['affects_social'] = $defaults['affects_social'];
        $this->planForm['tax_basis'] = $defaults['basis'];
        $this->planForm['allows_dependents'] = $defaults['allows_dependents'];
    }

    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        $term = trim($this->planSearch);

        return BenefitPlan::query()
            ->withCount(['enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active')])
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('provider', 'like', "%{$term}%")))
            ->orderBy('sort')
            ->orderBy('name')
            ->paginate(15);
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function positionOptions(): array
    {
        return DB::table('positions')->orderBy('name')->get(['id', 'name'])
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'label' => (string) $row->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function structureOptions(): array
    {
        return DB::table('structures')->orderBy('code')->orderBy('name')->get(['id', 'name'])
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'label' => (string) $row->name])
            ->all();
    }

    public function editPlan(int $id): void
    {
        $this->guardManage();
        $plan = BenefitPlan::query()->findOrFail($id);
        $rules = (array) ($plan->eligibility ?? []);

        $this->editingPlanId = $plan->id;
        $this->planForm = [
            'code' => $plan->code,
            'name' => $plan->name,
            'type' => $plan->type,
            'description' => (string) $plan->description,
            'provider' => (string) $plan->provider,
            'currency' => $plan->currency,
            'monthly_cost' => (string) $plan->monthly_cost,
            'employer_share_percent' => (string) $plan->employer_share_percent,
            'allows_dependents' => $plan->allows_dependents,
            'dependent_monthly_cost' => (string) $plan->dependent_monthly_cost,
            'dependent_employer_share_percent' => (string) $plan->dependent_employer_share_percent,
            'max_dependents' => $plan->max_dependents !== null ? (string) $plan->max_dependents : '',
            'taxable' => $plan->taxable,
            'affects_social' => $plan->affects_social,
            'tax_basis' => (string) $plan->tax_basis,
            'contract_start' => $plan->contract_start?->toDateString() ?? '',
            'contract_end' => $plan->contract_end?->toDateString() ?? '',
            'renewal_window_days' => (string) $plan->renewal_window_days,
            'auto_renew' => $plan->auto_renew,
            'self_service' => $plan->self_service,
            'is_active' => $plan->is_active,
            'sort' => (string) $plan->getAttribute('sort'),
            'eligibility' => [
                'after_probation' => (bool) ($rules['after_probation'] ?? false),
                'min_service_months' => isset($rules['min_service_months']) ? (string) $rules['min_service_months'] : '',
                'position_ids' => array_map('strval', (array) ($rules['position_ids'] ?? [])),
                'structure_ids' => array_map('strval', (array) ($rules['structure_ids'] ?? [])),
                'vtisk_categories' => (array) ($rules['vtisk_categories'] ?? []),
                'contract_types' => (array) ($rules['contract_types'] ?? []),
            ],
        ];
        $this->panel = 'plan';
        $this->resetValidation();
    }

    public function savePlan(BenefitPlanService $service): void
    {
        $this->guardManage();

        $data = $this->validate([
            'planForm.code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_\-]+$/', Rule::unique('benefit_plans', 'code')->ignore($this->editingPlanId)],
            'planForm.name' => ['required', 'string', 'max:255'],
            'planForm.type' => ['required', Rule::in(BenefitTypes::all())],
            'planForm.description' => ['nullable', 'string', 'max:2000'],
            'planForm.provider' => ['nullable', 'string', 'max:255'],
            'planForm.currency' => ['required', 'string', 'size:3'],
            'planForm.monthly_cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'planForm.employer_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'planForm.allows_dependents' => ['boolean'],
            'planForm.dependent_monthly_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'planForm.dependent_employer_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'planForm.max_dependents' => ['nullable', 'integer', 'min:1', 'max:20'],
            'planForm.taxable' => ['boolean'],
            'planForm.affects_social' => ['boolean'],
            'planForm.tax_basis' => ['nullable', 'string', 'max:60'],
            'planForm.contract_start' => ['nullable', 'date'],
            'planForm.contract_end' => ['nullable', 'date', 'after_or_equal:planForm.contract_start'],
            'planForm.renewal_window_days' => ['required', 'integer', 'min:1', 'max:180'],
            'planForm.auto_renew' => ['boolean'],
            'planForm.self_service' => ['boolean'],
            'planForm.is_active' => ['boolean'],
            'planForm.sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'planForm.eligibility.after_probation' => ['boolean'],
            'planForm.eligibility.min_service_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'planForm.eligibility.position_ids' => ['array'],
            'planForm.eligibility.position_ids.*' => ['integer', 'exists:positions,id'],
            'planForm.eligibility.structure_ids' => ['array'],
            'planForm.eligibility.structure_ids.*' => ['integer', 'exists:structures,id'],
            'planForm.eligibility.vtisk_categories' => ['array'],
            'planForm.eligibility.vtisk_categories.*' => ['string', Rule::in(['rehber', 'mutexessis', 'texniki_icraci', 'fehle'])],
            'planForm.eligibility.contract_types' => ['array'],
            'planForm.eligibility.contract_types.*' => ['string', Rule::in(['fixed', 'indefinite'])],
        ], attributes: $this->fieldLabels([
            'planForm.code' => 'code',
            'planForm.name' => 'name',
            'planForm.type' => 'type',
            'planForm.monthly_cost' => 'monthly_cost',
            'planForm.employer_share_percent' => 'employer_share_percent',
            'planForm.dependent_monthly_cost' => 'dependent_monthly_cost',
            'planForm.dependent_employer_share_percent' => 'dependent_employer_share_percent',
            'planForm.max_dependents' => 'max_dependents',
            'planForm.contract_start' => 'contract_start',
            'planForm.contract_end' => 'contract_end',
            'planForm.renewal_window_days' => 'renewal_window_days',
            'planForm.currency' => 'currency',
        ]))['planForm'];

        $service->save($data, $this->editingPlanId);

        $this->closePanel();
        $this->editingPlanId = null;
        unset($this->plans);
        $this->announce('saved');
    }

    public function deletePlan(int $id, BenefitPlanService $service): void
    {
        $this->guardManage();
        $service->delete($id);
        unset($this->plans);
        $this->announce('deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function blankForm(string $type): array
    {
        $defaults = BenefitTypes::defaults($type);

        return [
            'code' => '',
            'name' => '',
            'type' => $type,
            'description' => '',
            'provider' => '',
            'currency' => 'AZN',
            'monthly_cost' => '0',
            'employer_share_percent' => '100',
            'allows_dependents' => $defaults['allows_dependents'],
            'dependent_monthly_cost' => '0',
            'dependent_employer_share_percent' => '0',
            'max_dependents' => '',
            'taxable' => $defaults['taxable'],
            'affects_social' => $defaults['affects_social'],
            'tax_basis' => $defaults['basis'],
            'contract_start' => '',
            'contract_end' => '',
            'renewal_window_days' => (string) config('benefits.renewal.window_days', 30),
            'auto_renew' => true,
            'self_service' => true,
            'is_active' => true,
            'sort' => '0',
            'eligibility' => [
                'after_probation' => false,
                'min_service_months' => '',
                'position_ids' => [],
                'structure_ids' => [],
                'vtisk_categories' => [],
                'contract_types' => [],
            ],
        ];
    }

    protected function viewData(): array
    {
        return [
            'types' => BenefitTypes::all(),
            'bases' => BenefitTypes::bases(),
        ];
    }
}
