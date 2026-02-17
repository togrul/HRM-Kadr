<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Models\Structure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class SetPermission extends Component
{
    public $title;

    public $roleModel;

    public $permissionList = [];

    public array $permissionIdPool = [];

    public $selectAll = false;

    public $role;

    public $permissionStructureList = [];

    public array $structureIdPool = [];

    public array $structureNestedMap = [];

    public $selectAllStructure = false;

    public $structures;

    public array $permissions = [];

    public function mount()
    {
        $this->initializeProperties();
        $this->loadRoleData();
        $this->preloadPermissionData();
        $this->preloadStructureData();
        $this->syncSelectedLists();
    }

    private function initializeProperties(): void
    {
        $this->selectAll = false;
        $this->selectAllStructure = false;
    }

    private function loadRoleData(): void
    {
        $this->role = Role::findOrFail($this->roleModel);
        $this->title = __('Set permission').' - '."<span class='text-blue-500'>{$this->role->name}</span>";
    }

    public function store()
    {
        DB::transaction(function () {
            $this->updateRoleData();
        });

        $this->clearCacheAndSelections();
        $this->dispatch('permissionSet', __('Permission was added to role successfully!'));
    }

    private function updateRoleData(): void
    {
        $permissionIds = $this->normalizeIdList($this->permissionList, $this->permissionIdPool);
        $structureIds = $this->normalizeIdList($this->permissionStructureList, $this->structureIdPool);

        $this->role->structures()->sync($structureIds);
        $this->role->permissions()->sync($permissionIds);

        $this->permissionList = $permissionIds;
        $this->permissionStructureList = $structureIds;
        $this->syncSelectAllFlags();
    }

    private function clearCacheAndSelections(): void
    {
        Cache::forget('structures');
        Cache::forget("structure-accessible-".auth()->user()->id);
        $this->initializeProperties();
    }

    public function updatedSelectAll($value): void
    {
        $this->permissionList = $value ? $this->permissionIdPool : [];
    }

    public function updatingSelectAllStructure($value): void
    {
        $this->permissionStructureList = $value ? $this->structureIdPool : [];
    }

    public function updatePermissionStructureList(int $id): void
    {
        $id = (int) $id;
        $nestedIds = $this->structureNestedMap[$id] ?? [$id];
        $isCurrentlySelected = in_array($id, $this->permissionStructureList, true);

        if ($isCurrentlySelected) {
            $this->permissionStructureList = array_values(
                array_unique(array_merge($this->permissionStructureList, $nestedIds))
            );
        } else {
            $this->permissionStructureList = array_values(array_diff($this->permissionStructureList, $nestedIds));
        }

        $this->permissionStructureList = $this->normalizeIdList($this->permissionStructureList, $this->structureIdPool);
    }

    public function render()
    {
        return view('services::livewire.services.roles.set-permission', [
            'permissions' => $this->permissions,
        ]);
    }

    private function preloadPermissionData(): void
    {
        $permissions = Permission::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->permissionIdPool = $permissions
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->permissions = $this->groupPermissionsByModule($permissions);
    }

    private function preloadStructureData(): void
    {
        $allStructures = Structure::query()
            ->select('id', 'parent_id', 'name', 'shortname', 'code')
            ->orderBy('code')
            ->get();

        $this->structures = $allStructures
            ->where('parent_id', 1)
            ->values();

        $this->structureIdPool = $allStructures
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->structureNestedMap = $this->buildStructureNestedMap($allStructures);
    }

    private function syncSelectedLists(): void
    {
        $this->permissionList = $this->normalizeIdList(
            $this->role->permissions()->pluck('permissions.id')->all(),
            $this->permissionIdPool
        );

        $this->permissionStructureList = $this->normalizeIdList(
            $this->role->structures()->pluck('structures.id')->all(),
            $this->structureIdPool
        );

        $this->syncSelectAllFlags();
    }

    private function syncSelectAllFlags(): void
    {
        $this->selectAll = count($this->permissionIdPool) > 0
            && count($this->permissionList) === count($this->permissionIdPool);
        $this->selectAllStructure = count($this->structureIdPool) > 0
            && count($this->permissionStructureList) === count($this->structureIdPool);
    }

    private function normalizeIdList(array $ids, array $allowedIds): array
    {
        $allowedLookup = array_flip($allowedIds);

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && isset($allowedLookup[$id]))
            ->unique()
            ->values()
            ->all();
    }

    private function groupPermissionsByModule(EloquentCollection $permissions): array
    {
        return $permissions->reduce(function (array $carry, Permission $permission) {
            [$method, $module] = array_pad(explode('-', (string) $permission->name, 2), 2, null);

            if (blank($method) || blank($module)) {
                return $carry;
            }

            if (! isset($carry[$module])) {
                $carry[$module] = [];
            }

            $carry[$module][$method] = [
                'id' => (int) $permission->id,
                'name' => $permission->name,
                'title' => $method,
            ];

            return $carry;
        }, []);
    }

    private function buildStructureNestedMap(EloquentCollection $structures): array
    {
        $childrenMap = [];

        foreach ($structures as $structure) {
            $parentId = (int) ($structure->parent_id ?? 0);
            $childrenMap[$parentId][] = (int) $structure->id;
        }

        $resolved = [];
        $resolve = function (int $id) use (&$resolve, &$resolved, $childrenMap): array {
            if (isset($resolved[$id])) {
                return $resolved[$id];
            }

            $nested = [$id];
            foreach ($childrenMap[$id] ?? [] as $childId) {
                $nested = array_merge($nested, $resolve($childId));
            }

            return $resolved[$id] = array_values(array_unique($nested));
        };

        foreach ($structures as $structure) {
            $resolve((int) $structure->id);
        }

        return $resolved;
    }
}
