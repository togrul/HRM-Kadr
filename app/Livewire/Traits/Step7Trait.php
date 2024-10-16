<?php

namespace App\Livewire\Traits;

trait Step7Trait
{
    public $kinship = [];

    public $kinship_list = [];

    public $kinshipId;

    public $kinshipName;

    public $searchKinship;

    public function mountStep7Trait()
    {
        $this->kinshipName = '---';
        ! empty($this->personnelModel) && $this->fillKinship();
    }

    public function addKinship()
    {
        $this->validate($this->validationRules()[$this->step]);
        $this->kinship_list[] = $this->kinship;
        $this->kinshipName = '---';
        $this->reset('kinshipId');
        $this->kinship = [];
    }

    public function forceDeleteKinship($key)
    {
        unset($this->kinship_list[$key]);
    }

    protected function fillKinship()
    {
        $updateKinship = $this->personnelModelData->kinships->load('kinship')->toArray();

        if (! empty($updateKinship)) {
            foreach ($updateKinship as $key => $uptKinship) {
                $this->kinship_list[] = $this->mapAttributes(
                    attributes: [
                        'fullname', 'birthdate', 'birth_place', 'company_name', 'position',
                        'residental_address', 'registered_address', 'birth_certificate_number', 'marriage_certificate_number',
                    ],
                    getFrom: $uptKinship
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'kinship',
                    field: 'kinship_id',
                    key: $key,
                    fillTo: 'kinship_list',
                    getFrom: $uptKinship,
                    titleField: 'name',
                    hasLocale: true
                );
            }
        }
    }
}
