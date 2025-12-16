<?php

namespace App\Modules\Candidates\Livewire;

use App\Modules\Candidates\Support\Traits\CandidateCrud;
use App\Models\Candidate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddCandidate extends Component
{
    use CandidateCrud;
    use AuthorizesRequests;

    public function store(): void
    {
        $this->validate();

        $modelInstance = new Candidate;

        Candidate::create($this->modifyArray($this->candidate, $modelInstance->dateList()));

        $this->dispatch('candidateAdded', __('Candidate was added successfully!'));
    }
}
