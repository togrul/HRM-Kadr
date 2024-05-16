<?php

namespace App\Livewire\Traits;

use App\Models\PersonnelLaborActivity;
use App\Services\CalculateSeniorityService;

trait Step4Trait
{
    public $labor_activities = [];
    public $labor_activities_list = [];

    public $ranks = [];
    public $rank_list = [];

    public $isAddedRank;

    public $rankId,$rankName,$searchRank;

    public $isSpecialService;

    public $calculatedData = [];

    private $calculateService;

    public function addLaborActivity()
    {
        $this->validate($this->exceptArray('ranks'));
        $time = array_key_exists('time',$this->labor_activities) ? $this->labor_activities['time'] : '12:00';
        if($this->isSpecialService)
        {
            $this->labor_activities['is_special_service'] = $this->isSpecialService ? 1 : 0;
            $this->labor_activities['order_date'] .= " {$time}";
            unset($this->labor_activities['time']);
        }
        $this->labor_activities_list[] = $this->labor_activities;
        $this->labor_activities = array();
        $this->calculateSeniority();
    }

    public function addRank()
    {
        //property qoy add methoddusa onda yoxla validation yoxsa yoxlama

        $this->isAddedRank = true;
        $this->validate($this->exceptArray('labor_activities'));
        $this->rank_list[] = $this->ranks;
        $this->rankName = '---';
        $this->reset('rankId');
        $this->ranks = [];
        $this->isAddedRank = false;
    }

    public function forceDeleteLaborActivity($key)
    {
        unset($this->labor_activities_list[$key]);
        $this->calculateSeniority();
    }

    public function forceDeleteRank($key)
    {
        unset($this->rank_list[$key]);
    }

    public function mountStep4Trait() {
        $this->isAddedRank = false;
        $this->rankName = "---";
        !empty($this->personnelModel) && $this->fillStep4();
        $this->isSpecialService = false;
        $this->calculateSeniority();
    }

    private function calculateSeniority()
    {
        $this->calculateService = resolve(CalculateSeniorityService::class);
        $this->calculatedData = $this->calculateService->calculateMulti($this->labor_activities_list);
    }

    protected function fillStep4()
    {
        $updateLaborActivity = $this->personnelModelData->laborActivities;
        if(!empty($updateLaborActivity))
        {
            foreach($updateLaborActivity  as $key => $uptLabor)
            {
                $this->labor_activities_list[] = [
                    'company_name' => $uptLabor['company_name'],
                    'position' => $uptLabor['position'],
                    'coefficient' => $uptLabor['coefficient'],
                    'join_date' => $uptLabor['join_date'],
                    'leave_date' => $uptLabor['leave_date'],
                    'is_special_service' => $uptLabor['is_special_service'] == 1 ? true : false,
                    'order_given_by' => $uptLabor['order_given_by'],
                    'order_no' => $uptLabor['order_no'],
                    'order_date' => $uptLabor['order_date'],
                    'is_current' => $uptLabor['is_current']
                ];
            }
        }

        $updateRanks = $this->personnelModelData->ranks->load('rank')->toArray();

        if(!empty($updateRanks))
        {
            foreach($updateRanks  as $key => $uptRank)
            {
                $this->rank_list[] = [
                    'name' => $uptRank['name'],
                    'given_date' => $uptRank['given_date'],
                ];

                if(!empty($uptRank['rank_id']))
                {
                    $this->rank_list[$key]['rank_id'] =  [
                        'id' => $uptRank['rank']['id'],
                        'name' => $uptRank['rank']['name_'.config('app.locale')],
                    ];
                }
            }
        }
    }
}
