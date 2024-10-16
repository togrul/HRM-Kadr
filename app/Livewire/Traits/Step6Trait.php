<?php

namespace App\Livewire\Traits;

trait Step6Trait
{
    public $awardId;

    public $awardName;

    public $searchAward;

    public $punishmentId;

    public $punishmentName;

    public $searchPunishment;

    //    public $criminalId,$criminalName,$searchCriminal;

    public $award = [];

    public $award_list = [];

    public $punishment = [];

    public $punishment_list = [];

    //    public $criminal = [];
    //    public $criminal_list = [];

    public function mountStep6Trait()
    {
        $this->awardName =
        $this->punishmentName =
//            $this->criminalName =
            '---';
        if (! empty($this->personnelModel)) {
            $this->fillAwards();
            $this->fillPunishments();
            //            $this->fillCriminals();
        }
    }

    public function addAward()
    {
        $validator1 = $this->exceptArray('punishment');
        //        $validator2 = $this->exceptArray('criminal');
        //        $this->validate(array_intersect_assoc($validator1,$validator2));
        $this->validate($validator1);
        $this->award_list[] = $this->award;
        $this->awardName = '---';
        $this->reset('awardId');
        $this->award = [];
    }

    public function forceDeleteAward($key)
    {
        unset($this->award_list[$key]);
    }

    public function addPunishment()
    {
        $validator1 = $this->exceptArray('award');
        //        $validator2 = $this->exceptArray('criminal');
        //        $this->validate(array_intersect_assoc($validator1,$validator2));
        $this->validate($validator1);
        $this->punishment['expired_date'] = $this->punishment['expired_date'] ?? null;
        $this->punishment_list[] = $this->punishment;
        $this->punishmentName = '---';
        $this->reset('punishmentId');
        $this->punishment = [];
    }

    public function forceDeletePunishment($key)
    {
        unset($this->punishment_list[$key]);
    }

    //    public function addCriminal()
    //    {
    //        $validator1 = $this->exceptArray('award');
    //        $validator2 = $this->exceptArray('punishment');
    //        $this->validate(array_intersect_assoc($validator1,$validator2));
    //        $this->criminal_list[] = $this->criminal;
    //        $this->criminalName = '---';
    //        $this->reset('criminalId');
    //        $this->criminal = [];
    //    }
    //
    //    public function forceDeleteCriminal($key)
    //    {
    //        unset($this->criminal_list[$key]);
    //    }

    protected function fillAwards()
    {
        $updateAward = $this->personnelModelData->awards->load(['award', 'award.type'])->toArray();

        if (! empty($updateAward)) {
            foreach ($updateAward as $key => $uptAward) {
                $this->award_list[] = $this->mapAttributes(
                    attributes: [
                        'reason', 'given_date', 'is_old',
                    ],
                    getFrom: $uptAward
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'award',
                    field: 'award_id',
                    key: $key,
                    fillTo: 'award_list',
                    getFrom: $uptAward,
                    titleField: 'name'
                );
            }
        }
    }

    protected function fillPunishments()
    {
        $updatePunishment = $this->personnelModelData->punishments->load('punishment')->toArray();

        if (! empty($updatePunishment)) {
            foreach ($updatePunishment as $key => $uptPunishment) {
                $this->punishment_list[] = $this->mapAttributes(
                    attributes: [
                        'reason', 'given_date', 'expired_date',
                    ],
                    getFrom: $uptPunishment
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'punishment',
                    field: 'punishment_id',
                    key: $key,
                    fillTo: 'punishment_list',
                    getFrom: $uptPunishment,
                    titleField: 'name'
                );
            }
        }
    }

    //    protected function fillCriminals()
    //    {
    //        $updatePunishmentCriminal = $this->personnelModelData->criminals->load('punishment')->toArray();
    //
    //        if(!empty($updatePunishmentCriminal))
    //        {
    //            foreach($updatePunishmentCriminal  as $key => $uptCriminal)
    //            {
    //                $this->criminal_list[] = [
    //                    'reason' => $uptCriminal['reason'],
    //                    'given_date' => $uptCriminal['given_date'],
    //                ];
    //
    //                if(!empty($uptCriminal['punishment_id']))
    //                {
    //                    $this->criminal_list[$key][ 'punishment_id'] = [
    //                        'id' => $uptCriminal['punishment']['id'],
    //                        'name' => $uptCriminal['punishment']['name'],
    //                    ];
    //                }
    //            }
    //        }
    //    }
}
