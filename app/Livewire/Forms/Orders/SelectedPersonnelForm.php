<?php

namespace App\Livewire\Forms\Orders;

use Livewire\Form;

class SelectedPersonnelForm extends Form
{
    /**
     * Row => [ selected personnels ]
     */
    public array $rows = [];

    /**
     * Flat list of tabel numbers used to prevent duplicates.
     */
    public array $personnels = [];

    public function resetState(): void
    {
        $this->rows = [];
        $this->personnels = [];
    }

    public function hasRow(int $row): bool
    {
        return array_key_exists($row, $this->rows);
    }

    public function add(int $row, array $payload): void
    {
        if (! array_key_exists($row, $this->rows)) {
            $this->rows[$row] = [];
        }

        $this->rows[$row][] = $payload;
        $this->personnels[] = $payload['key'] ?? null;
        $this->personnels = array_values(array_filter($this->personnels));
    }

    public function remove(int $row, int $index): void
    {
        $tabel = $this->rows[$row][$index]['key'] ?? null;

        unset($this->rows[$row][$index]);

        if (empty($this->rows[$row])) {
            unset($this->rows[$row]);
        }

        if ($tabel !== null) {
            $position = array_search($tabel, $this->personnels, true);
            if ($position !== false) {
                unset($this->personnels[$position]);
                $this->personnels = array_values($this->personnels);
            }
        }
    }

    public function flattenedRows(): array
    {
        if (empty($this->rows)) {
            return [];
        }

        return array_merge(...array_values($this->rows));
    }
}
