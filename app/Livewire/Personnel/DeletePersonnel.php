<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class DeletePersonnel extends Component
{
    use AuthorizesRequests;

    #[\Livewire\Attributes\Locked]
    public ?int $personnelId = null;

    #[On('setDeletePersonnel')]
    public function setDeletePersonnel($personnelId): void
    {
        $personnel = Personnel::query()
            ->select('id', 'tabel_no')
            ->where('tabel_no', $personnelId)
            ->first();

        if (! $personnel) {
            $this->personnelId = null;

            return;
        }

        $this->authorize('delete-personnels', $personnel);
        $this->personnelId = (int) $personnel->id;

        $this->dispatch('deletePersonnelWasSet');
    }

    public function deletePersonnel(): void
    {
        if (! $this->personnelId) {
            return;
        }

        $personnel = Personnel::query()
            ->select('id', 'tabel_no', 'name', 'surname' , 'patronymic')
            ->find($this->personnelId);

        if (! $personnel) {
            $this->personnelId = null;

            return;
        }

        $this->authorize('delete-personnels', $personnel);

        $personnel->delete();

        $this->personnelId = null;

        $this->dispatch('personnelWasDeleted', __('Personnel was deleted!'));
    }

    public function render()
    {
        return view('livewire.personnel.delete-personnel');
    }
}
