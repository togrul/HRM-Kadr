<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\User;
use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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

    protected function rules(): array
    {
        $rules = [
            'user.name' => 'required|min:1',
            'user.email' => 'required|email|unique:users,email,'.$this->userModel->id,
            'roleId' => 'required|exists:roles,id',
        ];

        // Only validate password fields when a new password is actually supplied.
        if (! empty($this->user['password'])) {
            $rules['user.password'] = ['required', Password::min(8), 'different:user.old_password'];
            $rules['user.confirm-password'] = 'required|same:user.password';

            // Users changing their OWN password must prove the current one.
            if ((int) $this->userModel->id === (int) auth()->id()) {
                $rules['user.old_password'] = ['required', function ($attribute, $value, $fail) {
                    if (! Hash::check((string) $value, $this->userModel->password)) {
                        $fail(__('services::users.messages.old_password_mismatch'));
                    }
                }];
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'user.name' => __('services::common.labels.name'),
            'user.email' => __('services::common.labels.email'),
            'user.password' => __('services::common.labels.password'),
            'user.confirm-password' => __('services::common.labels.confirm_password'),
            'roleId' => __('services::common.labels.role'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('access-settings');
        $this->title = __('services::users.titles.edit');
        $userId = is_array($this->userModel)
            ? ($this->userModel['id'] ?? null)
            : $this->userModel;

        $this->userModel = User::where('id', $userId)->firstOrFail();
        $this->userModel->load('roles');
        $role = $this->userModel->roles->first();
        $this->roleId = $role?->id;

        $this->user['name'] = trim((string) $this->userModel->name);
        $this->user['email'] = trim((string) $this->userModel->email);
        $this->user['is_active'] = (bool) $this->userModel->is_active;
    }

    public function store(): void
    {
        $this->authorize('access-settings');

        // Livewire updates skip the HTTP TrimStrings middleware, so trim here — a stray
        // trailing space would otherwise fail the `email` rule on otherwise-valid input.
        $this->user['name'] = trim((string) ($this->user['name'] ?? ''));
        $this->user['email'] = trim((string) ($this->user['email'] ?? ''));

        $this->validate();

        // Whitelist updatable columns; never mass-assign the raw client array.
        $payload = [
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            'is_active' => (bool) ($this->user['is_active'] ?? false),
        ];

        if (! empty($this->user['password'])) {
            $payload['password'] = Hash::make($this->user['password']);
        }

        $this->userModel->update($payload);
        if ($this->roleId) {
            $this->userModel->roles()->sync($this->roleId);
        }

        $this->dispatch('userAdded', __('services::users.messages.updated'));
    }

    public function render(): View
    {
        return view('services::livewire.services.users.edit-user');
    }

    #[Computed]
    public function roleOptions(): array
    {
        $selected = $this->roleId;
        $search = $this->dropdownSearch('searchRole');

        $base = Role::query()
            ->select('id', DB::raw('name as label'))
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
