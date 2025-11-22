<?php

namespace App\Modules\Services\Livewire\Users;
use App\Models\User;
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
    public function setDeleteUser($userId)
    {
        $user = User::query()
            ->select('id')
            ->find($userId);

        if (! $user) {
            $this->userId = null;

            return;
        }

        // $this->authorize('delete', $user);

        $this->userId = (int) $user->id;

        $this->dispatch('deleteUserWasSet');
    }

    public function deleteUser()
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

        // $this->authorize('delete', $user);

        $user->delete();

        $this->userId = null;

        $this->dispatch('userWasDeleted', __('User was deleted!'));
    }

    public function render()
    {
        return view('services::livewire.services.users.delete-user');
    }
}
