<?php

namespace App\Modules\Personnel\Livewire;

use App\Modules\Personnel\Exports\PersonnelExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Modules\Personnel\Services\PersonnelListStateNormalizer;
use App\Modules\Personnel\Services\PersonnelLookupService;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['personnelAdded', 'fileAdded', 'personnelWasDeleted'])]
class AllPersonnel extends Component
{
    use AuthorizesRequests;
    use NestedStructureTrait;
    use SideModalAction;
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = 'current';

    #[Url]
    public array $filters = [];

    #[Url(as: 'structure')]
    public array $structure = [];

    #[Url(as: 'position')]
    public ?int $selectedPosition = null;

    public bool $filterDetailMounted = false;

    public bool $pendingFilterOpen = false;

    protected ?array $accessibleStructureCache = null;

    protected array $allowedStatuses = ['current', 'leaves', 'all', 'deleted', 'pending'];

    #[On('personnelTableRowAction')]
    public function forwardTableRowAction(string $type, mixed $payload = null): void
    {
        $this->handleRowAction($type, $payload);
    }

    public function exportExcel()
    {
        $this->authorize('export', Personnel::class);

        $report['data'] = $this->personnelExportRows();
        $report['filter'] = $this->filters;
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new PersonnelExport($report), "personnel-$name.xlsx");
    }

    #[On('filterSelected')]
    public function filterSelected(array $filter)
    {
        $normalized = $this->normalizeAndPersistFilters($filter);

        if ($this->filters === $normalized) {
            return;
        }

        $this->filters = $normalized;
        $this->structure = [];
        $this->resetPage();
    }

    public function openFilter(): void
    {
        if (! $this->filterDetailMounted) {
            $this->filterDetailMounted = true;
            $this->pendingFilterOpen = true;

            return;
        }

        $this->dispatch('setOpenFilter', filter: $this->filters);
    }

    #[On('filterDetailReady')]
    public function handleFilterDetailReady(): void
    {
        if (! $this->pendingFilterOpen) {
            return;
        }

        $this->pendingFilterOpen = false;
        $this->dispatch('setOpenFilter', filter: $this->filters);
    }

    public function setDeletePersonnel($personnelId)
    {
        $this->dispatch('setDeletePersonnel', $personnelId);
    }

    public function restoreData($id)
    {
        $personnel = Personnel::withTrashed()->where('tabel_no', $id)->first();

        if (! $personnel) {
            return;
        }

        $this->authorize('restore', $personnel);

        $personnel->restore();
        $personnel->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('personnelAdded', __('personnel::common.messages.personnel_updated'));
    }

    public function forceDeleteData($id)
    {
        $model = Personnel::withTrashed()->where('tabel_no', $id)->first();

        if (! $model) {
            return;
        }

        $this->authorize('forceDelete', $model);

        $model->forceDelete();
        $this->dispatch('personnelWasDeleted', __('personnel::common.messages.personnel_deleted'));
    }

    #[On('selectStructure')]
    public function selectStructure(mixed $payload = null): void
    {
        $id = $this->resolveSelectStructureId($payload);

        if ($id === null) {
            return;
        }

        $this->structure = $this->getNestedStructure($id);
        $this->resetPage();
    }

    protected function resolveSelectStructureId(mixed $payload): ?int
    {
        if (is_array($payload)) {
            if (array_key_exists('id', $payload)) {
                $payload = $payload['id'];
            } elseif (! empty($payload) && array_is_list($payload)) {
                $payload = $payload[0];
            }
        }

        if (! is_numeric($payload)) {
            return null;
        }

        $id = (int) $payload;

        return $id > 0 ? $id : null;
    }

    public function getStatusFilters(): array
    {
        return [
            ['key' => 'current', 'label' => __('personnel::common.labels.active')],
            ['key' => 'leaves', 'label' => __('personnel::common.labels.resigned')],
            ['key' => 'all', 'label' => __('personnel::common.labels.all')],
            ['key' => 'deleted', 'label' => __('personnel::common.labels.deleted'), 'permission' => 'access-admin'],
            ['key' => 'pending', 'label' => __('personnel::common.labels.pending')],
        ];
    }

    public function getTableHeaders(): array
    {
        return [
            __('personnel::common.labels.number'),
            __('personnel::common.labels.tabel'),
            __('personnel::common.labels.fullname'),
            __('personnel::common.labels.structure'),
            __('personnel::common.labels.date'),
            __('services::common.labels.action'),
        ];
    }

    public function setStatus($newStatus)
    {
        if (! is_string($newStatus) || ! in_array($newStatus, $this->allowedStatuses, true)) {
            return;
        }

        if ($this->status === $newStatus) {
            return;
        }

        $this->status = $newStatus;
        $this->resetPage();
    }

    public function setPosition($new)
    {
        if (! is_numeric($new)) {
            return;
        }

        $newPosition = (int) $new;

        if ($this->selectedPosition === $newPosition) {
            return;
        }

        $this->selectedPosition = $newPosition;
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->reset('selectedPosition');
        $this->resetPage();
    }

    public function resetSelectedFilter()
    {
        $this->filters = [];
        $this->resetPage();
        $this->fillFilter();
        if ($this->filterDetailMounted) {
            $this->dispatch('filterResetted');
        }
    }

    public function fillFilter()
    {
        $normalizer = app(PersonnelListStateNormalizer::class);

        $this->status = $normalizer->normalizeStatus($this->status, $this->allowedStatuses);
        $this->filters = $normalizer->normalizeFilters(is_array($this->filters) ? $this->filters : []);
        $this->structure = $normalizer->normalizeStructure($this->structure);
        $this->selectedPosition = $normalizer->normalizePosition($this->selectedPosition);
    }

    protected function getSafeFilterPayload(): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeFilters(
            is_array($this->filters) ? $this->filters : []
        );
    }

    protected function normalizeAndPersistFilters(array $filter): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeFilters($filter);
    }

    #[Computed(persist: true)]
    public function positions()
    {
        return app(PersonnelLookupService::class)->positions();
    }

    public function mount()
    {
        $this->authorize('viewAny', Personnel::class);
        $this->fillFilter();
        $this->filters = $this->getSafeFilterPayload();
    }

    public function canEditPersonnels(): bool
    {
        return auth()->user()?->can('edit-personnels') ?? false;
    }

    public function canDeletePersonnels(): bool
    {
        return auth()->user()?->can('delete-personnels') ?? false;
    }

    public function canViewProfessionalPortfolio(): bool
    {
        return ProfessionalPortfolioPermissionMatrix::canViewPortfolio(auth()->user());
    }

    public function canManageMyHrAccounts(): bool
    {
        return auth()->user()?->can('manage-my-hr-accounts') ?? false;
    }

    public function canManageOnboardingDocuments(): bool
    {
        return (auth()->user()?->can('assign-onboarding-documents') ?? false)
            || (auth()->user()?->can('manage-onboarding-document-templates') ?? false);
    }

    public function canManageLearningMaterials(): bool
    {
        return (auth()->user()?->can('assign-employee-content') ?? false)
            || (auth()->user()?->can('manage-employee-content-library') ?? false);
    }

    public function handleRowAction(string $type, mixed $payload = null): void
    {
        if (is_object($payload)) {
            $payload = (array) $payload;
        } elseif (! is_array($payload)) {
            $payload = [];
        }

        $actionType = data_get($payload, 'type');
        if (! is_string($actionType)) {
            $actionType = in_array($type, ['edit', 'files', 'information', 'vacations'], true) ? 'open' : $type;
        }
        $value = (string) data_get($payload, 'value', '');

        $menu = (string) data_get($payload, 'menu', '');

        if ($actionType === 'open') {
            if ($menu === 'professional-portfolio' && ! $this->canViewProfessionalPortfolio()) {
                return;
            }

            if ($menu === 'my-hr-account' && ! $this->canManageMyHrAccounts()) {
                return;
            }

            if ($menu === 'onboarding-documents' && ! $this->canManageOnboardingDocuments()) {
                return;
            }

            if ($menu === 'learning-materials' && ! $this->canManageLearningMaterials()) {
                return;
            }

            if (! in_array($menu, ['professional-portfolio', 'my-hr-account', 'onboarding-documents', 'learning-materials'], true) && ! $this->canEditPersonnels()) {
                return;
            }
        }

        if (! $this->canDeletePersonnels() && in_array($actionType, ['delete', 'force-delete'], true)) {
            return;
        }

        match ($actionType) {
            'open' => $this->openSideMenu(data_get($payload, 'menu'), $value),
            'restore' => $this->restoreData($value),
            'delete' => $this->setDeletePersonnel($value),
            'force-delete' => $this->forceDeleteData($value),
            default => null,
        };
    }

    protected function selectedStructureIds(): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeStructure($this->structure);
    }

    protected function accessibleStructureIds(): array
    {
        if (! is_null($this->accessibleStructureCache)) {
            return $this->accessibleStructureCache;
        }

        return $this->accessibleStructureCache = resolve(StructureService::class)->getAccessibleStructures();
    }

    protected function personnelQuery(bool $withStructureTree = true): Builder
    {
        return app(PersonnelQueryService::class)->build(
            status: $this->status,
            filters: $this->filters,
            selectedStructureIds: $this->selectedStructureIds(),
            accessibleStructureIds: $this->accessibleStructureIds(),
            selectedPosition: $this->selectedPosition,
            withStructureTree: $withStructureTree,
        );
    }

    protected function personnelExportRows(): iterable
    {
        return app(PersonnelQueryService::class)->buildExport(
            status: $this->status,
            filters: $this->filters,
            selectedStructureIds: $this->selectedStructureIds(),
            accessibleStructureIds: $this->accessibleStructureIds(),
            selectedPosition: $this->selectedPosition,
        )
            ->cursor()
            ->map(fn (Personnel $personnel): array => [
                'name' => $personnel->name,
                'surname' => $personnel->surname,
                'patronymic' => $personnel->patronymic,
            ]);
    }

    protected function normalizeStructureState(mixed $value): array
    {
        return app(PersonnelListStateNormalizer::class)->normalizeStructure($value);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.all-personnel');
    }
}
