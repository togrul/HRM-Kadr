<?php

namespace App\Support\Livewire;

use App\Models\Personnel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Tabel-no / name typeahead that picks one employee. Shared by the Compensation and
 * Payroll workspaces; the consuming view renders the input and the result list.
 * Axtarış və seçim istifadəçinin struktur görünürlüyü ilə məhdudlaşır; seçilmiş tabel
 * nömrəsi kilidlidir — yalnız selectPersonnel() (görünürlük yoxlaması ilə) onu dəyişir.
 */
trait SearchesPersonnel
{
    use ScopesPersonnelByStructure;

    public string $personnelSearch = '';

    #[Locked]
    public ?string $selectedTabelNo = null;

    #[Locked]
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

        return $this->personnelScope()->constrain(Personnel::query(), 'personnels.structure_id')
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
        abort_unless($this->tabelInScope($tabelNo), 403);

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
