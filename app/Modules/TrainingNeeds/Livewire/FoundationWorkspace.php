<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingCatalogMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Isolate;

#[Isolate]
class FoundationWorkspace extends AbstractTrainingNeedsWorkspace
{
    use HandlesTrainingCatalogMutations;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('training_needs', ['catalogs', 'matrix', 'profiles']);
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.foundation-workspace');
    }
}
