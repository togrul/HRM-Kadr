<?php

namespace App\Modules\Services\Livewire\Users;

use App\Livewire\Traits\SideModalAction;
use App\Models\User;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use App\Modules\Services\Livewire\Concerns\AuthorizesUserManagement;
use App\Services\UserAdministrationGuard;
use Illuminate\Contracts\View\View;
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
    use AuthorizesSettingsAccess;
    use AuthorizesUserManagement;

    #[Url]
    public $status;

    #[Url]
    public $q;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function setDeleteUser($userId): void
    {
        $this->dispatch('setDeleteUser', $userId);
    }

    public function resetFilter(): void
    {
        $this->reset('q');
        $this->resetPage();
        $this->fillFilter();
    }

    public function fillFilter(): void
    {
        $this->status = request()->query('status') ? (int) request()->query('status') : 1;
    }

    public function setStatus($newStatus): void
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function forceDeleteData($id): void
    {
        $model = User::withTrashed()->findOrFail($id);
        $this->authorizeTarget($model);
        $model->forceDelete();

        activity('users')
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->event('force_deleted')
            ->withProperties(['user_id' => $model->id, 'email' => $model->email])
            ->log('user.force_deleted');

        $this->dispatch('userWasDeleted', __('services::users.messages.deleted'));
    }

    #[On('restoreData')]
    public function restoreData($id): void
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorizeTarget($user);
        $user->restore();
        $user->update([
            'deleted_by' => null,
            'is_active' => true,
        ]);

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->event('restored')
            ->withProperties(['user_id' => $user->id, 'email' => $user->email])
            ->log('user.restored');

        $this->dispatch('userAdded', __('services::users.messages.restored'));
    }

    public function mount(): void
    {
        $this->authorize(UserAdministrationGuard::MANAGE_USERS);
        $this->fillFilter();
    }

    /**
     * Özünü, ya da özündə olmayan icazəyə malik istifadəçini silmək/bərpa etmək olmaz.
     */
    private function authorizeTarget(User $user): void
    {
        $this->authorize(UserAdministrationGuard::MANAGE_USERS);
        abort_if((int) $user->id === (int) auth()->id(), 403, __('services::users.messages.cannot_delete_self'));
        abort_unless(
            app(UserAdministrationGuard::class)->canManageUser(auth()->user(), $user),
            403,
            __('services::users.messages.target_has_more_permissions')
        );
    }

    public function render(): View
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
