<?php

namespace App\Livewire\Structure;

use Livewire\Component;
use App\Models\Structure;
use Livewire\Attributes\Url;

class Sidebar extends Component
{
    #[Url]
    public $selectedStructure;

    protected $listeners = ['filterSelected'];

    public function filterSelected()
    {
        $this->selectedStructure = null;
    }

    public function selectStructure($id)
    {
        $this->selectedStructure = $id;
        $this->dispatch('selectStructure',$id);
    }

    public function render()
    {
        $structures = Structure::with(['parent','subs'])->whereNull('parent_id')->get();
        return view('livewire.structure.sidebar',compact('structures'));
    }
}
