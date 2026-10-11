<?php

namespace App\Modules\EmployeeRelations\Livewire;

use App\Models\Structure;
use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Application\Services\CaseTypeService;
use App\Modules\EmployeeRelations\Application\Services\PersonnelLookup;
use App\Modules\EmployeeRelations\Application\Services\StatisticsService;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Modules\EmployeeRelations\Domain\OrderCancellationState;
use App\Services\StructureService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * İşçi münasibətləri: işlərin siyahısı (məxfilik CaseAccess::visibleQuery ilə),
 * yeni iş, anonimləşdirilmiş statistika və iş növləri soraqçası.
 *
 * @property-read LengthAwarePaginator<int, RelationCase> $cases
 * @property-read array<int, string> $typeOptions
 * @property-read array<string, mixed> $statistics
 * @property-read array<int, string> $structureOptions
 * @property-read \Illuminate\Support\Collection<int, RelationCaseType> $types
 */
class Dashboard extends Component
{
    use WithPagination;

    public const PER_PAGE = 20;

    public const TABS = ['cases', 'statistics', 'types'];

    #[Url(as: 'tab')]
    public string $activeTab = 'cases';

    public string $search = '';

    public ?string $status = '';

    public ?string $typeId = '';

    public bool $overdueOnly = false;

    /** Yalnız bağlı əmri ləğv gözləyən işlər (apellyasiya tənbehi ləğv edib). */
    public bool $cancellationPending = false;

    public bool $showCreate = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public string $subjectSearch = '';

    public string $complainantSearch = '';

    public ?string $statsStructure = '';

    public ?string $statsFrom = '';

    public ?string $statsTo = '';

    /** @var array<string, mixed> */
    public array $typeForm = [];

    public ?int $editingTypeId = null;

    public function mount(CaseAccess $access): void
    {
        abort_unless($access->canUseModule($this->user()) || $this->user()->can(CaseAccess::STATISTICS), 403);

        if (! in_array($this->activeTab, $this->tabs(), true)) {
            $this->activeTab = $this->tabs()[0] ?? 'cases';
        }

        $this->form = $this->blankForm();
        $this->typeForm = $this->blankTypeForm();
    }

    /**
     * @return list<string>
     */
    public function tabs(): array
    {
        $user = $this->user();
        $access = app(CaseAccess::class);

        $allowed = [
            'cases' => $access->canUseModule($user),
            'statistics' => $user->can(CaseAccess::STATISTICS),
            'types' => $user->can(CaseAccess::MANAGE),
        ];

        return array_values(array_filter(self::TABS, fn (string $tab): bool => $allowed[$tab]));
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, $this->tabs(), true), 403);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'typeId', 'overdueOnly', 'cancellationPending'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'typeId', 'overdueOnly', 'cancellationPending');
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, RelationCase>
     */
    #[Computed]
    public function cases(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return app(CaseAccess::class)->visibleQuery($this->user())
            ->with(['type:id,name', 'subject:id,surname,name,patronymic', 'structure:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('case_number', 'like', '%'.$search.'%')
                ->orWhere('title', 'like', '%'.$search.'%')))
            ->when(filled($this->status), fn ($query) => $query->where('status', (string) $this->status))
            ->when(filled($this->typeId), fn ($query) => $query->where('case_type_id', (int) $this->typeId))
            ->when($this->overdueOnly, fn ($query) => $query
                ->whereNotIn('status', [CaseStatus::Decided->value, CaseStatus::Closed->value])
                ->whereNotNull('stage_due_at')
                ->where('stage_due_at', '<', now()))
            ->when($this->cancellationPending, fn ($query) => $query->where('order_cancellation_status', OrderCancellationState::Pending->value))
            ->latest('id')
            ->paginate(self::PER_PAGE);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return RelationCaseType::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        $options = [];

        foreach (CaseStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public function openCreate(): void
    {
        abort_unless(app(CaseAccess::class)->canCreate($this->user()), 403);

        $this->resetValidation();
        $this->form = $this->blankForm();
        $this->subjectSearch = '';
        $this->complainantSearch = '';
        $this->showCreate = true;
    }

    public function closeCreate(): void
    {
        $this->showCreate = false;
    }

    /**
     * @return list<array{id:int, name:string, meta:string}>
     */
    public function personnelResults(string $term): array
    {
        return app(PersonnelLookup::class)->search($this->user(), $term);
    }

    public function pickPersonnel(string $field, int $personnelId): void
    {
        abort_unless(in_array($field, ['subject_personnel_id', 'complainant_personnel_id'], true), 404);
        $this->form[$field.'_label'] = app(PersonnelLookup::class)->visibleName($this->user(), $personnelId);
        $this->form[$field] = $personnelId;

        if ($field === 'subject_personnel_id') {
            $this->subjectSearch = '';
        } else {
            $this->complainantSearch = '';
        }
    }

    public function clearPersonnel(string $field): void
    {
        abort_unless(in_array($field, ['subject_personnel_id', 'complainant_personnel_id'], true), 404);
        $this->form[$field] = null;
        $this->form[$field.'_label'] = '';
    }

    public function saveCase(CaseService $cases): void
    {
        $validated = $this->validate([
            'form.case_type_id' => ['required', 'integer'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['required', 'string', 'max:10000'],
            'form.occurred_at' => ['nullable', 'date', 'before_or_equal:today'],
            'form.discovered_at' => ['nullable', 'date', 'before_or_equal:today'],
            'form.discovered_by_audit' => ['boolean'],
            'form.subject_personnel_id' => ['nullable', 'integer'],
            'form.complainant_personnel_id' => ['nullable', 'integer'],
            'form.is_anonymous' => ['boolean'],
        ], attributes: [
            'form.case_type_id' => __('employee_relations::cases.fields.type'),
            'form.title' => __('employee_relations::cases.fields.title'),
            'form.description' => __('employee_relations::cases.fields.description'),
            'form.occurred_at' => __('employee_relations::cases.fields.occurred_at'),
            'form.discovered_at' => __('employee_relations::cases.fields.discovered_at'),
        ])['form'];

        $case = $cases->openByStaff($this->user(), $validated);

        $this->showCreate = false;
        $this->dispatch('notify', type: 'success', message: __('employee_relations::cases.messages.case_created', ['number' => $case->label()]));
        $this->redirectRoute('employee-relations.cases.show', ['case' => $case->id], navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function statistics(): array
    {
        return app(StatisticsService::class)->build(
            $this->user(),
            filled($this->statsStructure) ? (int) $this->statsStructure : null,
            $this->statsFrom ?: null,
            $this->statsTo ?: null,
        );
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function structureOptions(): array
    {
        $query = Structure::query()->orderBy('name');
        app(StructureService::class)->scopeFor($this->user())->constrain($query, 'id');

        return $query->limit(500)->pluck('name', 'id')->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, RelationCaseType>
     */
    #[Computed]
    public function types(): \Illuminate\Support\Collection
    {
        return RelationCaseType::query()->withCount('cases')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function editType(int $typeId): void
    {
        abort_unless($this->user()->can(CaseAccess::MANAGE), 403);

        $type = RelationCaseType::query()->findOrFail($typeId);
        $this->editingTypeId = $type->id;
        $this->typeForm = [
            'name' => $type->name,
            'description' => (string) $type->description,
            'investigation_sla_days' => $type->investigation_sla_days,
            'is_disciplinary' => $type->is_disciplinary,
            'is_active' => $type->is_active,
        ];
    }

    public function cancelType(): void
    {
        $this->editingTypeId = null;
        $this->typeForm = $this->blankTypeForm();
        $this->resetValidation();
    }

    public function saveType(CaseTypeService $types): void
    {
        $validated = $this->validate([
            'typeForm.name' => ['required', 'string', 'max:150'],
            'typeForm.description' => ['nullable', 'string', 'max:500'],
            'typeForm.investigation_sla_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'typeForm.is_disciplinary' => ['boolean'],
            'typeForm.is_active' => ['boolean'],
        ], attributes: [
            'typeForm.name' => __('employee_relations::cases.fields.type_name'),
            'typeForm.investigation_sla_days' => __('employee_relations::cases.fields.investigation_sla_days'),
        ])['typeForm'];

        $types->save($this->user(), $validated, $this->editingTypeId);

        $this->cancelType();
        unset($this->types, $this->typeOptions);
        $this->dispatch('notify', type: 'success', message: __('employee_relations::cases.messages.type_saved'));
    }

    public function toggleType(int $typeId): void
    {
        app(CaseTypeService::class)->toggle($this->user(), $typeId);
        unset($this->types, $this->typeOptions);
    }

    public function render(): View
    {
        $user = $this->user();
        $access = app(CaseAccess::class);

        return view('employee-relations::livewire.dashboard', [
            'canCreate' => $access->canCreate($user),
            'canManage' => $user->can(CaseAccess::MANAGE),
            'canReveal' => $user->can(CaseAccess::REVEAL),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankForm(): array
    {
        return [
            'case_type_id' => (string) (array_key_first($this->typeOptions) ?? ''),
            'title' => '',
            'description' => '',
            'occurred_at' => '',
            'discovered_at' => today()->toDateString(),
            'discovered_by_audit' => false,
            'subject_personnel_id' => null,
            'subject_personnel_id_label' => '',
            'complainant_personnel_id' => null,
            'complainant_personnel_id_label' => '',
            'is_anonymous' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankTypeForm(): array
    {
        return [
            'name' => '',
            'description' => '',
            'investigation_sla_days' => null,
            'is_disciplinary' => false,
            'is_active' => true,
        ];
    }
}
