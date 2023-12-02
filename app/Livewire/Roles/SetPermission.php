<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetPermission extends Component
{
    public $title;
    public $roleModel;
    public $permissionList = [];
    public $selectAll;
    public $role;

    public function mount()
    {
        $this->selectAll = false;
        $this->role = Role::where('id',$this->roleModel)->first();
        $this->title = __('Set permission').' - '."<span class='text-blue-500'>{$this->role->name}</span>";

        $this->permissionList = $this->role
            ->permissions()
            ->get()
            ->pluck('id')
            ->toArray();
    }

    public function store()
    {
        $this->role->syncPermissions($this->permissionList);

        $this->selectAll = false;

        // $this->dispatchBrowserEvent('refresh-page');
        $this->dispatch('permissionSet',__('Permission was added to role successfully!'));
    }

    public function updatedSelectAll($value)
    {
        if ($value)
            $this->permissionList = Permission::pluck('id')->toArray();
        else
            $this->permissionList = [];
    }

    public function render()
    {
        return view('livewire.roles.set-permission',[
            'permissions' => Permission::all()
        ]);
    }
}
