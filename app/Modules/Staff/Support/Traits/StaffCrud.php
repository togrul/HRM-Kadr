<?php

namespace App\Modules\Staff\Support\Traits;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Staff\Application\Services\StaffHeadcountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

trait StaffCrud
{
    use DropdownConstructTrait;

    public $title;

    public string $searchStructure = '';

    public string $searchPosition = '';

    public $staff = [];

    public $staffModel;

    public ?int $structureId = null;

    /**
     * Simple per-request caches to avoid repeating lookups.
     */
    protected array $positionLabels = [];

    protected ?array $allowedStructureIdsCache = null;

    protected ?array $allowedPositionIdsCache = null;

    public function rules(): array
    {
        // Every row needs a position — top-level units included. A row without one cannot
        // be matched to anybody, and the old "establishment total" rows on top-level units
        // were counted on top of the position rows beneath them (Dolu twice the headcount).
        return [
            'staff.*.structure_id' => ['required', 'integer', Rule::in($this->allowedStructureIds())],
            'staff.*.position_id' => ['required', 'integer', Rule::in($this->allowedPositionIds())],
            'staff.*.total' => 'required|integer|min:0',
            'staff.*.filled' => 'required|integer|min:0',
            'staff.*.vacant' => 'required|integer|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'staff.*.structure_id' => __('staff::common.fields.structure'),
            'staff.*.position_id' => __('staff::common.fields.position'),
            'staff.*.total' => __('staff::common.fields.total'),
            'staff.*.filled' => __('staff::common.fields.filled'),
            'staff.*.vacant' => __('staff::common.fields.vacant'),
        ];
    }

    public function updated($propertyName, $value): void
    {
        if (is_string($propertyName) && str_starts_with($propertyName, 'staff.')) {
            $this->handleStaffPropertyUpdate($propertyName, $value);
        }
    }

    protected function resolvePositionName($id): string
    {
        if (empty($id)) {
            return '---';
        }

        if (! isset($this->positionLabels[$id])) {
            $this->positionLabels[$id] = Position::whereKey($id)->value('name') ?? '---';
        }

        return $this->positionLabels[$id];
    }

    public function addRow(): void
    {
        $structureId = $this->staffModel ?? $this->structureId;

        $lastKey = array_key_last($this->staff);
        $nextKey = is_null($lastKey) ? 0 : $lastKey + 1;

        $this->staff[$nextKey] = [
            'structure_id' => $structureId,
            'position_id' => null,
            'total' => 0,
            'filled' => 0,
            'vacant' => 0,
            'position' => [
                'id' => null,
                'name' => '---',
            ],
        ];
    }

    public function deleteRow($row): void
    {
        unset($this->staff[$row]);
    }

    public function setData($array_key, $model, $key, $content, $name, $id): void
    {
        $this->searchPosition = '';
        $this->{$model}[$array_key][$key] = $id;
        $this->{$model}[$array_key][$content] = [
            'id' => $id,
            'name' => $name ?? '---',
        ];
        $this->fillAutoData($array_key, $model);
    }

    protected function fillAutoData($array_key, $model): void
    {
        if (empty($this->staff)) {
            return;
        }

        if (empty($this->staffModel) && $this->structureId) {
            $this->staff[$array_key]['structure_id'] = $this->structureId;
        }

        if (! Arr::has($this->staff[$array_key], ['structure_id', 'position_id'])) {
            return;
        }

        if ($model === 'structureId') {
            $this->staff[$array_key]['position_id'] = null;
            $this->staff[$array_key]['position'] = [
                'id' => null,
                'name' => '---',
            ];
        }

        $this->syncComputedStaffRows();
    }

    public function updatedStructureId($value): void
    {
        foreach ($this->staff as $index => $row) {
            $this->staff[$index]['structure_id'] = $value;
            $this->staff[$index]['position_id'] = null;
            $this->staff[$index]['position'] = [
                'id' => null,
                'name' => '---',
            ];
        }

        $this->syncComputedStaffRows();
    }

    public function render(): View
    {
        $view_name = ! empty($this->staffModel)
            ? 'staff::livewire.staff-schedule.edit-staff'
            : 'staff::livewire.staff-schedule.add-staff';

        return view($view_name);
    }

    #[\Livewire\Attributes\Computed]
    public function structureOptions(): array
    {
        $selected = $this->staffModel ?? $this->structureId;
        $search = $this->dropdownSearch('searchStructure');

        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->orderBy('code');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'staff:structures',
                base: $base,
                selectedId: $selected,
                limit: 100
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 100
        );
    }

    #[\Livewire\Attributes\Computed]
    public function positionOptions(): array
    {
        $search = $this->dropdownSearch('searchPosition');

        $base = Position::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'staff:positions',
                base: $base,
                selectedId: null,
                limit: 100
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: null,
            limit: 100
        );
    }

    protected function recalculateVacant(int $index, ?int $overriddenTotal = null): void
    {
        $total = $overriddenTotal ?? (int) ($this->staff[$index]['total'] ?? 0);
        $filled = (int) ($this->staff[$index]['filled'] ?? 0);

        $this->staff[$index]['total'] = $total;
        $this->staff[$index]['vacant'] = max(0, $total - $filled);
    }

    /**
     * Dolu for every form row from the live headcount of its exact (structure, position) —
     * the same figure the ştat tree shows. One grouped query for all rows.
     */
    protected function syncComputedStaffRows(): void
    {
        $indexes = collect(array_keys($this->staff))
            ->filter(fn ($index) => is_numeric($index))
            ->map(fn ($index) => (int) $index)
            ->values()
            ->all();

        if (empty($indexes)) {
            return;
        }

        $structureIds = collect($indexes)
            ->map(fn (int $index) => (int) ($this->staff[$index]['structure_id'] ?? $this->structureId ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $counts = $structureIds === [] ? [] : app(StaffHeadcountService::class)->activeCounts($structureIds);

        foreach ($indexes as $index) {
            $structureId = (int) ($this->staff[$index]['structure_id'] ?? $this->structureId ?? 0);
            $positionId = (int) ($this->staff[$index]['position_id'] ?? 0);

            $this->staff[$index]['filled'] = $structureId > 0 && $positionId > 0
                ? (int) ($counts[$structureId][$positionId] ?? 0)
                : 0;
            $this->recalculateVacant($index);
        }
    }

    protected function handleStaffPropertyUpdate(string $propertyName, $value): void
    {
        $segments = explode('.', $propertyName);
        $index = (int) ($segments[1] ?? -1);
        $field = $segments[2] ?? null;

        if (! array_key_exists($index, $this->staff) || $field === null) {
            return;
        }

        if ($field === 'total') {
            $this->recalculateVacant($index, (int) $value);
            $this->syncComputedStaffRows();

            return;
        }

        if ($field === 'position_id') {
            $label = $this->resolvePositionName($value);
            $this->staff[$index]['position'] = [
                'id' => $value ?: null,
                'name' => $label,
            ];

            $this->syncComputedStaffRows();

            return;
        }

        if ($field === 'structure_id') {
            $this->staff[$index]['position_id'] = null;
            $this->staff[$index]['position'] = [
                'id' => null,
                'name' => '---',
            ];

            $this->syncComputedStaffRows();
        }
    }

    protected function allowedStructureIds(): array
    {
        if ($this->allowedStructureIdsCache === null) {
            $this->allowedStructureIdsCache = Structure::query()
                ->accessible()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->allowedStructureIdsCache;
    }

    protected function allowedPositionIds(): array
    {
        if ($this->allowedPositionIdsCache === null) {
            $this->allowedPositionIdsCache = Position::query()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->allowedPositionIdsCache;
    }
}
