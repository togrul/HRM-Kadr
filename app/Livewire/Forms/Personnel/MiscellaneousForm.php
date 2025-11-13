<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class MiscellaneousForm extends Form
{
    public array $language = [];

    public array $languageList = [];

    public array $event = [];

    public array $eventList = [];

    public array $degree = [];

    public array $degreeList = [];

    public array $election = [];

    public array $electionList = [];

    public bool $hasElectedElectorals = false;

    public function resetForm(): void
    {
        $this->language = $this->defaultLanguage();
        $this->languageList = [];
        $this->event = $this->defaultEvent();
        $this->eventList = [];
        $this->degree = $this->defaultDegree();
        $this->degreeList = [];
        $this->election = $this->defaultElection();
        $this->electionList = [];
        $this->hasElectedElectorals = false;
    }

    public function resetLanguage(): void
    {
        $this->language = $this->defaultLanguage();
    }

    public function resetEvent(): void
    {
        $this->event = $this->defaultEvent();
    }

    public function resetDegree(): void
    {
        $this->degree = $this->defaultDegree();
    }

    public function resetElection(): void
    {
        $this->election = $this->defaultElection();
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing([
            'foreignLanguages.language',
            'participations',
            'degreeAndNames.degreeAndName',
            'degreeAndNames.documentType',
            'elections',
        ]);

        $this->languageList = $personnel->foreignLanguages
            ->map(function ($entry) {
                $payload = array_replace(
                    $this->defaultLanguage(),
                    Arr::only($entry->toArray(), ['knowledge_status'])
                );

                $payload['language_id'] = $entry->language_id;
                $payload['language_label'] = optional($entry->language)->name;

                return $payload;
            })
            ->values()
            ->all();

        $this->eventList = $personnel->participations
            ->map(fn ($entry) => array_replace(
                $this->defaultEvent(),
                Arr::only($entry->toArray(), ['event_type', 'event_name', 'event_date'])
            ))
            ->values()
            ->all();

        $this->degreeList = $personnel->degreeAndNames
            ->map(function ($entry) {
                $payload = array_replace(
                    $this->defaultDegree(),
                    Arr::only($entry->toArray(), [
                        'science',
                        'given_date',
                        'subject',
                        'diplom_serie',
                        'diplom_no',
                        'diplom_given_date',
                        'document_issued_by',
                    ])
                );

                $payload['degree_and_name_id'] = $entry->degree_and_name_id;
                $payload['degree_label'] = optional($entry->degreeAndName)->name;
                $payload['edu_doc_type_id'] = $entry->edu_doc_type_id;
                $payload['edu_doc_label'] = optional($entry->documentType)->name;

                return $payload;
            })
            ->values()
            ->all();

        $this->hasElectedElectorals = $personnel->elections->isNotEmpty();

        $this->electionList = $personnel->elections
            ->map(fn ($entry) => array_replace(
                $this->defaultElection(),
                Arr::only($entry->toArray(), ['election_type', 'location', 'elected_date'])
            ))
            ->values()
            ->all();
    }

    public function languagesForPersistence(): array
    {
        return collect($this->languageList ?? [])
            ->map(fn ($entry) => Arr::except($entry ?? [], ['language_label']))
            ->all();
    }

    public function eventsForPersistence(): array
    {
        return $this->eventList ?? [];
    }

    public function degreesForPersistence(): array
    {
        return collect($this->degreeList ?? [])
            ->map(fn ($entry) => Arr::except($entry ?? [], ['degree_label', 'edu_doc_label']))
            ->all();
    }

    public function electionsForPersistence(): array
    {
        if (! $this->hasElectedElectorals) {
            return [];
        }

        return $this->electionList ?? [];
    }

    public function addLanguageEntry(?string $label = null): void
    {
        $entry = $this->language;
        $entry['language_label'] = $label;

        $this->languageList[] = $entry;
        $this->resetLanguage();
    }

    public function removeLanguageEntry(int $index): void
    {
        if (! array_key_exists($index, $this->languageList)) {
            return;
        }

        unset($this->languageList[$index]);
        $this->languageList = array_values($this->languageList);
    }

    public function addEventEntry(): void
    {
        $this->eventList[] = $this->event;
        $this->resetEvent();
    }

    public function removeEventEntry(int $index): void
    {
        if (! array_key_exists($index, $this->eventList)) {
            return;
        }

        unset($this->eventList[$index]);
        $this->eventList = array_values($this->eventList);
    }

    public function addDegreeEntry(?string $degreeLabel = null, ?string $documentLabel = null): void
    {
        $entry = $this->degree;
        $entry['degree_label'] = $degreeLabel;
        $entry['edu_doc_label'] = $documentLabel;

        $this->degreeList[] = $entry;
        $this->resetDegree();
    }

    public function removeDegreeEntry(int $index): void
    {
        if (! array_key_exists($index, $this->degreeList)) {
            return;
        }

        unset($this->degreeList[$index]);
        $this->degreeList = array_values($this->degreeList);
    }

    public function addElectionEntry(): void
    {
        if (! $this->hasElectedElectorals) {
            return;
        }

        $this->electionList[] = $this->election;
        $this->resetElection();
    }

    public function removeElectionEntry(int $index): void
    {
        if (! array_key_exists($index, $this->electionList)) {
            return;
        }

        unset($this->electionList[$index]);
        $this->electionList = array_values($this->electionList);
    }

    protected function defaultLanguage(): array
    {
        return [
            'language_id' => null,
            'language_label' => null,
            'knowledge_status' => null,
        ];
    }

    protected function defaultEvent(): array
    {
        return [
            'event_type' => null,
            'event_name' => null,
            'event_date' => null,
        ];
    }

    protected function defaultDegree(): array
    {
        return [
            'degree_and_name_id' => null,
            'degree_label' => null,
            'science' => null,
            'given_date' => null,
            'subject' => null,
            'edu_doc_type_id' => null,
            'edu_doc_label' => null,
            'diplom_serie' => null,
            'diplom_no' => null,
            'diplom_given_date' => null,
            'document_issued_by' => null,
        ];
    }

    protected function defaultElection(): array
    {
        return [
            'election_type' => null,
            'location' => null,
            'elected_date' => null,
        ];
    }
}
