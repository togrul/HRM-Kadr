<?php

namespace App\Modules\SidebarStructure\Livewire;

use App\Models\Structure;
use App\Services\StructureService;
use App\Support\OrderLookupCache;
use Illuminate\Support\Collection;
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

    /**
     * The highlight is drawn by Alpine from $wire.selectedStructure, so clearing it needs
     * the new snapshot only, not a re-render of the whole tree.
     */
    #[On('filterSelected')]
    public function filterSelected(): void
    {
        $this->selectedStructure = null;
        $this->skipRender();
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

        return view('structure::livewire.structure.sidebar', [
            'structures' => $structures,
            'openIds' => (object) array_fill_keys($this->initiallyOpenIds($structures), true),
        ]);
    }

    /**
     * Roots start open (their direct children show), every deeper level folded, except
     * the ancestors of the selected unit so the highlight is never hidden in a fold.
     *
     * @param  Collection<int, Structure>  $roots
     * @return list<int>
     */
    private function initiallyOpenIds(Collection $roots): array
    {
        $parents = [];
        $walk = function (Collection $nodes) use (&$walk, &$parents): void {
            foreach ($nodes as $node) {
                $parents[$node->id] = $node->parent_id;
                $walk($node->subs);
            }
        };
        $walk($roots);

        $open = $roots->pluck('id')->all();
        $id = $parents[$this->selectedStructure] ?? null;

        while ($id !== null && ! in_array($id, $open, true)) {
            $open[] = $id;
            $id = $parents[$id] ?? null;
        }

        return $open;
    }

    public function placeholder()
    {
        return view('structure::livewire.structure.placeholders.sidebar');
    }
}
