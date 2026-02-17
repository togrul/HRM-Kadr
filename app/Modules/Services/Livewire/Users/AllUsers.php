<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\SideModalAction;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['userAdded', 'userWasDeleted'])]
class AllUsers extends Component
{
    use AuthorizesRequests,SideModalAction,WithPagination;

    #[Url]
    public $status;

    #[Url]
    public $q;

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function setDeleteUser($userId)
    {
        $this->dispatch('setDeleteUser', $userId);
    }

    public function resetFilter()
    {
        $this->reset('q');
        $this->resetPage();
        $this->fillFilter();
    }

    public function fillFilter()
    {
        $this->status = request()->query('status') ? (int) request()->query('status') : 1;
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
        $this->dispatch('userWasDeleted', __('User was deleted!'));
    }

    #[On('restoreData')]
    public function restoreData($id)
    {
        $user = User::withTrashed()->find($id);
        $user->restore();
        $user->update([
            'deleted_by' => null,
            'is_active' => true,
        ]);
        $this->dispatch('userAdded', __('User was updated successfully!'));
    }

    public function mount()
    {
        $this->fillFilter();
    }

    public function render()
    {
        $_users = User::with(['roles:id,name', 'personDidDelete:id,name'])
            ->when(! empty($this->q), function ($q) {
                return $q->where(function ($nested) {
                    $nested->where('name', 'LIKE', "%{$this->q}%")
                        ->orWhere('email', 'LIKE', "%{$this->q}%");
                });
            })
            ->when($this->status != 2, function ($q) {
                $q->where('is_active', $this->status);
            })
            ->when($this->status == 2, function ($q) {
                $q->onlyTrashed();
            })
            ->paginate(15)
            ->withQueryString();

        $_users = $this->decorateUsers($_users);

        return view('services::livewire.services.users.all-users', compact('_users'));
    }

    protected function decorateUsers(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (User $user, int $index) use ($start) {
                $user->row_no = $start + $index + 1;
                $user->primary_role = $user->roles->first()?->name;

                if ($user->deleted_at) {
                    $user->deleted_at_label = $user->deleted_at->format('d-m-Y H:i');
                    $user->deleted_by_name = $user->personDidDelete?->name ?? '';
                }

                return $user;
            })
        );

        return $paginated;
    }
}
