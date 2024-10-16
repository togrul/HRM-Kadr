<?php

namespace App\Livewire\Candidates;

use App\Livewire\Traits\CandidateCrud;
use App\Models\Candidate;
use Livewire\Component;

class AddCandidate extends Component
{
    use CandidateCrud;

    public function store(): void
    {
        $this->validate();

        $modelInstance = new Candidate;

        Candidate::create($this->modifyArray($this->candidate, $modelInstance->dateList()));

        $this->dispatch('candidateAdded', __('Candidate was added successfully!'));
    }
}
