<?php

namespace App\Livewire\Traits;

trait Step5Trait
{
    public $militaryRankId;

    public $militaryRankName;

    public $searchMilitaryRank;

    public $military = [];

    public $military_list = [];

    public $injuries = [];

    public $injury_list = [];

    public $captivity = [];

    public $captivity_list = [];

    public $personnel_extra = [];

    public function mountStep5Trait()
    {
        $this->militaryRankName = '---';
        if (! empty($this->personnelModel)) {
            $this->fillMilitary();
            $this->fillInjury();
            $this->fillCaptivity();
        }
    }

    public function addMilitary()
    {
        $validator1 = $this->exceptArray('injuries');
        $validator2 = $this->exceptArray('captivity');
        $this->validate(array_intersect_assoc($validator1, $validator2));

        $this->military_list[] = $this->military;
        $this->militaryRankName = '---';
        $this->reset('militaryRankId');
        $this->military = [];
    }

    public function forceDeleteMilitary($key)
    {
        unset($this->military_list[$key]);
    }

    protected function fillMilitary()
    {
        $updateMilitary = $this->personnelModelData->military->load('rank')->toArray();

        if (! empty($updateMilitary)) {
            foreach ($updateMilitary as $key => $uptMilitary) {
                $this->military_list[] = $this->mapAttributes(
                    attributes: [
                        'attitude_to_military_service', 'given_date', 'start_date', 'end_date',
                    ],
                    getFrom: $uptMilitary
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'rank',
                    field: 'rank_id',
                    key: $key,
                    fillTo: 'military_list',
                    getFrom: $uptMilitary,
                    titleField: 'name',
                    hasLocale: true
                );
            }
        }
    }

    protected function fillInjury()
    {
        $updateInjury = $this->personnelModelData->injuries->toArray();

        if (! empty($updateInjury)) {
            foreach ($updateInjury as $key => $uptInjury) {
                $this->injury_list[] = $this->mapAttributes(
                    attributes: [
                        'injury_type', 'location', 'date_time', 'description',
                    ],
                    getFrom: $uptInjury
                );
            }
        }
    }

    public function addInjury()
    {
        $validator1 = $this->exceptArray('military');
        $validator2 = $this->exceptArray('captivity');
        $this->validate(array_intersect_assoc($validator1, $validator2));

        $this->injury_list[] = $this->injuries;
        $this->injuries = [];
    }

    public function forceDeleteInjury($key)
    {
        unset($this->injury_list[$key]);
    }

    protected function fillCaptivity()
    {
        $updateCaptivity = $this->personnelModelData->captives->toArray();

        if (! empty($updateCaptivity)) {
            foreach ($updateCaptivity as $key => $uptCaptivity) {
                $this->captivity_list[] = $this->mapAttributes(
                    attributes: [
                        'location', 'condition', 'taken_captive_date', 'release_date',
                    ],
                    getFrom: $uptCaptivity
                );
            }
        }
    }

    public function addCaptivity()
    {
        $validator1 = $this->exceptArray('military');
        $validator2 = $this->exceptArray('injuries');
        $this->validate(array_intersect_assoc($validator1, $validator2));

        $this->captivity_list[] = $this->captivity;
        $this->captivity = [];
    }

    public function forceDeleteCaptivity($key)
    {
        unset($this->captivity_list[$key]);
    }
}
