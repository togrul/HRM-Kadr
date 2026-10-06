<?php

namespace App\Support\Livewire;

use App\Models\Personnel;
use Livewire\Attributes\Computed;

/**
 * Tabel-no / name typeahead that picks one employee. Shared by the Compensation and
 * Payroll workspaces; the consuming view renders the input and the result list.
 */
trait SearchesPersonnel
{
    public string $personnelSearch = '';

    public ?string $selectedTabelNo = null;

    public ?string $selectedPersonnelLabel = null;

    /**
     * @return array<int, array{tabel_no: string, label: string}>
     */
    #[Computed]
    public function personnelResults(): array
    {
        $term = trim($this->personnelSearch);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Personnel::query()
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('tabel_no', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['tabel_no', 'surname', 'name'])
            ->map(fn (Personnel $p): array => [
                'tabel_no' => $p->tabel_no,
                'label' => trim("{$p->tabel_no} — {$p->surname} {$p->name}"),
            ])
            ->all();
    }

    public function selectPersonnel(string $tabelNo, string $label): void
    {
        $this->selectedTabelNo = $tabelNo;
        $this->selectedPersonnelLabel = $label;
        $this->personnelSearch = '';
    }

    public function clearPersonnel(): void
    {
        $this->selectedTabelNo = null;
        $this->selectedPersonnelLabel = null;
    }
}
