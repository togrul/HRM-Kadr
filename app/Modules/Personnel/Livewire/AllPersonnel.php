<?php

namespace App\Modules\Personnel\Livewire;

use App\Modules\Personnel\Exports\PersonnelExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Modules\Personnel\Services\PersonnelLookupService;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Services\PersonnelRowViewModelService;
use App\Modules\Personnel\Services\PersonnelRowActionService;
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

    public function exportExcel()
    {
        $this->authorize('export', Personnel::class);

        $report['data'] = $this->returnData(type: 'excel');
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
        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Personnel::withTrashed()->where('tabel_no', $id)->first();

        if (! $model) {
            return;
        }

        $this->authorize('forceDelete', $model);

        $model->forceDelete();
        $this->dispatch('personnelWasDeleted', __('Personnel was deleted!'));
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
            ['key' => 'current', 'label' => __('Active')],
            ['key' => 'leaves', 'label' => __('Resigned')],
            ['key' => 'all', 'label' => __('All')],
            ['key' => 'deleted', 'label' => __('Deleted'), 'permission' => 'access-admin'],
            ['key' => 'pending', 'label' => __('Pending')],
        ];
    }

    public function getTableHeaders(): array
    {
        return [
            __('#'),
            __('Tabel'),
            __('Fullname'),
            __('Structure'),
            __('Date'),
            'action',
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
        $this->status = in_array($this->status, $this->allowedStatuses, true) ? $this->status : 'current';
        $this->filters ??= [];
        $this->structure = $this->normalizeStructureState($this->structure);
        $this->selectedPosition = is_numeric($this->selectedPosition) ? (int) $this->selectedPosition : null;
    }

    #[Computed]
    public function personnels()
    {
        return $this->returnData();
    }

    public function rowActions(Personnel $personnel): array
    {
        return app(PersonnelRowActionService::class)->build($personnel, $this->status);
    }

    protected function getSafeFilterPayload(): array
    {
        $filters = is_array($this->filters) ? $this->filters : [];

        return array_filter($filters, fn ($value, $key) => $value !== null && $value !== '' && $value !== [] && $key !== '__identity', ARRAY_FILTER_USE_BOTH);
    }

    protected function normalizeAndPersistFilters(array $filter): array
    {
        return array_filter($filter, fn ($value, $key) => $value !== null && $value !== '' && $value !== [] && $key !== '__identity', ARRAY_FILTER_USE_BOTH);
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

        if ($actionType === 'open' && ! $this->canEditPersonnels()) {
            return;
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

    protected function returnData($type = 'normal')
    {
        $query = $this->personnelQuery(withStructureTree: $type === 'normal');
        $rowViewModelService = app(PersonnelRowViewModelService::class);

        if ($type === 'normal') {
            return $rowViewModelService->decoratePaginator($query->paginate(10)->withQueryString());
        }

        return $query->cursor();
    }

    protected function personnelQuery(bool $withStructureTree = true): Builder
    {
        return app(PersonnelQueryService::class)->build(
            status: $this->status,
            filters: $this->filters,
            selectedStructureIds: $this->selectedStructureIds(),
            accessibleStructureIds: $this->accessibleStructureIds(),
            selectedPosition: $this->selectedPosition,
            withStructureTree: $withStructureTree
        );
    }

    protected function accessibleStructureIds(): array
    {
        if (! is_null($this->accessibleStructureCache)) {
            return $this->accessibleStructureCache;
        }

        return $this->accessibleStructureCache = resolve(StructureService::class)->getAccessibleStructures();
    }

    protected function selectedStructureIds(): array
    {
        return $this->normalizeStructureState($this->structure);
    }

    protected function normalizeStructureState(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        if (is_numeric($value)) {
            return [ (int) $value ];
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            return array_values(array_filter(array_map('intval', explode(',', $value))));
        }

        return [];
    }

    public function render()
    {
        return view('personnel::livewire.personnel.all-personnel');
    }
}
