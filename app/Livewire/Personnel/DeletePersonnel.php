<?php

namespace App\Livewire\Personnel;

use App\Models\Personnel;
use Livewire\Attributes\On;
use Livewire\Component;

class DeletePersonnel extends Component
{
    public ?Personnel $personnel;

    #[On('setDeletePersonnel')]
    public function setDeletePersonnel($personnelId): void
    {
        $this->authorize('delete-personnels', $personnelId);
        $this->personnel = Personnel::where('tabel_no', $personnelId)->first();

        $this->dispatch('deletePersonnelWasSet');
    }

    public function deletePersonnel(): void
    {
        $this->authorize('delete-personnels', $this->personnel);

        Personnel::destroy($this->personnel->id);
        $this->personnel = null;

        $this->dispatch('personnelWasDeleted', __('Personnel was deleted!'));
    }

    public function render()
    {
        return view('livewire.personnel.delete-personnel');
    }
}
