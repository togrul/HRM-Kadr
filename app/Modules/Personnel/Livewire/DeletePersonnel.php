<?php

namespace App\Modules\Personnel\Livewire;

use App\Models\Personnel;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class DeletePersonnel extends Component
{
    use AuthorizesRequests;

    #[\Livewire\Attributes\Locked]
    public ?int $personnelId = null;

    #[On('setDeletePersonnel')]
    public function setDeletePersonnel(mixed $personnelId): void
    {
        $id = filter_var($personnelId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $personnel = $id === false
            ? null
            : Personnel::query()->select('id', 'tabel_no')->find($id);

        if (! $personnel) {
            $this->personnelId = null;

            return;
        }

        $this->authorize('delete', $personnel);
        $this->personnelId = (int) $personnel->id;

        $this->dispatch('deletePersonnelWasSet');
    }

    public function deletePersonnel(): void
    {
        if (! $this->personnelId) {
            return;
        }

        $personnel = Personnel::query()
            ->select('id', 'tabel_no', 'name', 'surname', 'patronymic')
            ->find($this->personnelId);

        if (! $personnel) {
            $this->personnelId = null;

            return;
        }

        $this->authorize('delete', $personnel);

        $personnel->delete();

        $this->personnelId = null;

        $this->dispatch('personnelWasDeleted', __('personnel::common.messages.personnel_deleted'));
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.delete-personnel');
    }
}
