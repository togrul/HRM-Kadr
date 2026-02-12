<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Models\Structure;
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

    public $selectAll = false;

    public $role;

    public $permissionStructureList = [];

    public $selectAllStructure = false;

    public $structures;

    public function mount()
    {
        $this->initializeProperties();
        $this->loadRoleData();
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

        $this->permissionList = $this->role->permissions()->pluck('id')->toArray();
        $this->permissionStructureList = $this->role->structures()->pluck('structure_id')->toArray();
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
        $this->role->structures()->sync($this->permissionStructureList);
        $this->role->syncPermissions($this->permissionList);
    }

    private function clearCacheAndSelections(): void
    {
        Cache::forget('structures');
        Cache::forget("structure-accessible-".auth()->user()->id);
        $this->initializeProperties();
    }

    public function updatedSelectAll($value): void
    {
        $this->permissionList = $value
            ? Permission::pluck('id')->toArray()
            : [];
    }

    public function updatingSelectAllStructure($value): void
    {
        $this->permissionStructureList = $value
            ? Structure::withRecursive('subs')->pluck('id')->toArray()
            : [];
    }

    public function updatePermissionStructureList(int $id): void
    {
        $structure = Structure::find($id);

        if ($structure) {
            $nestedIds = $structure->getAllNestedIds();
            $isCurrentlySelected = in_array($structure->id, $this->permissionStructureList);

            if ($isCurrentlySelected) {
                $this->permissionStructureList = array_values(
                    array_unique(
                        array_merge($this->permissionStructureList, $nestedIds)
                    )
                );
            } else {
                $this->permissionStructureList = array_values(
                    array_diff($this->permissionStructureList, $nestedIds)
                );
            }
        }
    }

    public function render()
    {
        $this->loadStructures();
        $permissions = $this->groupPermissionsByModule();

        return view('services::livewire.services.roles.set-permission', [
            'permissions' => $permissions,
        ]);
    }

    private function loadStructures(): void
    {
        $this->structures = Structure::whereNotNull('parent_id')
            ->where('parent_id', 1)
            ->orderBy('code')
            ->get();
    }

    private function groupPermissionsByModule(): array
    {
        return Permission::all()->groupBy('name')->reduce(function ($carry, $collection, $key) {
            [$method, $module] = explode('-', $key);

            if (! isset($carry[$module])) {
                $carry[$module] = [];
            }

            $carry[$module][$method] = [
                'id' => $collection[0]->id,
                'name' => $collection[0]->name,
                'title' => $method,
            ];

            return $carry;
        }, []);
    }
}
