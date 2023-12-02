<?php

namespace App\Livewire\Traits;

trait Step5Trait
{
    public $militaryRankId,$militaryRankName,$searchMilitaryRank;

    public $military = [];
    public $military_list = [];

    public function mountStep5Trait() { 
        $this->militaryRankName = '---';
        !empty($this->personnelModel) && $this->fillMilitary();
    }

    public function addMilitary()
    {
        //property qoy add methoddusa onda yoxla validation yoxsa yoxlama
        $this->validate($this->validationRules()[$this->step]);
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
}