<?php

namespace App\Modules\SidebarStructure\Livewire;

use App\Models\Structure;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public $selectedStructure;

    public function mount(): void
    {
        $selectedFromUrl = request()->query('structure');

        if (is_array($selectedFromUrl) && ! empty($selectedFromUrl)) {
            $first = reset($selectedFromUrl);
            if (is_numeric($first)) {
                $this->selectedStructure = (int) $first;
            }
        }
    }

    #[On('filterSelected')]
    public function filterSelected()
    {
        $this->selectedStructure = null;
    }

    public function selectStructure($id): void
    {
        $this->selectedStructure = $id;
        $this->dispatch('selectStructure', (int) $id);
    }

    public function render()
    {
      $structures = Cache::rememberForever('structures', function () {
            return Structure::withRecursive('subs')->whereNull('parent_id')->orderBy('code')->get();
        });

        return view('structure::livewire.structure.sidebar', compact('structures'));
    }

    public function placeholder()
    {
        return view('structure::livewire.structure.placeholders.sidebar');
    }
}
