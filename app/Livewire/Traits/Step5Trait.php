<?php

namespace App\Livewire\Traits;

trait Step5Trait
{
    public $militaryRankId,$militaryRankName,$searchMilitaryRank;

    public $military = [];
    public $military_list = [];

    public $injuries = [];
    public $injury_list = [];

    public $captivity = [];
    public $captivity_list = [];

    public function mountStep5Trait() {
        $this->militaryRankName = '---';
        if(!empty($this->personnelModel))
        {
            $this->fillMilitary();
            $this->fillInjury();
        };
    }

    public function addMilitary()
    {
        $validator1 = $this->exceptArray('injuries');
        $validator2 = $this->exceptArray('captivity');
        $this->validate(array_intersect_assoc($validator1,$validator2));

        $this->military_list[] = $this->military;
        $this->militaryRankName = '---';
        $this->reset('militaryRankId');
        $this->military = [];;
    }

    public function forceDeleteMilitary($key)
    {
        unset($this->military_list[$key]);
    }

    protected function fillMilitary()
    {
        $updateMilitary = $this->personnelModelData->military->load('rank')->toArray();

        if(!empty($updateMilitary))
        {
            foreach($updateMilitary  as $key => $uptMilitary)
            {
                $this->military_list[] = [
                    'attitude_to_military_service' => $uptMilitary['attitude_to_military_service'],
                    'given_date' => $uptMilitary['given_date'],
                    'start_date' => $uptMilitary['start_date'],
                    'end_date' => $uptMilitary['end_date'],
                ];

                if(!empty($uptMilitary['rank_id']))
                {
                    $this->military_list[$key]['rank_id'] = [
                        'id' => $uptMilitary['rank']['id'],
                        'name' => $uptMilitary['rank']['name_'.config('app.locale')],
                    ];
                }
            }
        }
    }

    protected function fillInjury()
    {
        $updateInjury = $this->personnelModelData->injuries->toArray();

        if(!empty($updateInjury)) {
            foreach ($updateInjury as $key => $uptInjury)
            {
                $this->injury_list[] = [
                    'injury_type' => $uptInjury['injury_type'],
                    'location' => $uptInjury['location'],
                    'date_time' => $uptInjury['date_time'],
                    'description' => $uptInjury['description'],
                ];
            }
        }
    }

    public function addInjury()
    {
        $validator1 = $this->exceptArray('military');
        $validator2 = $this->exceptArray('captivity');
        $this->validate(array_intersect_assoc($validator1,$validator2));

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

        if(!empty($updateCaptivity)) {
            foreach ($updateCaptivity as $key => $uptCaptivity)
            {
                $this->captivity_list[] = [
                    'location' => $uptCaptivity['location'],
                    'condition' => $uptCaptivity['condition'],
                    'taken_captive_date' => $uptCaptivity['taken_captive_date'],
                    'release_date' => $uptCaptivity['release_date'],
                ];
            }
        }
    }

    public function addCaptivity()
    {
        $validator1 = $this->exceptArray('military');
        $validator2 = $this->exceptArray('injuries');
        $this->validate(array_intersect_assoc($validator1,$validator2));

        $this->captivity_list[] = $this->captivity;
        $this->captivity = [];
    }

    public function forceDeleteCaptivity($key)
    {
        unset($this->captivity_list[$key]);
    }
}
