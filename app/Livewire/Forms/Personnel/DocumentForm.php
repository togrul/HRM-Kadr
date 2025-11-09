<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class DocumentForm extends Form
{
    public array $document = [];

    public array $serviceCards = [];

    public array $serviceCardsList = [];

    public array $passports = [];

    public array $passportsList = [];

    public function resetForm(): void
    {
        $this->document = $this->defaultDocument();
        $this->serviceCards = $this->defaultServiceCard();
        $this->serviceCardsList = [];
        $this->passports = $this->defaultPassport();
        $this->passportsList = [];
    }

    public function resetServiceCard(): void
    {
        $this->serviceCards = $this->defaultServiceCard();
    }

    public function resetPassport(): void
    {
        $this->passports = $this->defaultPassport();
    }

    public function mergeDocument(array $payload): void
    {
        $this->document = array_replace(
            $this->defaultDocument(),
            $this->document,
            $payload
        );
    }

    public function toPayload(): array
    {
        return [
            'document' => $this->document,
            'service_cards' => [
                'current' => $this->serviceCards,
                'list' => $this->serviceCardsList,
            ],
            'passports' => [
                'current' => $this->passports,
                'list' => $this->passportsList,
            ],
        ];
    }

    public function fillFromArrays(array $document, array $serviceCards, array $serviceCardList, array $passports, array $passportList): void
    {
        $this->document = $document ?: $this->defaultDocument();
        $this->serviceCards = $serviceCards ?: $this->defaultServiceCard();
        $this->serviceCardsList = $serviceCardList;
        $this->passports = $passports ?: $this->defaultPassport();
        $this->passportsList = $passportList;
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing([
            'idDocuments.nationality',
            'idDocuments.bornCountry',
            'idDocuments.bornCity',
            'cards',
            'passports',
        ]);

        $document = $personnel->idDocuments;

        if ($document) {
            $documentArray = $document->toArray();

            $this->document = array_replace(
                $this->defaultDocument(),
                Arr::only($documentArray, array_keys($this->defaultDocument()))
            );
        }

        $this->serviceCardsList = $personnel->cards
            ->map(fn ($card) => Arr::only($card->toArray(), array_keys($this->defaultServiceCard())))
            ->values()
            ->all();

        $this->passportsList = $personnel->passports
            ->map(fn ($passport) => Arr::only($passport->toArray(), array_keys($this->defaultPassport())))
            ->values()
            ->all();
    }

    protected function defaultDocument(): array
    {
        return [
            'pin' => null,
            'series' => null,
            'number' => null,
            'nationality_id' => null,
            'born_country_id' => null,
            'born_city_id' => null,
            'birthplace' => null,
            'registered_address' => null,
            'is_married' => null,
            'military_duty' => null,
            'blood_group' => null,
            'eye_color' => null,
            'height' => null,
            'document_issued_authority' => null,
            'document_issued_date' => null,
        ];
    }

    protected function defaultServiceCard(): array
    {
        return [
            'card_number' => null,
            'given_date' => null,
            'valid_date' => null,
        ];
    }

    protected function defaultPassport(): array
    {
        return [
            'serial_number' => null,
            'given_date' => null,
            'valid_date' => null,
        ];
    }
}
