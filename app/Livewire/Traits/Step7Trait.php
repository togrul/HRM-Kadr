<?php

namespace App\Livewire\Traits;

trait Step7Trait
{
    public $kinship = [];
    public $kinship_list = [];
    public $kinshipId,$kinshipName,$searchKinship;

    public function mountStep7Trait() {
        $this->kinshipName = '---';
        !empty($this->personnelModel) && $this->fillKinship();
    }

    public function addKinship()
    {
        $this->validate($this->validationRules()[$this->step]);
        $this->kinship_list[] = $this->kinship;
        $this->kinshipName = '---';
        $this->reset('kinshipId');
        $this->kinship = [];;
    }

    public function forceDeleteKinship($key)
    {
        unset($this->kinship_list[$key]);
    }

    protected function fillKinship()
    {
        $updateKinship = $this->personnelModelData->kinships->load('kinship')->toArray();

        if(!empty($updateKinship))
        {
            foreach($updateKinship  as $key => $uptKinship)
            {
                $this->kinship_list[] = [
                    'fullname' => $uptKinship['fullname'],
                    'birthdate' => $uptKinship['birthdate'],
                    'birth_place' => $uptKinship['birth_place'],
                    'company_name' => $uptKinship['company_name'],
                    'position' => $uptKinship['position'],
                    'registered_address' => $uptKinship['registered_address'],
                    'residental_address' => $uptKinship['residental_address'],
                    'birth_certificate_number' => $uptKinship['birth_certificate_number'],
                    'marriage_certificate_number' => $uptKinship['marriage_certificate_number'],
                ];

                if(!empty($uptKinship['kinship_id']))
                {
                    $this->kinship_list[$key]['kinship_id'] = [
                        'id' => $uptKinship['kinship']['id'],
                        'name' => $uptKinship['kinship']['name_'.config('app.locale')],
                    ];
                }
            }
        }
    }
}
