<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class AddUser extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;

    public $title;

    public $user = [];

    public ?int $roleId = null;

    public string $searchRole = '';

    protected function rules()
    {
        return [
            'user.name' => 'required|string|min:1',
            'user.email' => 'required|email|unique:users,email',
            'user.password' => 'required|min:4',
            'user.confirm-password' => 'required_with:user.password|same:user.password|min:4',
            'roleId' => 'required|exists:roles,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'user.name' => __('services::common.labels.name'),
            'user.email' => __('services::common.labels.email'),
            'user.password' => __('services::common.labels.password'),
            'user.confirm-password' => __('services::common.labels.confirm_password'),
            'roleId' => __('services::common.labels.role'),
        ];
    }

    public function store()
    {
        $this->validate();

        $this->user['password'] = Hash::make($this->user['password']);

        $user = User::create($this->user);
        if ($this->roleId) {
            $role = Role::find($this->roleId);
            if ($role) {
                $user->assignRole($role->name);
            }
        }

        $this->dispatch('userAdded', __('services::users.messages.created'));
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('services::users.titles.add');
        $this->roleId = null;
    }

    public function render()
    {
        return view('services::livewire.services.users.add-user');
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
