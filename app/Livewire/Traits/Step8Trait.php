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
    public $languageId,$languageName;
    public $degreeId,$degreeName;
    public $eduDocId,$eduDocName;

    public function mountStep8Trait() { 
        $this->languageName  = $this->degreeName = $this->eduDocName = '---';
        if(!empty($this->personnelModel))
        {
            $this->fillLanguage();
            $this->fillEvents();
            $this->fillDegree();
        }
    }

    public function addLanguage()
    {
        $validator1 = $this->exceptArray('event');
        $validator2 = $this->exceptArray('degree');
        $this->validate(array_intersect_assoc($validator1,$validator2));
        $this->language_list[] = $this->language;
        $this->languageName = $this->knowledgeName = '---';
        $this->reset(['languageId']);
        $this->language = [];;
    }

    public function forceDeleteLanguage($key)
    {
        unset($this->language_list[$key]);
    }

    public function addEvent()
    {
        $validator1 = $this->exceptArray('language');
        $validator2 = $this->exceptArray('degree');
        $this->validate(array_intersect_assoc($validator1,$validator2));
        $this->event_list[] = $this->event;
        $this->event = [];;
    }

    public function forceDeleteEvent($key)
    {
        unset($this->event_list[$key]);
    }

    public function addDegree()
    {
        $validator1 = $this->exceptArray('event');
        $validator2 = $this->exceptArray('language');
        $this->validate(array_intersect_assoc($validator1,$validator2));
        $this->degree_list[] = $this->degree;
        $this->degreeName = $this->eduDocName = '---';
        $this->reset(['eduDocId','eduDocId']);
        $this->degree = []; 
    }

    public function forceDeleteDegree($key)
    {
        unset($this->degree_list[$key]);
    }

    protected function fillLanguage()
    {
        $updateLanguage = $this->personnelModelData->foreignLanguages->load('language')->toArray();

        foreach($updateLanguage  as $key => $uptLanguage)
        {
            $this->language_list[] = [
                'knowledge_status' => $uptLanguage['knowledge_status']
            ];

            if(!empty($uptLanguage['language_id']))
            {
                $this->language_list[$key]['language_id'] = [
                    'id' => $uptLanguage['language']['id'],
                    'name' => $uptLanguage['language']['name'],
                ];
            }
        }
    }

    protected function fillEvents()
    {
        $updateEvents = $this->personnelModelData->participations->toArray();

        foreach($updateEvents  as $key => $uptEvents)
        {
            $this->event_list[] = [
                'event_type' => $uptEvents['event_type'],
                'event_name' => $uptEvents['event_name'],
                'event_date' => $uptEvents['event_date'],
            ];
        }
    }

    protected function fillDegree()
    {
        $updateDegree = $this->personnelModelData->degreeAndNames->load(['degreeAndName','documentType'])->toArray();

        foreach($updateDegree  as $key => $uptDegree)
        {
            $this->degree_list[] = [
                'science' => $uptDegree['science'],
                'given_date' => $uptDegree['given_date'],
                'subject' => $uptDegree['subject'],
                'diplom_serie' => $uptDegree['diplom_serie'],
                'diplom_no' => $uptDegree['diplom_no'],
                'diplom_given_date' => $uptDegree['diplom_given_date'],
                'document_issued_by' => $uptDegree['document_issued_by'],
            ];

            if(!empty($uptDegree['degree_and_name_id']))
            {
                $this->degree_list[$key]['degree_and_name_id'] = [
                    'id' => $uptDegree['degree_and_name']['id'],
                    'name' => $uptDegree['degree_and_name']['name'],
                ];
            }

            if(!empty($uptDegree['edu_doc_type_id']))
            {
                $this->degree_list[$key]['edu_doc_type_id'] = [
                    'id' => $uptDegree['document_type']['id'],
                    'name' => $uptDegree['document_type']['name'],
                ];
            }
        }
    }
}