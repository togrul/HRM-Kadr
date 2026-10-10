<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\User;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use App\Modules\Services\Livewire\Concerns\AuthorizesUserManagement;
use App\Services\UserAdministrationGuard;
use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EditUser extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;
    use AuthorizesUserManagement;
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
            $rules['user.password'] = ['required', Password::defaults(), 'different:user.old_password'];
            $rules['user.confirm-password'] = 'required|same:user.password';

            // Users changing their OWN password must prove the current one.
            if ($this->isSelf()) {
                $rules['user.old_password'] = ['required', function ($attribute, $value, $fail) {
                    if (! Hash::check((string) $value, $this->userModel->password)) {
                        $fail(__('services::users.messages.old_password_mismatch'));
                    }
                }];
            }
        }

        // Başqasının giriş məlumatını (e-poçt / şifrə) dəyişmək idarəçinin öz şifrəsi ilə təsdiqlənir.
        if (! $this->isSelf() && $this->changesCredentials()) {
            $rules['user.actor_password'] = ['required', function ($attribute, $value, $fail) {
                if (! app(UserAdministrationGuard::class)->passwordMatches(auth()->user(), (string) $value)) {
                    $fail(__('services::users.messages.actor_password_mismatch'));
                }
            }];
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
            'user.old_password' => __('services::common.labels.current_password'),
            'user.actor_password' => __('services::users.fields.actor_password'),
            'roleId' => __('services::common.labels.role'),
        ];
    }

    public function mount(): void
    {
        $this->authorize(UserAdministrationGuard::MANAGE_USERS);
        $this->title = __('services::users.titles.edit');
        $userId = is_array($this->userModel)
            ? ($this->userModel['id'] ?? null)
            : $this->userModel;

        $this->userModel = User::where('id', $userId)->firstOrFail();
        $this->authorizeTarget();
        $this->userModel->load('roles');
        $role = $this->userModel->roles->first();
        $this->roleId = $role?->id;

        $this->user['name'] = trim((string) $this->userModel->name);
        $this->user['email'] = trim((string) $this->userModel->email);
        $this->user['is_active'] = (bool) $this->userModel->is_active;
    }

    public function store(): void
    {
        $this->authorize(UserAdministrationGuard::MANAGE_USERS);
        $this->userModel = $this->userModel->fresh(['roles']) ?? abort(404);
        $this->authorizeTarget();

        // Livewire updates skip the HTTP TrimStrings middleware, so trim here — a stray
        // trailing space would otherwise fail the `email` rule on otherwise-valid input.
        $this->user['name'] = trim((string) ($this->user['name'] ?? ''));
        $this->user['email'] = trim((string) ($this->user['email'] ?? ''));

        $this->validate();
        $this->guardPrivilegeChanges();

        $isActive = (bool) ($this->user['is_active'] ?? false);

        // Whitelist updatable columns; never mass-assign the raw client array.
        $payload = [
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            'is_active' => $isActive,
        ];

        if (! empty($this->user['password'])) {
            $payload['password'] = Hash::make($this->user['password']);
        }

        // Şifrə dəyişəndə və ya hesab deaktiv olanda «məni xatırla» kukisi də etibarsız olur;
        // açıq sessiyaları AuthenticateSession / EnsureUserIsActive bağlayır.
        if (isset($payload['password']) || (! $isActive && (bool) $this->userModel->is_active)) {
            $payload['remember_token'] = Str::random(60);
        }

        $before = $this->userModel->only(['name', 'email', 'is_active']);
        $beforeRoles = $this->userModel->roles->pluck('name')->all();

        $this->userModel->forceFill($payload)->save();
        if ($this->roleId && ! $this->isSelf()) {
            $this->userModel->roles()->sync($this->roleId);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        activity('users')
            ->performedOn($this->userModel)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'before' => $before + ['roles' => $beforeRoles],
                'after' => $this->userModel->only(['name', 'email', 'is_active']) + ['roles' => $this->userModel->roles()->pluck('name')->all()],
                'password_changed' => isset($payload['password']),
            ])
            ->log('user.updated');

        $this->user['password'] = null;
        $this->user['confirm-password'] = null;
        $this->user['old_password'] = null;
        $this->user['actor_password'] = null;

        $this->dispatch('userAdded', __('services::users.messages.updated'));
    }

    public function isSelf(): bool
    {
        return (int) $this->userModel->id === (int) auth()->id();
    }

    protected function changesCredentials(): bool
    {
        return ! empty($this->user['password'])
            || mb_strtolower(trim((string) ($this->user['email'] ?? ''))) !== mb_strtolower(trim((string) $this->userModel->email));
    }

    /**
     * Hədəfdə idarəçidə olmayan icazə varsa — redaktə qadağandır.
     */
    protected function authorizeTarget(): void
    {
        abort_unless(
            app(UserAdministrationGuard::class)->canManageUser(auth()->user(), $this->userModel),
            403,
            __('services::users.messages.target_has_more_permissions')
        );
    }

    /**
     * Öz rolunu / statusunu / e-poçtunu dəyişmək və öz icazələrindən geniş rol təyin etmək olmaz.
     */
    protected function guardPrivilegeChanges(): void
    {
        $guard = app(UserAdministrationGuard::class);
        $currentRoleId = $this->userModel->roles->first()?->id;

        if ($this->isSelf()) {
            $errors = [];

            if ((int) $this->roleId !== (int) $currentRoleId) {
                $errors['roleId'] = __('services::users.messages.own_role_locked');
            }

            if (! (bool) ($this->user['is_active'] ?? false)) {
                $errors['user.is_active'] = __('services::users.messages.own_status_locked');
            }

            if (mb_strtolower($this->user['email']) !== mb_strtolower(trim((string) $this->userModel->email))) {
                $errors['user.email'] = __('services::users.messages.own_email_locked');
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return;
        }

        $role = Role::query()->find($this->roleId);
        if ($role && (int) $role->id !== (int) $currentRoleId && ! $guard->canAssignRole(auth()->user(), $role)) {
            throw ValidationException::withMessages(['roleId' => __('services::users.messages.role_exceeds_your_permissions')]);
        }
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
