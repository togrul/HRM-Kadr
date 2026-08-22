<?php

namespace App\Modules\Compensation\Livewire;

use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeCompensation;
use App\Models\PayGrade;
use App\Models\PayScale;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Application\Services\SalaryScaleService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use InteractsWithTabbedWorkspace;
    use WithPagination;

    public string $activeTab = 'scales';

    // --- Scales ---
    public ?int $editingScaleId = null;

    public string $scaleSearch = '';

    public array $scaleForm = [
        'name' => '',
        'regime_id' => null,
        'currency' => 'AZN',
        'effective_from' => '',
        'effective_to' => '',
        'is_active' => true,
        'description' => '',
    ];

    // --- Grades (within a selected scale) ---
    public ?int $selectedScaleId = null;

    public ?int $editingGradeId = null;

    public array $gradeForm = [
        'code' => '',
        'name' => '',
        'base_amount' => '',
        'rank_category_id' => null,
        'position_id' => null,
        'sort' => 0,
    ];

    // --- Components ---
    public ?int $editingComponentId = null;

    public string $componentSearch = '';

    public array $componentForm = [
        'code' => '',
        'name' => '',
        'type' => 'earning',
        'calc_type' => 'fixed',
        'taxable' => true,
        'affects_social' => true,
        'is_statutory' => false,
        'gl_code' => '',
        'sort' => 0,
        'is_active' => true,
    ];

    // --- Assignments ---
    public string $personnelSearch = '';

    public ?string $selectedTabelNo = null;

    public ?string $selectedPersonnelLabel = null;

    public array $assignmentForm = [
        'regime_id' => null,
        'pay_grade_id' => null,
        'base_amount' => '',
        'currency' => 'AZN',
        'effective_from' => '',
        'order_no' => '',
        'note' => '',
    ];

    public array $assignmentLines = [];

    // --- Bank ---
    public ?int $editingBankId = null;

    public array $bankForm = [
        'iban' => '',
        'bank_name' => '',
        'account_no' => '',
        'is_primary' => true,
        'is_active' => true,
    ];

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return ['scales', 'components', 'assignments', 'bank', 'history', 'statutory'];
    }

    // --- Statutory rates ---
    public array $statutoryForm = [
        'regime_id' => null,
        'component_code' => 'income_tax',
        'payer' => 'ee',
        'base' => 'social',
        'effective_from' => '',
    ];

    public array $statutoryBrackets = [];

    #[Computed]
    public function statutoryRates(): Collection
    {
        return \App\Models\StatutoryRate::query()
            ->with('regime:id,name')
            ->orderBy('component_code')
            ->orderBy('payer')
            ->get();
    }

    public function addStatutoryBracket(): void
    {
        $this->statutoryBrackets[] = ['up_to' => '', 'rate' => ''];
    }

    public function removeStatutoryBracket(int $index): void
    {
        unset($this->statutoryBrackets[$index]);
        $this->statutoryBrackets = array_values($this->statutoryBrackets);
    }

    public function saveStatutoryRate(): void
    {
        $this->guardManage();

        $data = $this->validate([
            'statutoryForm.regime_id' => 'nullable|exists:compensation_regimes,id',
            'statutoryForm.component_code' => 'required|in:income_tax,dsmf,unemployment,medical',
            'statutoryForm.payer' => 'required|in:ee,er',
            'statutoryForm.base' => 'required|in:taxable,social',
            'statutoryForm.effective_from' => 'required|date',
            'statutoryBrackets' => 'required|array|min:1',
            'statutoryBrackets.*.rate' => 'required|numeric|min:0|max:100',
            'statutoryBrackets.*.up_to' => 'nullable|numeric|min:0',
        ], attributes: [
            'statutoryForm.regime_id' => __('compensation::dashboard.fields.regime'),
            'statutoryForm.component_code' => __('compensation::dashboard.statutory.component'),
            'statutoryForm.payer' => __('compensation::dashboard.statutory.payer'),
            'statutoryForm.base' => __('compensation::dashboard.statutory.base'),
            'statutoryForm.effective_from' => __('compensation::dashboard.fields.effective_from'),
            'statutoryBrackets' => __('compensation::dashboard.statutory.brackets'),
            'statutoryBrackets.*.rate' => __('compensation::dashboard.statutory.rate'),
            'statutoryBrackets.*.up_to' => __('compensation::dashboard.statutory.up_to'),
        ])['statutoryForm'];

        $brackets = array_map(fn ($b) => [
            'up_to' => ($b['up_to'] === '' || $b['up_to'] === null) ? null : (float) $b['up_to'],
            'rate' => (float) $b['rate'],
        ], $this->statutoryBrackets);

        \App\Models\StatutoryRate::create([
            'regime_id' => $data['regime_id'] ?: null,
            'component_code' => $data['component_code'],
            'payer' => $data['payer'],
            'base' => $data['base'],
            'brackets' => $brackets,
            'effective_from' => $data['effective_from'],
            'is_active' => true,
        ]);

        $this->statutoryForm = ['regime_id' => null, 'component_code' => 'income_tax', 'payer' => 'ee', 'base' => 'social', 'effective_from' => ''];
        $this->statutoryBrackets = [];
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    public function deleteStatutoryRate(int $id): void
    {
        $this->guardManage();
        \App\Models\StatutoryRate::whereKey($id)->delete();
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.deleted'));
    }

    #[Computed]
    public function allowedTabsList(): array
    {
        return $this->allowedTabs();
    }

    public function canView(): bool
    {
        return auth()->user()?->can('show-compensation') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-compensation') ?? false;
    }

    public function canViewAmounts(): bool
    {
        return auth()->user()?->can('view-compensation-amounts') ?? false;
    }

    protected function guardManage(): void
    {
        abort_unless($this->canManage(), 403);
    }

    /**
     * Map [validation path => field key] to translated attribute labels for validation messages.
     *
     * @param  array<string,string>  $map
     * @return array<string,string>
     */
    protected function fieldLabels(array $map): array
    {
        $labels = [];

        foreach ($map as $path => $key) {
            $labels[$path] = __('compensation::dashboard.fields.'.$key);
        }

        return $labels;
    }

    // ----------------------------------------------------------------
    // Option lists
    // ----------------------------------------------------------------

    #[Computed]
    public function summaryStats(): array
    {
        return [
            ['key' => 'scales', 'value' => PayScale::query()->count(), 'accent' => 'bg-sky-500'],
            ['key' => 'grades', 'value' => PayGrade::query()->count(), 'accent' => 'bg-violet-500'],
            ['key' => 'components', 'value' => CompensationComponent::query()->where('is_active', true)->count(), 'accent' => 'bg-amber-400'],
            ['key' => 'assignments', 'value' => EmployeeCompensation::query()->where('status', 'active')->count(), 'accent' => 'bg-emerald-500'],
        ];
    }

    #[Computed]
    public function regimeOptions(): array
    {
        return CompensationRegime::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name'])
            ->map(fn (CompensationRegime $r): array => ['id' => $r->id, 'label' => $r->name])
            ->all();
    }

    #[Computed]
    public function componentOptions(): array
    {
        return CompensationComponent::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name', 'type'])
            ->map(fn (CompensationComponent $c): array => ['id' => $c->id, 'label' => $c->name])
            ->all();
    }

    public string $searchRankCategory = '';

    public string $searchPosition = '';

    #[Computed]
    public function rankCategoryOptions(): array
    {
        $term = trim($this->searchRankCategory);

        return \App\Models\RankCategory::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn ($r): array => ['id' => $r->id, 'label' => $r->name])
            ->all();
    }

    #[Computed]
    public function positionOptions(): array
    {
        $term = trim($this->searchPosition);

        return \App\Models\Position::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn ($p): array => ['id' => $p->id, 'label' => $p->name])
            ->all();
    }

    #[Computed]
    public function personnelResults(): array
    {
        $term = trim($this->personnelSearch);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Personnel::query()
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('tabel_no', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['tabel_no', 'surname', 'name'])
            ->map(fn (Personnel $p): array => [
                'tabel_no' => $p->tabel_no,
                'label' => trim("{$p->tabel_no} — {$p->surname} {$p->name}"),
            ])
            ->all();
    }

    // ----------------------------------------------------------------
    // Scales
    // ----------------------------------------------------------------

    #[Computed]
    public function scales(): LengthAwarePaginator
    {
        $term = trim($this->scaleSearch);

        return PayScale::query()
            ->with('regime:id,name')
            ->withCount('grades')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderByDesc('effective_from')
            ->paginate(8, ['*'], 'scalesPage');
    }

    public function updatedScaleSearch(): void
    {
        $this->resetPage('scalesPage');
    }

    public function editScale(int $id): void
    {
        $scale = PayScale::findOrFail($id);
        $this->editingScaleId = $scale->id;
        $this->scaleForm = [
            'name' => $scale->name,
            'regime_id' => $scale->regime_id,
            'currency' => $scale->currency,
            'effective_from' => optional($scale->effective_from)->toDateString() ?? '',
            'effective_to' => optional($scale->effective_to)->toDateString() ?? '',
            'is_active' => (bool) $scale->is_active,
            'description' => $scale->description ?? '',
        ];
        $this->resetValidation();
    }

    public function saveScale(SalaryScaleService $service): void
    {
        $this->guardManage();

        $data = $this->validate([
            'scaleForm.name' => 'required|string|max:255',
            'scaleForm.regime_id' => 'required|exists:compensation_regimes,id',
            'scaleForm.currency' => 'required|string|size:3',
            'scaleForm.effective_from' => 'required|date',
            'scaleForm.effective_to' => 'nullable|date|after_or_equal:scaleForm.effective_from',
            'scaleForm.is_active' => 'boolean',
            'scaleForm.description' => 'nullable|string|max:2000',
        ], attributes: $this->fieldLabels([
            'scaleForm.name' => 'name',
            'scaleForm.regime_id' => 'regime',
            'scaleForm.currency' => 'currency',
            'scaleForm.effective_from' => 'effective_from',
            'scaleForm.effective_to' => 'effective_to',
            'scaleForm.is_active' => 'is_active',
            'scaleForm.description' => 'description',
        ]))['scaleForm'];

        $data['effective_to'] = $data['effective_to'] ?: null;

        if ($this->editingScaleId) {
            $service->updateScale(PayScale::findOrFail($this->editingScaleId), $data);
        } else {
            $service->createScale($data);
        }

        $this->cancelScale();
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    public function deleteScale(int $id, SalaryScaleService $service): void
    {
        $this->guardManage();
        $service->deleteScale(PayScale::findOrFail($id));

        if ($this->editingScaleId === $id) {
            $this->cancelScale();
        }
        if ($this->selectedScaleId === $id) {
            $this->selectedScaleId = null;
        }

        $this->resetPage('scalesPage');
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.deleted'));
    }

    public function cancelScale(): void
    {
        $this->editingScaleId = null;
        $this->scaleForm = [
            'name' => '', 'regime_id' => null, 'currency' => 'AZN',
            'effective_from' => '', 'effective_to' => '', 'is_active' => true, 'description' => '',
        ];
        $this->resetValidation();
    }

    // ----------------------------------------------------------------
    // Grades
    // ----------------------------------------------------------------

    public function selectScale(int $id): void
    {
        $this->selectedScaleId = $id;
        $this->cancelGrade();
    }

    #[Computed]
    public function grades(): Collection
    {
        if (! $this->selectedScaleId) {
            return collect();
        }

        return PayGrade::query()
            ->where('pay_scale_id', $this->selectedScaleId)
            ->with(['rankCategory:id,name', 'position:id,name'])
            ->orderBy('sort')
            ->get();
    }

    public function editGrade(int $id): void
    {
        $grade = PayGrade::findOrFail($id);
        $this->selectedScaleId = $grade->pay_scale_id;
        $this->editingGradeId = $grade->id;
        $this->gradeForm = [
            'code' => $grade->code,
            'name' => $grade->name,
            'base_amount' => (string) $grade->base_amount,
            'rank_category_id' => $grade->rank_category_id,
            'position_id' => $grade->position_id,
            'sort' => $grade->sort,
        ];
        $this->resetValidation();
    }

    public function saveGrade(SalaryScaleService $service): void
    {
        $this->guardManage();
        abort_unless($this->selectedScaleId !== null, 422);

        $data = $this->validate([
            'gradeForm.code' => 'required|string|max:64',
            'gradeForm.name' => 'required|string|max:255',
            'gradeForm.base_amount' => 'required|numeric|min:0',
            'gradeForm.rank_category_id' => 'nullable|exists:rank_categories,id',
            'gradeForm.position_id' => 'nullable|exists:positions,id',
            'gradeForm.sort' => 'nullable|integer|min:0',
        ], attributes: $this->fieldLabels([
            'gradeForm.code' => 'code',
            'gradeForm.name' => 'name',
            'gradeForm.base_amount' => 'base_amount',
            'gradeForm.rank_category_id' => 'rank_category',
            'gradeForm.position_id' => 'position',
            'gradeForm.sort' => 'sort',
        ]))['gradeForm'];

        $data['pay_scale_id'] = $this->selectedScaleId;
        $data['sort'] = $data['sort'] ?? 0;

        if ($this->editingGradeId) {
            $service->updateGrade(PayGrade::findOrFail($this->editingGradeId), $data);
        } else {
            $service->createGrade($data);
        }

        $this->cancelGrade();
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    public function deleteGrade(int $id, SalaryScaleService $service): void
    {
        $this->guardManage();
        $service->deleteGrade(PayGrade::findOrFail($id));

        if ($this->editingGradeId === $id) {
            $this->cancelGrade();
        }

        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.deleted'));
    }

    public function cancelGrade(): void
    {
        $this->editingGradeId = null;
        $this->gradeForm = [
            'code' => '', 'name' => '', 'base_amount' => '',
            'rank_category_id' => null, 'position_id' => null, 'sort' => 0,
        ];
        $this->resetValidation();
    }

    // ----------------------------------------------------------------
    // Components
    // ----------------------------------------------------------------

    #[Computed]
    public function components(): LengthAwarePaginator
    {
        $term = trim($this->componentSearch);

        return CompensationComponent::query()
            ->when($term !== '', fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%"))
            ->orderBy('sort')
            ->paginate(10, ['*'], 'componentsPage');
    }

    public function updatedComponentSearch(): void
    {
        $this->resetPage('componentsPage');
    }

    public function editComponent(int $id): void
    {
        $component = CompensationComponent::findOrFail($id);
        $this->editingComponentId = $component->id;
        $this->componentForm = [
            'code' => $component->code,
            'name' => $component->name,
            'type' => $component->type,
            'calc_type' => $component->calc_type,
            'taxable' => (bool) $component->taxable,
            'affects_social' => (bool) $component->affects_social,
            'is_statutory' => (bool) $component->is_statutory,
            'gl_code' => $component->gl_code ?? '',
            'sort' => $component->sort,
            'is_active' => (bool) $component->is_active,
        ];
        $this->resetValidation();
    }

    public function saveComponent(): void
    {
        $this->guardManage();

        $codeRule = 'required|string|max:64|unique:compensation_components,code';
        if ($this->editingComponentId) {
            $codeRule .= ','.$this->editingComponentId;
        }

        $data = $this->validate([
            'componentForm.code' => $codeRule,
            'componentForm.name' => 'required|string|max:255',
            'componentForm.type' => 'required|in:earning,deduction',
            'componentForm.calc_type' => 'required|in:fixed,percent,formula,per_diem,rate',
            'componentForm.taxable' => 'boolean',
            'componentForm.affects_social' => 'boolean',
            'componentForm.is_statutory' => 'boolean',
            'componentForm.gl_code' => 'nullable|string|max:64',
            'componentForm.sort' => 'nullable|integer|min:0',
            'componentForm.is_active' => 'boolean',
        ], attributes: $this->fieldLabels([
            'componentForm.code' => 'code',
            'componentForm.name' => 'name',
            'componentForm.type' => 'type',
            'componentForm.calc_type' => 'calc_type',
            'componentForm.taxable' => 'taxable',
            'componentForm.affects_social' => 'affects_social',
            'componentForm.is_statutory' => 'is_statutory',
            'componentForm.gl_code' => 'gl_code',
            'componentForm.sort' => 'sort',
            'componentForm.is_active' => 'is_active',
        ]))['componentForm'];

        $data['gl_code'] = $data['gl_code'] ?: null;
        $data['sort'] = $data['sort'] ?? 0;

        if ($this->editingComponentId) {
            CompensationComponent::findOrFail($this->editingComponentId)->update($data);
        } else {
            CompensationComponent::create($data);
        }

        $this->cancelComponent();
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    public function deleteComponent(int $id): void
    {
        $this->guardManage();
        CompensationComponent::whereKey($id)->delete();

        if ($this->editingComponentId === $id) {
            $this->cancelComponent();
        }

        $this->resetPage('componentsPage');
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.deleted'));
    }

    public function cancelComponent(): void
    {
        $this->editingComponentId = null;
        $this->componentForm = [
            'code' => '', 'name' => '', 'type' => 'earning', 'calc_type' => 'fixed',
            'taxable' => true, 'affects_social' => true, 'is_statutory' => false,
            'gl_code' => '', 'sort' => 0, 'is_active' => true,
        ];
        $this->resetValidation();
    }

    // ----------------------------------------------------------------
    // Assignments
    // ----------------------------------------------------------------

    public function selectPersonnel(string $tabelNo, string $label): void
    {
        $this->selectedTabelNo = $tabelNo;
        $this->selectedPersonnelLabel = $label;
        $this->personnelSearch = '';
    }

    public function clearPersonnel(): void
    {
        $this->selectedTabelNo = null;
        $this->selectedPersonnelLabel = null;
    }

    public function addAssignmentLine(): void
    {
        $this->assignmentLines[] = ['component_id' => null, 'amount' => '', 'percent' => '', 'note' => ''];
    }

    public function removeAssignmentLine(int $index): void
    {
        unset($this->assignmentLines[$index]);
        $this->assignmentLines = array_values($this->assignmentLines);
    }

    #[Computed]
    public function currentAssignment(): ?EmployeeCompensation
    {
        if (! $this->selectedTabelNo) {
            return null;
        }

        return app(CompensationService::class)->currentFor($this->selectedTabelNo);
    }

    public function saveAssignment(CompensationService $service): void
    {
        $this->guardManage();
        abort_unless($this->selectedTabelNo !== null, 422);

        $validated = $this->validate([
            'assignmentForm.regime_id' => 'required|exists:compensation_regimes,id',
            'assignmentForm.pay_grade_id' => 'nullable|exists:pay_grades,id',
            'assignmentForm.base_amount' => 'required|numeric|min:0',
            'assignmentForm.currency' => 'required|string|size:3',
            'assignmentForm.effective_from' => 'required|date',
            'assignmentForm.order_no' => 'nullable|string|max:64',
            'assignmentForm.note' => 'nullable|string|max:2000',
            'assignmentLines.*.component_id' => 'nullable|exists:compensation_components,id',
            'assignmentLines.*.amount' => 'nullable|numeric',
            'assignmentLines.*.percent' => 'nullable|numeric|min:0|max:100',
        ], attributes: $this->fieldLabels([
            'assignmentForm.regime_id' => 'regime',
            'assignmentForm.pay_grade_id' => 'base_amount',
            'assignmentForm.base_amount' => 'base_amount',
            'assignmentForm.currency' => 'currency',
            'assignmentForm.effective_from' => 'effective_from',
            'assignmentForm.order_no' => 'order_no',
            'assignmentForm.note' => 'note',
        ]));

        $service->assignCompensation(
            $this->selectedTabelNo,
            $validated['assignmentForm'],
            $this->assignmentLines,
        );

        $this->assignmentForm = [
            'regime_id' => null, 'pay_grade_id' => null, 'base_amount' => '',
            'currency' => 'AZN', 'effective_from' => '', 'order_no' => '', 'note' => '',
        ];
        $this->assignmentLines = [];
        unset($this->currentAssignment);

        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    // ----------------------------------------------------------------
    // Bank
    // ----------------------------------------------------------------

    #[Computed]
    public function bankAccounts(): Collection
    {
        if (! $this->selectedTabelNo) {
            return collect();
        }

        return EmployeeBankAccount::query()
            ->where('tabel_no', $this->selectedTabelNo)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->get();
    }

    public function editBank(int $id): void
    {
        $account = EmployeeBankAccount::findOrFail($id);
        $this->selectedTabelNo = $account->tabel_no;
        $this->editingBankId = $account->id;
        $this->bankForm = [
            'iban' => $account->iban,
            'bank_name' => $account->bank_name ?? '',
            'account_no' => $account->account_no ?? '',
            'is_primary' => (bool) $account->is_primary,
            'is_active' => (bool) $account->is_active,
        ];
        $this->resetValidation();
    }

    public function saveBank(): void
    {
        $this->guardManage();
        abort_unless($this->selectedTabelNo !== null, 422);

        $data = $this->validate([
            'bankForm.iban' => 'required|string|max:34',
            'bankForm.bank_name' => 'nullable|string|max:255',
            'bankForm.account_no' => 'nullable|string|max:64',
            'bankForm.is_primary' => 'boolean',
            'bankForm.is_active' => 'boolean',
        ], attributes: $this->fieldLabels([
            'bankForm.iban' => 'iban',
            'bankForm.bank_name' => 'bank_name',
            'bankForm.account_no' => 'account_no',
            'bankForm.is_primary' => 'is_primary',
            'bankForm.is_active' => 'is_active',
        ]))['bankForm'];

        $data['tabel_no'] = $this->selectedTabelNo;

        DB::transaction(function () use ($data): void {
            if (! empty($data['is_primary'])) {
                // Only one primary per employee.
                EmployeeBankAccount::query()
                    ->where('tabel_no', $this->selectedTabelNo)
                    ->when($this->editingBankId, fn ($q) => $q->whereKeyNot($this->editingBankId))
                    ->update(['is_primary' => false]);
            }

            if ($this->editingBankId) {
                EmployeeBankAccount::findOrFail($this->editingBankId)->update($data);
            } else {
                EmployeeBankAccount::create($data);
            }
        });

        $this->cancelBank();
        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.saved'));
    }

    public function deleteBank(int $id): void
    {
        $this->guardManage();
        EmployeeBankAccount::whereKey($id)->delete();

        if ($this->editingBankId === $id) {
            $this->cancelBank();
        }

        $this->dispatch('notify', type: 'success', message: __('compensation::dashboard.messages.deleted'));
    }

    public function cancelBank(): void
    {
        $this->editingBankId = null;
        $this->bankForm = [
            'iban' => '', 'bank_name' => '', 'account_no' => '', 'is_primary' => true, 'is_active' => true,
        ];
        $this->resetValidation();
    }

    // ----------------------------------------------------------------
    // History
    // ----------------------------------------------------------------

    #[Computed]
    public function history(): Collection
    {
        if (! $this->selectedTabelNo) {
            return collect();
        }

        return app(CompensationService::class)->historyFor($this->selectedTabelNo);
    }

    public function render(): View
    {
        return view('compensation::livewire.dashboard');
    }
}
