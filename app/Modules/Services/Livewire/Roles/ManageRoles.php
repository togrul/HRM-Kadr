<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Livewire\Traits\SideModalAction;
use App\Models\Role;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use App\Support\Permissions\RoleTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['permissionSet', 'roleWasDeleted'])]
class ManageRoles extends Component
{
    use AuthorizesRequests, SideModalAction;
    use AuthorizesSettingsAccess;

    public $role_name;

    public $role_id;

    public $isUpdate;

    public bool $isCreating = false;

    protected function rules(): array
    {
        return [
            'role_name' => [
                'required',
                Rule::unique('roles', 'name')->ignore($this->role_id),
            ],
        ];
    }

    public function editRole($id): void
    {
        $this->isUpdate = true;
        $this->isCreating = false;
        $this->resetErrorBag();
        $this->getRoleByID($id);
    }

    public function startCreate(): void
    {
        $this->isCreating = true;
        $this->isUpdate = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function setDeleteRole($roleId): void
    {
        $this->dispatch('setDeleteRole', $roleId);
    }

    public function store(): void
    {
        $this->validate();

        $payload = ['name' => $this->role_name];

        if ($this->role_id) {
            Role::findOrFail($this->role_id)->update($payload);
        } else {
            Role::create($payload);
        }

        $this->dispatch('roleUpdated', __('services::roles.messages.role_saved'));
        $this->cancel();
    }

    public function sendRole($id): void
    {
        $this->getRoleByID($id);
        $this->openSidebar();
        $this->dispatch('getRole', $id);
        $this->resetErrorBag();
    }

    private function getRoleByID($id): void
    {
        $role = Role::findOrFail($id);
        $this->role_id = $id;
        $this->role_name = $role->name;
    }

    public function cancel(): void
    {
        $this->isUpdate = false;
        $this->isCreating = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    private function resetInputFields(): void
    {
        $this->role_name = '';
        $this->role_id = null;
    }

    public function roleDisplayName(Role $role): string
    {
        return RoleTranslation::label((string) $role->name);
    }

    public function render(): View
    {
        $roles = Role::query()
            ->select('id', 'name', 'guard_name')
            ->withCount(['permissions', 'users'])
            ->with([
                'users' => fn ($query) => $query
                    ->select('users.id', 'users.name', 'users.email')
                    ->orderBy('users.name')
                    ->limit(6),
            ])
            ->orderBy('name')
            ->get();

        return view('services::livewire.services.roles.manage-roles', compact('roles'));
    }
}
