<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Form;

class KinshipForm extends Form
{
    public array $kinship = [];

    public array $kinshipList = [];

    public ?string $editingKinshipKey = null;

    public function resetForm(): void
    {
        $this->editingKinshipKey = null;
        $this->kinship = $this->defaultKinship();
        $this->kinshipList = [];
    }

    public function resetKinship(): void
    {
        $this->kinship = $this->defaultKinship();
    }

    public function cancelKinshipEdit(): void
    {
        $this->editingKinshipKey = null;
        $this->resetKinship();
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing('kinships.kinship');

        $locale = app()->getLocale();

        $this->kinshipList = $personnel->kinships
            ->map(function ($kinship) use ($locale) {
                $payload = array_replace(
                    $this->defaultKinship(),
                    Arr::only($kinship->toArray(), [
                        'id',
                        'fullname',
                        'birthdate',
                        'birth_place',
                        'company_name',
                        'position',
                        'registered_address',
                        'residental_address',
                        'birth_certificate_number',
                        'marriage_certificate_number',
                    ])
                );

                $payload['kinship_id'] = $kinship->kinship_id;
                $column = "name_{$locale}";
                $payload['kinship_name'] = optional($kinship->kinship)->{$column};
                $payload['row_key'] = $this->rowKey((int) $kinship->id);

                return $payload;
            })
            ->values()
            ->all();
    }

    public function kinshipsForPersistence(): array
    {
        return collect($this->kinshipList ?? [])
            ->map(fn ($entry) => Arr::except($entry ?? [], ['kinship_name', 'row_key']))
            ->all();
    }

    public function saveKinshipEntry(?string $label = null): void
    {
        $entry = array_replace($this->defaultKinship(), $this->kinship);
        $entry['kinship_name'] = $label;
        $entry['row_key'] = $entry['row_key'] ?: Str::uuid()->toString();

        $index = $this->indexForRowKey($this->editingKinshipKey);

        if ($index !== null) {
            $this->kinshipList[$index] = $entry;
            $this->cancelKinshipEdit();

            return;
        }

        $this->kinshipList[] = $entry;
        $this->resetKinship();
    }

    public function beginKinshipEdit(string $rowKey): void
    {
        $index = $this->indexForRowKey($rowKey);

        if ($index === null) {
            return;
        }

        $this->editingKinshipKey = $rowKey;
        $this->kinship = array_replace($this->defaultKinship(), $this->kinshipList[$index] ?? []);
    }

    public function removeKinshipEntry(string $rowKey): void
    {
        $index = $this->indexForRowKey($rowKey);

        if ($index === null) {
            return;
        }

        unset($this->kinshipList[$index]);
        $this->kinshipList = array_values($this->kinshipList);

        if ($this->editingKinshipKey === $rowKey) {
            $this->cancelKinshipEdit();
        }
    }

    public function isEditingKinship(): bool
    {
        return $this->editingKinshipKey !== null;
    }

    protected function defaultKinship(): array
    {
        return [
            'id' => null,
            'row_key' => null,
            'kinship_id' => null,
            'kinship_name' => null,
            'fullname' => null,
            'birthdate' => null,
            'birth_place' => null,
            'company_name' => null,
            'position' => null,
            'registered_address' => null,
            'residental_address' => null,
            'birth_certificate_number' => null,
            'marriage_certificate_number' => null,
        ];
    }

    private function indexForRowKey(?string $rowKey): ?int
    {
        if (! $rowKey) {
            return null;
        }

        foreach ($this->kinshipList as $index => $entry) {
            if (($entry['row_key'] ?? null) === $rowKey) {
                return $index;
            }
        }

        return null;
    }

    private function rowKey(int $id): string
    {
        return "kinship-{$id}";
    }
}
