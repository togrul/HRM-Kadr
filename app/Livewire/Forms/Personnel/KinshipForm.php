<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class KinshipForm extends Form
{
    public array $kinship = [];

    public array $kinshipList = [];

    public function resetForm(): void
    {
        $this->kinship = $this->defaultKinship();
        $this->kinshipList = [];
    }

    public function resetKinship(): void
    {
        $this->kinship = $this->defaultKinship();
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

                return $payload;
            })
            ->values()
            ->all();
    }

    public function kinshipsForPersistence(): array
    {
        return collect($this->kinshipList ?? [])
            ->map(fn ($entry) => Arr::except($entry ?? [], ['kinship_name']))
            ->all();
    }

    public function addKinshipEntry(?string $label = null): void
    {
        $entry = $this->kinship;
        $entry['kinship_name'] = $label;

        $this->kinshipList[] = $entry;
        $this->resetKinship();
    }

    public function removeKinshipEntry(int $index): void
    {
        if (! array_key_exists($index, $this->kinshipList)) {
            return;
        }

        unset($this->kinshipList[$index]);
        $this->kinshipList = array_values($this->kinshipList);
    }

    protected function defaultKinship(): array
    {
        return [
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
}
