<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Modules\Candidates\Support\Traits\CandidateCrud;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddCandidate extends Component
{
    use AuthorizesRequests;
    use CandidateCrud;

    public function placeholder(): View
    {
        return view('candidates::livewire.candidates.placeholders.add-candidate');
    }

    public function store(): void
    {
        $this->validate();

        $modelInstance = new Candidate;

        Candidate::create($this->modifyArray($this->candidate, $modelInstance->dateList()));

        $this->dispatch('candidateAdded', __('candidates::common.messages.candidate_added'));
    }
}
