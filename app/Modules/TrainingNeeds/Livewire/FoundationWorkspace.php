<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingCatalogMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Livewire\WithPagination;

#[Isolate]
class FoundationWorkspace extends AbstractTrainingNeedsWorkspace
{
    use HandlesTrainingCatalogMutations;
    use WithPagination;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('training_needs', ['catalogs', 'matrix', 'profiles']);
    }

    public function render(): View
    {
        return view('training-needs::livewire.training-needs.foundation-workspace');
    }
}
