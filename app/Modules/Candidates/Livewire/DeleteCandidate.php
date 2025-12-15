<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteCandidate extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $candidateId = null;

    #[On('setDeleteCandidate')]
    public function setDeleteCandidate($candidateId)
    {
        $candidate = Candidate::query()
            ->select('id')
            ->find($candidateId);

        if (! $candidate) {
            $this->candidateId = null;

            return;
        }

        $this->authorize('delete', $candidate);

        $this->candidateId = (int) $candidate->id;

        $this->dispatch('deleteCandidateWasSet');
    }

    public function deleteCandidate()
    {
        if (! $this->candidateId) {
            return;
        }

        $candidate = Candidate::query()
            ->select('id')
            ->find($this->candidateId);

        if (! $candidate) {
            $this->candidateId = null;

            return;
        }

        $this->authorize('delete', $candidate);

        $candidate->delete();

        $this->candidateId = null;

        $this->dispatch('candidateWasDeleted', __('Candidate was deleted!'));
    }

    public function render()
    {
        return view('candidates::livewire.candidates.delete-candidate');
    }
}
