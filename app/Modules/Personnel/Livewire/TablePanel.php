<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Services\PersonnelRowActionService;
use App\Modules\Personnel\Services\PersonnelRowViewModelService;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Isolate;
use Livewire\Component;
use Livewire\WithPagination;

#[Isolate]
class TablePanel extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $status = 'current';

    public array $filters = [];

    public array $structure = [];

    public ?int $selectedPosition = null;

    protected ?array $accessibleStructureCache = null;

    public function mount(string $status = 'current', array $filters = [], array $structure = [], ?int $selectedPosition = null): void
    {
        $this->authorize('viewAny', Personnel::class);
        $this->status = $status;
        $this->filters = $this->normalizeFilterPayload($filters);
        $this->structure = $this->normalizeStructureState($structure);
        $this->selectedPosition = $selectedPosition;
    }

    public function placeholder()
    {
        return view('personnel::livewire.personnel.placeholders.table-panel');
    }

    public function handleRowAction(string $type, mixed $payload = null): void
    {
        $this->dispatch('personnelTableRowAction', type: $type, payload: $payload);
    }

    public function rowActions(Personnel $personnel): array
    {
        return app(PersonnelRowActionService::class)->build($personnel, $this->status);
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

    public function getPersonnelsProperty(): LengthAwarePaginator
    {
        return app(PersonnelRowViewModelService::class)->decoratePaginator(
            $this->personnelQuery()->paginate(10, pageName: 'personnelPage')->withQueryString()
        );
    }

    protected function personnelQuery(): Builder
    {
        return app(PersonnelQueryService::class)->build(
            status: $this->status,
            filters: $this->filters,
            selectedStructureIds: $this->structure,
            accessibleStructureIds: $this->accessibleStructureIds(),
            selectedPosition: $this->selectedPosition,
            withStructureTree: true
        );
    }

    protected function accessibleStructureIds(): array
    {
        if (! is_null($this->accessibleStructureCache)) {
            return $this->accessibleStructureCache;
        }

        return $this->accessibleStructureCache = resolve(StructureService::class)->getAccessibleStructures();
    }

    protected function normalizeStructureState(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === ''
                ? []
                : array_values(array_filter(array_map('intval', explode(',', $value))));
        }

        return [];
    }

    protected function normalizeFilterPayload(array $filters): array
    {
        return array_filter($filters, fn ($value, $key) => $value !== null && $value !== '' && $value !== [] && $key !== '__identity', ARRAY_FILTER_USE_BOTH);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.table-panel');
    }
}
