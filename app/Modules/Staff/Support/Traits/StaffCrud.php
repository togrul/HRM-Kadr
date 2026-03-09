<?php

namespace App\Modules\Staff\Support\Traits;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Livewire\Traits\DropdownConstructTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait StaffCrud
{
    use DropdownConstructTrait;

    public $title;

    public string $searchStructure = '';

    public string $searchPosition = '';

    public $staff = [];

    public $staffModel;

    public $hidePosition = false;

    public ?int $structureId = null;

    /**
     * Simple per-request caches to avoid repeating lookups.
     */
    protected array $positionLabels = [];
    protected array $structureParents = [];
    protected array $structureNestedIds = [];
    protected ?array $structureChildrenByParent = null;

    public function rules(): array
    {
        $positionRule = $this->hidePosition ? 'nullable' : 'required|int|exists:positions,id';

        return [
            'staff.*.structure_id' => 'required|int|exists:structures,id',
            'staff.*.position_id' => $positionRule,
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

    public function addRow()
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

        $this->hidePositionAction($nextKey);
    }

    public function deleteRow($row)
    {
        unset($this->staff[$row]);
    }

    public function setData($array_key, $model, $key, $content, $name, $id)
    {
        $this->searchPosition = '';
        $this->{$model}[$array_key][$key] = $id;
        $this->{$model}[$array_key][$content] = [
            'id' => $id,
            'name' => $name ?? '---',
        ];
        $this->fillAutoData($array_key, $model);
    }

    protected function hidePositionAction($array_key)
    {
        $structureId = $this->staff[$array_key]['structure_id'] ?? null;
        $parent_id = $structureId ? $this->resolveParentId((int) $structureId) : null;
        $this->hidePosition = empty($parent_id);
    }

    protected function fillAutoData($array_key, $model)
    {
        if (empty($this->staff)) {
            return;
        }

        $this->hidePosition = false;
        if (empty($this->staffModel) && $this->structureId) {
            $this->staff[$array_key]['structure_id'] = $this->structureId;
        }

        if (! Arr::has($this->staff[$array_key], ['structure_id', 'position_id'])) {
            return;
        }

        if ($model === 'structureId') {
            $this->hidePositionAction($array_key);
            $this->staff[$array_key]['position_id'] = null;
            $this->staff[$array_key]['position'] = [
                'id' => null,
                'name' => '---',
            ];
        }

        $this->recalculateFilledCounts($array_key);
    }

    public function updatedStructureId($value): void
    {
        foreach ($this->staff as $index => $row) {
            $this->staff[$index]['structure_id'] = $value;
            $this->hidePositionAction($index);
            $this->staff[$index]['position_id'] = null;
            $this->staff[$index]['position'] = [
                'id' => null,
                'name' => '---',
            ];
        }

        $this->syncComputedStaffRows();
    }

    public function render()
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

    protected function recalculateFilledCounts(int $index): void
    {
        if (! array_key_exists($index, $this->staff)) {
            return;
        }

        $structureId = (int) ($this->staff[$index]['structure_id'] ?? $this->structureId ?? 0);
        $positionId = (int) ($this->staff[$index]['position_id'] ?? 0);

        if ($structureId <= 0) {
            $this->staff[$index]['filled'] = 0;
            $this->recalculateVacant($index);

            return;
        }

        $query = Personnel::query()->active();

        if ($positionId > 0 && ! $this->hidePosition) {
            $query->where('structure_id', $structureId)
                ->where('position_id', $positionId);
        } else {
            $structureIds = $this->resolveStructureTreeIds($structureId);
            $query->whereIn('structure_id', $structureIds);
        }

        $this->staff[$index]['filled'] = $query->count();
        $this->recalculateVacant($index);
    }

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

        if (empty($structureIds)) {
            foreach ($indexes as $index) {
                $this->staff[$index]['filled'] = 0;
                $this->recalculateVacant($index);
            }

            return;
        }

        $nestedIdsByStructure = $this->buildNestedIdsByStructure($structureIds);
        $relevantStructureIds = collect($nestedIdsByStructure)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $activeByStructure = Personnel::query()
            ->active()
            ->whereIn('structure_id', $relevantStructureIds)
            ->select('structure_id', DB::raw('count(*) as aggregate'))
            ->groupBy('structure_id')
            ->pluck('aggregate', 'structure_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $positionIds = collect($indexes)
            ->map(fn (int $index) => (int) ($this->staff[$index]['position_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $activeByStructurePosition = [];
        if (! empty($positionIds) && ! $this->hidePosition) {
            $activeByStructurePosition = Personnel::query()
                ->active()
                ->whereIn('structure_id', $structureIds)
                ->whereIn('position_id', $positionIds)
                ->select('structure_id', 'position_id', DB::raw('count(*) as aggregate'))
                ->groupBy('structure_id', 'position_id')
                ->get()
                ->reduce(function (array $carry, $row) {
                    $structureId = (int) $row->structure_id;
                    $positionId = (int) $row->position_id;
                    $carry[$structureId][$positionId] = (int) $row->aggregate;

                    return $carry;
                }, []);
        }

        foreach ($indexes as $index) {
            $structureId = (int) ($this->staff[$index]['structure_id'] ?? $this->structureId ?? 0);
            $positionId = (int) ($this->staff[$index]['position_id'] ?? 0);

            if ($structureId <= 0) {
                $this->staff[$index]['filled'] = 0;
                $this->recalculateVacant($index);

                continue;
            }

            if ($positionId > 0 && ! $this->hidePosition) {
                $filled = (int) ($activeByStructurePosition[$structureId][$positionId] ?? 0);
            } else {
                $filled = 0;
                foreach ($nestedIdsByStructure[$structureId] ?? [$structureId] as $nestedId) {
                    $filled += (int) ($activeByStructure[(int) $nestedId] ?? 0);
                }
            }

            $this->staff[$index]['filled'] = $filled;
            $this->recalculateVacant($index);
        }
    }

    protected function resolveStructureTreeIds(int $structureId): array
    {
        if (! isset($this->structureNestedIds[$structureId])) {
            $nested = $this->buildNestedIdsByStructure([$structureId]);
            $this->structureNestedIds[$structureId] = $nested[$structureId] ?? [$structureId];
        }

        return $this->structureNestedIds[$structureId];
    }

    protected function buildNestedIdsByStructure(array $structureIds): array
    {
        if (empty($structureIds)) {
            return [];
        }

        $childrenByParent = $this->resolveChildrenByParent();
        $memo = $this->structureNestedIds;

        $collectNestedIds = function (int $id) use (&$collectNestedIds, &$memo, $childrenByParent): array {
            if (isset($memo[$id])) {
                return $memo[$id];
            }

            $ids = [$id];
            foreach ($childrenByParent[$id] ?? [] as $childId) {
                $ids = array_merge($ids, $collectNestedIds((int) $childId));
            }

            return $memo[$id] = array_values(array_unique($ids));
        };

        $nestedIdsByStructure = [];
        foreach ($structureIds as $structureId) {
            $structureId = (int) $structureId;
            if ($structureId <= 0) {
                continue;
            }

            $nestedIdsByStructure[$structureId] = $collectNestedIds($structureId);
        }

        $this->structureNestedIds = $memo;

        return $nestedIdsByStructure;
    }

    protected function resolveChildrenByParent(): array
    {
        if ($this->structureChildrenByParent !== null) {
            return $this->structureChildrenByParent;
        }

        $this->structureChildrenByParent = Structure::query()
            ->select('id', 'parent_id')
            ->get()
            ->reduce(function (array $carry, Structure $structure) {
                $parentId = (int) ($structure->parent_id ?? 0);
                $carry[$parentId][] = (int) $structure->id;

                return $carry;
            }, []);

        return $this->structureChildrenByParent;
    }

    protected function resolveParentId(int $structureId): ?int
    {
        if (! array_key_exists($structureId, $this->structureParents)) {
            $this->structureParents[$structureId] = Structure::whereKey($structureId)->value('parent_id');
        }

        return $this->structureParents[$structureId];
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
            $this->recalculateFilledCounts($index);
            return;
        }

        if ($field === 'position_id') {
            $label = $this->resolvePositionName($value);
            $this->staff[$index]['position'] = [
                'id' => $value ?: null,
                'name' => $label,
            ];

            $this->recalculateFilledCounts($index);

            return;
        }

        if ($field === 'structure_id') {
            $this->hidePositionAction($index);
            $this->staff[$index]['position_id'] = null;
            $this->staff[$index]['position'] = [
                'id' => null,
                'name' => '---',
            ];

            $this->recalculateFilledCounts($index);
        }
        
    }
}
