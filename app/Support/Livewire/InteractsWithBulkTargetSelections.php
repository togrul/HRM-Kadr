<?php

namespace App\Support\Livewire;

trait InteractsWithBulkTargetSelections
{
    public function togglePersonnel(int $personnelId): void
    {
        $this->toggleSelectedId('selectedPersonnelIds', $personnelId);
    }

    public function toggleStructure(int $structureId): void
    {
        $this->toggleSelectedId('selectedStructureIds', $structureId);
    }

    public function togglePosition(int $positionId): void
    {
        $this->toggleSelectedId('selectedPositionIds', $positionId);
    }

    public function clearSelection(): void
    {
        $this->selectedPersonnelIds = [];
        $this->selectedStructureIds = [];
        $this->selectedPositionIds = [];
    }

    protected function incrementVersion(string $version): string
    {
        if (preg_match('/^\d+(?:\.\d+)?$/', $version) !== 1) {
            return $version.'-v2';
        }

        $parts = array_map('intval', explode('.', $version));
        if (count($parts) === 1) {
            return $parts[0].'.1';
        }

        $parts[count($parts) - 1]++;

        return implode('.', $parts);
    }

    protected function toggleSelectedId(string $property, int $id): void
    {
        $selected = $this->{$property} ?? [];

        if (in_array($id, $selected, true)) {
            $this->{$property} = array_values(array_filter(
                $selected,
                fn (int $selectedId): bool => $selectedId !== $id
            ));

            return;
        }

        $selected[] = $id;
        $this->{$property} = array_values(array_unique(array_map('intval', $selected)));
    }
}
