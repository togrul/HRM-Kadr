<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class EditUser extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;

    public $userModel;

    public $title;

    public $user;

    public ?int $roleId = null;

    public string $searchRole = '';

    protected function rules()
    {
        $rules = [
            'user.name' => 'required|min:1',
            'user.email' => 'required|unique:users,email,'.$this->userModel->id,
            'roleId' => 'required|exists:roles,id',
        ];

        if (! empty($this->user['old_password'])) {
            if (Hash::check($this->user['old_password'], $this->userModel->password)) {
                $rules['user.old_password'] = 'required|min:4';
                $rules['user.password'] = 'required|min:4|different:user.old_password';
            } else {
                $rules['user.old_password'] = ['required', function ($attribute, $value, $fail) {
                    if (! Hash::check($this->user['old_password'], $this->userModel->password)) {
                        $fail(__('Old Password didn\'t match'));
                    }
                }, ];
            }
        }

        return $rules;
    }

    protected function validationAttributes()
    {
        return [
            'user.name' => __('Name'),
            'user.email' => __('Email'),
            'user.password' => __('Password'),
            'user.confirm-password' => __('Confirm password'),
            'roleId' => __('Role'),
        ];
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Edit user');
        $userId = is_array($this->userModel)
            ? ($this->userModel['id'] ?? null)
            : $this->userModel;

        $this->userModel = User::where('id', $userId)->firstOrFail();
        $this->userModel->load('roles');
        $role = $this->userModel->roles->first();
        $this->roleId = $role?->id;

        $this->user['name'] = $this->userModel->name;
        $this->user['email'] = $this->userModel->email;
        $this->user['is_active'] = (bool) $this->userModel->is_active;
    }

    public function store()
    {
        $this->validate();

        if (isset($this->user['password'])) {
            $this->user['password'] = Hash::make($this->user['password']);
        }

        $this->userModel->update($this->user);
        if ($this->roleId) {
            $this->userModel->roles()->sync($this->roleId);
        }

        $this->dispatch('userAdded', __('User was updated successfully!'));
    }

    public function render()
    {
        return view('services::livewire.services.users.edit-user');
    }

    #[Computed]
    public function roleOptions(): array
    {
        $selected = $this->roleId;
        $search = $this->dropdownSearch('searchRole');

        $base = Role::query()
            ->select('id', \DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'users:roles',
                base: $base,
                selectedId: $selected,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }
}
