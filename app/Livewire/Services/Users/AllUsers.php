<?php

namespace App\Livewire\Services\Users;

use App\Livewire\Traits\SideModalAction;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class AllUsers extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;

    #[Url]
    public $status;

    #[Url]
    public $q;

    protected $listeners = ['restoreData','userAdded' => '$refresh','userWasDeleted' => '$refresh'];

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function setDeleteUser($userId)
    {
        $this->dispatch('setDeleteUser',$userId);
    }

    public function resetFilter()
    {
        $this->reset('q');
        $this->resetPage();
        $this->fillFilter();
    }

    public function fillFilter()
    {
        $this->status = request()->query('status') ? (int)request()->query('status') : 1;
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function forceDeleteData($id)
    {
        $model = User::withTrashed()->find($id);
        $model->forceDelete();
        $this->dispatch('userWasDeleted' , __('User was deleted!'));
    }

    public function restoreData($id)
    {
        $user = User::withTrashed()->find($id);
        $user->restore();
        $user->update([
            'deleted_by' => null,
            'is_active' => true
        ]);
        $this->dispatch('userAdded',__('User was updated successfully!'));
    }

    public function mount()
    {
        $this->fillFilter();
    }

    public function render()
    {
        $_users = User::with('roles')
                ->when(!empty($this->q),function($q) {
                    return $q->where('name','LIKE',"%{$this->q}%")
                        ->orWhere('email','LIKE',"%{$this->q}%");
                })
                ->when($this->status != 2,function($q)
                {
                    $q->where('is_active',$this->status);
                })
                ->when($this->status == 2,function($q)
                {
                    $q->onlyTrashed();
                })
                ->paginate(15)
                ->withQueryString();

        return view('livewire.services.users.all-users',compact('_users'));
    }
}
