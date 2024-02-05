<?php

namespace App\Livewire\Candidates;

use App\Models\Candidate;
use Livewire\Component;

class DeleteCandidate extends Component
{
    public ?Candidate $candidate;

    protected $listeners = ['setDeleteCandidate'];

    public function setDeleteCandidate($candidateId)
    {
        $this->candidate = Candidate::where('id',$candidateId)->first();

        $this->dispatch('deleteCandidateWasSet');
    }

    public function deleteCandidate()
    {
        // $this->authorize('delete',$this->candidate);

        Candidate::destroy($this->candidate->id);

        $this->candidate = null;

        $this->dispatch('candidateWasDeleted' , __('Candidate was deleted!'));
    }

    public function render()
    {
        return view('livewire.candidates.delete-candidate');
    }
}
