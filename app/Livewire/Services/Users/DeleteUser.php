<?php

namespace App\Livewire\Services\Users;

use App\Models\User;
use Livewire\Component;

class DeleteUser extends Component
{
    public ?User $user;

    protected $listeners = ['setDeleteUser'];

    public function setDeleteUser($userId)
    {
        $this->user = User::findOrFail($userId);

        $this->dispatch('deleteUserWasSet');
    }

    public function deleteUser()
    {
        // $this->authorize('delete',$this->comment);

        User::destroy($this->user->id);

        $this->user = null;

        $this->dispatch('userWasDeleted' , __('User was deleted!'));
    }

    public function render()
    {
        return view('livewire.services.users.delete-user');
    }
}
