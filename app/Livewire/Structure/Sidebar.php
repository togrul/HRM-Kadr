<?php

namespace App\Livewire\Structure;

use App\Models\Structure;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Sidebar extends Component
{
    #[Url]
    public $selectedStructure;

    #[On('filterSelected')]
    public function filterSelected()
    {
        $this->selectedStructure = null;
    }

    public function selectStructure($id): void
    {
        $this->selectedStructure = $id;
        $this->dispatch('selectStructure', $id);
    }

    public function render()
    {
        $structures = Cache::rememberForever('structures', function () {
            return Structure::withRecursive('subs')->whereNull('parent_id')->orderBy('code')->get();
        });

        return view('livewire.structure.sidebar', compact('structures'));
    }
}
