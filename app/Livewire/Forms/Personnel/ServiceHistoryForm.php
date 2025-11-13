<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class ServiceHistoryForm extends Form
{
    public array $military = [];

    public array $militaryList = [];

    public array $injury = [];

    public array $injuryList = [];

    public array $captivity = [];

    public array $captivityList = [];

    public function resetForm(): void
    {
        $this->military = $this->defaultMilitary();
        $this->militaryList = [];
        $this->injury = $this->defaultInjury();
        $this->injuryList = [];
        $this->captivity = $this->defaultCaptivity();
        $this->captivityList = [];
    }

    public function resetMilitary(): void
    {
        $this->military = $this->defaultMilitary();
    }

    public function resetInjury(): void
    {
        $this->injury = $this->defaultInjury();
    }

    public function resetCaptivity(): void
    {
        $this->captivity = $this->defaultCaptivity();
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing(['military.rank', 'injuries', 'captives']);

        $this->militaryList = $personnel->military
            ->map(function ($service) {
                $payload = array_replace(
                    $this->defaultMilitary(),
                    Arr::only($service->toArray(), array_keys($this->defaultMilitary()))
                );

                $payload['rank_id'] = $service->rank_id;

                return $payload;
            })
            ->values()
            ->all();

        $this->injuryList = $personnel->injuries
            ->map(fn ($injury) => array_replace(
                $this->defaultInjury(),
                Arr::only($injury->toArray(), array_keys($this->defaultInjury()))
            ))
            ->values()
            ->all();

        $this->captivityList = $personnel->captives
            ->map(fn ($entry) => array_replace(
                $this->defaultCaptivity(),
                Arr::only($entry->toArray(), array_keys($this->defaultCaptivity()))
            ))
            ->values()
            ->all();
    }

    public function addMilitaryEntry(): void
    {
        $this->militaryList[] = $this->military;
        $this->resetMilitary();
    }

    public function removeMilitaryEntry(int $index): void
    {
        if (! array_key_exists($index, $this->militaryList)) {
            return;
        }

        unset($this->militaryList[$index]);
        $this->militaryList = array_values($this->militaryList);
    }

    public function addInjuryEntry(): void
    {
        $this->injuryList[] = $this->injury;
        $this->resetInjury();
    }

    public function removeInjuryEntry(int $index): void
    {
        if (! array_key_exists($index, $this->injuryList)) {
            return;
        }

        unset($this->injuryList[$index]);
        $this->injuryList = array_values($this->injuryList);
    }

    public function addCaptivityEntry(): void
    {
        $this->captivityList[] = $this->captivity;
        $this->resetCaptivity();
    }

    public function removeCaptivityEntry(int $index): void
    {
        if (! array_key_exists($index, $this->captivityList)) {
            return;
        }

        unset($this->captivityList[$index]);
        $this->captivityList = array_values($this->captivityList);
    }

    protected function defaultMilitary(): array
    {
        return [
            'rank_id' => null,
            'attitude_to_military_service' => null,
            'given_date' => null,
            'start_date' => null,
            'end_date' => null,
        ];
    }

    protected function defaultInjury(): array
    {
        return [
            'injury_type' => null,
            'location' => null,
            'date_time' => null,
            'description' => null,
        ];
    }

    protected function defaultCaptivity(): array
    {
        return [
            'location' => null,
            'condition' => null,
            'taken_captive_date' => null,
            'release_date' => null,
        ];
    }

    public function militaryForPersistence(): array
    {
        return $this->militaryList ?? [];
    }

    public function injuriesForPersistence(): array
    {
        return $this->injuryList ?? [];
    }

    public function captivitiesForPersistence(): array
    {
        return $this->captivityList ?? [];
    }
}
