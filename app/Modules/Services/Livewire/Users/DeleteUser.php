<?php

namespace App\Modules\Services\Livewire\Users;

use App\Models\User;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use App\Modules\Services\Livewire\Concerns\AuthorizesUserManagement;
use App\Services\UserAdministrationGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteUser extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;
    use AuthorizesUserManagement;

    #[Locked]
    public ?int $userId = null;

    #[On('setDeleteUser')]
    public function setDeleteUser($userId): void
    {
        $user = User::query()
            ->select('id')
            ->find($userId);

        if (! $user) {
            $this->userId = null;

            return;
        }

        $this->authorizeTarget($user);

        $this->userId = (int) $user->id;

        $this->dispatch('deleteUserWasSet');
    }

    public function deleteUser(): void
    {
        if (! $this->userId) {
            return;
        }

        $user = User::query()
            ->select('id')
            ->find($this->userId);

        if (! $user) {
            $this->userId = null;

            return;
        }

        $this->authorizeTarget($user);

        $user->delete();

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->withProperties(['user_id' => $user->id])
            ->log('user.deleted');

        $this->userId = null;

        $this->dispatch('userWasDeleted', __('services::users.messages.deleted'));
    }

    /**
     * Özünü, ya da özündə olmayan icazəyə malik istifadəçini silmək olmaz.
     */
    private function authorizeTarget(User $user): void
    {
        $this->authorize(UserAdministrationGuard::MANAGE_USERS);
        abort_if((int) $user->id === (int) auth()->id(), 403, __('services::users.messages.cannot_delete_self'));
        abort_unless(
            app(UserAdministrationGuard::class)->canManageUser(auth()->user(), User::query()->findOrFail($user->id)),
            403,
            __('services::users.messages.target_has_more_permissions')
        );
    }

    public function render(): View
    {
        return view('services::livewire.services.users.delete-user');
    }
}
