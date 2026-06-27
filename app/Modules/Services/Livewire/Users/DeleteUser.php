<?php

namespace App\Modules\Services\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteUser extends Component
{
    use AuthorizesRequests;

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

        $this->authorize('access-settings');

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

        $this->authorize('access-settings');

        $user->delete();

        activity('users')
            ->performedOn($user)
            ->event('deleted')
            ->withProperties(['user_id' => $user->id])
            ->log('user.deleted');

        $this->userId = null;

        $this->dispatch('userWasDeleted', __('services::users.messages.deleted'));
    }

    public function render(): View
    {
        return view('services::livewire.services.users.delete-user');
    }
}
