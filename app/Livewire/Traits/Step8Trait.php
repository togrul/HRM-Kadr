<?php

namespace App\Livewire\Traits;

trait Step8Trait
{
    public $language = [];

    public $language_list = [];

    public $event = [];

    public $event_list = [];

    public $degree = [];

    public $degree_list = [];

    public $languageId;

    public $languageName;

    public $degreeId;

    public $degreeName;

    public $eduDocId;

    public $eduDocName;

    public $hasElectedElectorals;

    public $elections = [];

    public $election_list = [];

    public function mountStep8Trait()
    {
        $this->resetDefaultSelections();
        if (! empty($this->personnelModel)) {
            $this->initializePersonnelData();
        }
    }

    protected function resetDefaultSelections()
    {
        $this->hasElectedElectorals = false;
        $this->languageName = $this->degreeName = $this->eduDocName = '---';
    }

    protected function initializePersonnelData()
    {
        $this->fillLanguage();
        $this->fillEvents();
        $this->fillDegree();
        $this->fillElections();
        $this->hasElectedElectorals = count($this->personnelModelData->elections) > 0;
    }

    protected function resetLanguageSelection()
    {
        $this->languageName = $this->knowledgeName = '---';
        $this->reset(['languageId']);
        $this->language = [];
    }

    protected function deleteFromList(&$list, $key)
    {
        unset($list[$key]);
    }

    public function addLanguage()
    {
        $this->validateCommon(['event', 'degree', 'elections']);
        $this->language_list[] = $this->language;
        $this->resetLanguageSelection();
    }

    public function forceDeleteLanguage($key)
    {
        $this->deleteFromList($this->language_list, $key);
    }

    public function addEvent()
    {
        $this->validateCommon(['language', 'degree', 'elections']);
        $this->event_list[] = $this->event;
        $this->resetEventSelection();
    }

    protected function resetEventSelection()
    {
        $this->event = [];
    }

    public function forceDeleteEvent($key)
    {
        $this->deleteFromList($this->event_list, $key);
    }

    public function addDegree()
    {
        $this->validateCommon(['event', 'language', 'elections']);
        $this->degree_list[] = $this->degree;
        $this->resetDegreeSelection();
    }

    protected function resetDegreeSelection()
    {
        $this->degreeName = $this->eduDocName = '---';
        $this->reset(['eduDocId', 'degreeId']);
        $this->degree = [];
    }

    public function forceDeleteDegree($key)
    {
        $this->deleteFromList($this->degree_list, $key);
    }

    public function addElection()
    {
        $this->validateCommon(['language', 'event', 'degree']);
        $this->election_list[] = $this->elections;
        $this->resetElectionSelection();
    }

    protected function resetElectionSelection()
    {
        $this->elections = [];
    }

    public function forceDeleteElection($key)
    {
        $this->deleteFromList($this->election_list, $key);
    }

    protected function fillLanguage()
    {
        $updateLanguage = $this->personnelModelData->foreignLanguages->load('language')->toArray();

        foreach ($updateLanguage as $key => $uptLanguage) {
            $this->language_list[] = $this->mapAttributes(
                attributes: ['knowledge_status'],
                getFrom: $uptLanguage
            );

            $this->handleRelatedEntitiesMultiDimensional(
                entity: 'language',
                field: 'language_id',
                key: $key,
                fillTo: 'language_list',
                getFrom: $uptLanguage,
                titleField: 'name'
            );
        }
    }

    protected function fillEvents()
    {
        $updateEvents = $this->personnelModelData->participations->toArray();

        foreach ($updateEvents as $key => $uptEvents) {
            $this->event_list[] = $this->mapAttributes(
                attributes: ['event_type', 'event_name', 'event_date'],
                getFrom: $uptEvents
            );
        }
    }

    protected function fillDegree()
    {
        $updateDegree = $this->personnelModelData->degreeAndNames->load(['degreeAndName', 'documentType'])->toArray();

        foreach ($updateDegree as $key => $uptDegree) {
            $this->degree_list[] = $this->mapAttributes(
                attributes: [
                    'science', 'given_date', 'subject',
                    'diplom_serie', 'diplom_no', 'diplom_given_date', 'document_issued_by',
                ],
                getFrom: $uptDegree
            );

            $this->handleRelatedEntitiesMultiDimensional(
                entity: 'degree_and_name',
                field: 'degree_and_name_id',
                key: $key,
                fillTo: 'degree_list',
                getFrom: $uptDegree,
                titleField: 'name'
            );

            $this->handleRelatedEntitiesMultiDimensional(
                entity: 'document_type',
                field: 'edu_doc_type_id',
                key: $key,
                fillTo: 'degree_list',
                getFrom: $uptDegree,
                titleField: 'name'
            );
        }
    }

    protected function fillElections()
    {
        $updateElections = $this->personnelModelData->elections->toArray();

        foreach ($updateElections as $key => $uptElection) {
            $this->election_list[] = $this->mapAttributes(
                attributes: [
                    'election_type', 'location', 'elected_date',
                ],
                getFrom: $uptElection
            );
        }
    }
}
