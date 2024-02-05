<?php

namespace App\Livewire\Candidates;

use App\Livewire\Traits\CandidateCrud;
use App\Models\Candidate;
use Livewire\Component;

class AddCandidate extends Component
{
    use CandidateCrud;

    public function store()
    {
        $this->validate();

        Candidate::create($this->modifyArray($this->candidate));

        $this->dispatch('candidateAdded',__('Candidate was added successfully!'));
    }
}
