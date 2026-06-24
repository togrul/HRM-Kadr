<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\Personnel;
use Livewire\Component;

/**
 * Reusable employee search/select as its OWN Livewire component. Because its re-render
 * is isolated from the parent, the input keeps focus even inside a teleported side-modal
 * (typing only re-renders this child, never the parent → the @teleport is not re-run).
 * It reports the chosen employee up via events keyed by a `target` string.
 */
class PersonnelPicker extends Component
{
    public string $target = '';

    public ?int $selectedId = null;

    public ?string $selectedLabel = null;

    public string $placeholder = '';

    public string $query = '';

    public function mount(string $target, ?int $selectedId = null, ?string $selectedLabel = null, string $placeholder = ''): void
    {
        $this->target = $target;
        $this->selectedId = $selectedId;
        $this->selectedLabel = $selectedLabel;
        $this->placeholder = $placeholder;
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getResultsProperty(): array
    {
        $term = trim($this->query);
        if (mb_strlen($term) < 2) {
            return [];
        }

        return Personnel::query()
            ->where('is_pending', false)
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('patronymic', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['id', 'surname', 'name', 'patronymic'])
            ->map(fn (Personnel $p): array => [
                'id' => $p->id,
                'label' => trim("{$p->surname} {$p->name} {$p->patronymic}"),
            ])
            ->all();
    }

    public function select(int $id, string $label): void
    {
        $this->selectedId = $id;
        $this->selectedLabel = $label;
        $this->query = '';
        $this->dispatch('personnel-picked', target: $this->target, id: $id, label: $label);
    }

    public function clear($id = null): void
    {
        $this->selectedId = null;
        $this->selectedLabel = null;
        $this->query = '';
        $this->dispatch('personnel-cleared', target: $this->target);
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.personnel-picker');
    }
}
