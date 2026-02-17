<?php

namespace App\Modules\Services\Livewire\Roles;

use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

#[On('permissionWasDeleted')]
class Permissions extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $permission_id;

    public $permission_name;

    public string $search = '';

    public int $perPage = 25;

    protected function rules(): array
    {
        return [
            'permission_name' => [
                'required',
                Rule::unique('permissions', 'name')->ignore($this->permission_id),
            ],
        ];
    }

    public function editPermission($id)
    {
        $permission = Permission::findOrFail($id);
        $this->permission_id = $id;
        $this->permission_name = $permission->name;
    }

    public function store()
    {
        $this->validate();
        $data = ['name' => $this->permission_name];

        if ($this->permission_id) {
            Permission::findOrFail($this->permission_id)->update($data);
        } else {
            Permission::create($data);
        }

        $this->dispatch('permissionUpdated', __('Permission was added successfully!'));
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function setDeletePermission($permissionId)
    {
        $this->dispatch('setDeletePermission', $permissionId);
    }

    public function cancel()
    {
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function resetInputFields()
    {
        $this->permission_name = '';
        $this->permission_id = null;
    }

    public function render()
    {
        $permissions = Permission::query()
            ->select('id', 'name')
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('services::livewire.services.roles.permissions', compact('permissions'));
    }
}
