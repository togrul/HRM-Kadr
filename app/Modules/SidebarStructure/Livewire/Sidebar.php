<?php

namespace App\Modules\SidebarStructure\Livewire;

use App\Models\Structure;
use App\Services\StructureService;
use App\Support\OrderLookupCache;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public ?int $selectedStructure = null;

    #[On('structureUpdated')]
    public function refreshStructureTree(): void
    {
        OrderLookupCache::bump('structures');
    }

    /**
     * The highlight must be a function of the page's filter, not of state this component
     * happens to be holding: the host page filters by the clicked unit PLUS every
     * descendant, and if this component is ever re-mounted mid-request (a parent
     * re-render, a teleported panel) there is no page query string to recover from, so a
     * purely local selection silently disappears. Pages that own a structure filter pass
     * the clicked id in; everything else still falls back to the URL.
     */
    public function mount(?int $selected = null): void
    {
        $this->selectedStructure = $selected ?? $this->selectedFromQueryString();
    }

    /**
     * The host writes the clicked unit first, then its descendants.
     */
    private function selectedFromQueryString(): ?int
    {
        $selectedFromUrl = request()->query('structure');

        if (! is_array($selectedFromUrl) || $selectedFromUrl === []) {
            return null;
        }

        $first = reset($selectedFromUrl);

        return is_numeric($first) ? (int) $first : null;
    }

    #[On('filterSelected')]
    public function filterSelected()
    {
        $this->selectedStructure = null;
    }

    public function selectStructure($id): void
    {
        $this->selectedStructure = (int) $id;
        $this->dispatch('selectStructure', (int) $id);
    }

    public function render()
    {
        // The tree is trimmed to what the user may see, so it is cached per accessible set
        // (never under one shared key) and versioned so a structure edit invalidates it.
        $accessible = resolve(StructureService::class)->getAccessibleStructures();
        $key = OrderLookupCache::key('structures', 'sidebar:'.md5(implode(',', $accessible)));

        $structures = Cache::rememberForever($key, function () {
            return Structure::withRecursive('subs')->whereNull('parent_id')->orderBy('code')->get();
        });

        return view('structure::livewire.structure.sidebar', compact('structures'));
    }

    public function placeholder()
    {
        return view('structure::livewire.structure.placeholders.sidebar');
    }
}
