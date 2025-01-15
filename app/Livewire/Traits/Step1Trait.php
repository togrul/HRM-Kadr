<?php

namespace App\Livewire\Traits;

trait Step1Trait
{
    public $personnel = [];

    public $nationalityName;

    public $nationalityId;

    public $previousNationalityName;

    public $previousNationalityId;

    public $searchNationality;

    public $searchPreviousNationality;

    public $educationDegreeId;

    public $educationDegreeName;

    public $searchEducationDegree;

    public $structureId;

    public $structureName;

    public $searchStructure;

    public $positionId;

    public $positionName;

    public $searchPosition;

    public $workNormId;

    public $workNormName;

    public $searchWorkNorm;

    public $disabilityId;

    public $disabilityName;

    public $searchDisability;

    public $socialOriginId;

    public $socialOriginName;

    public $searchSocialOrigin;

    public $isDisability;

    public $avatar;

    public function updatedAvatar()
    {
        $this->validate([
            'avatar' => 'image|max:2048',
        ]);
    }

    public function mountStep1Trait()
    {
        $this->isDisability = false;
        $this->personnel = [
            'has_changed_initials' => false,
            'has_changed_nationality' => false,
        ];
        $this->nationalityName = $this->previousNationalityName = $this->structureName = $this->educationDegreeName = $this->positionName = $this->workNormName = $this->disabilityName = $this->socialOriginName = '---';

        ! empty($this->personnelModel) && $this->fillPersonnel();

    }

    protected function fillPersonnel(): void
    {
        $this->updatePersonnel = $this->personnelModelData->toArray();
        $this->personnel = $this->mapAttributes(attributes: [
            'tabel_no', 'surname', 'name', 'patronymic', 'photo',
            'has_changed_initials', 'previous_surname', 'previous_name',
            'previous_patronymic', 'initials_changed_date', 'initials_change_reason',
            'birthdate', 'gender', 'mobile', 'phone', 'email',
            'has_changed_nationality', 'nationality_changed_date',
            'nationality_change_reason', 'pin', 'residental_address',
            'registered_address', 'join_work_date', 'leave_work_date',
            'extra_important_information', 'computer_knowledge',
            'scientific_works_inventions', 'referenced_by',
            'special_inspection_date', 'special_inspection_result',
            'medical_inspection_date', 'medical_inspection_result',
        ], getFrom: $this->updatePersonnel);

        $this->personnel_extra = $this->mapAttributes(
            attributes: ['participation_in_war', 'discrediting_information'],
            getFrom: $this->updatePersonnel
        );

        $this->handleRelatedEntity(entity: 'nationality', field: 'nationality_id', fillTo: 'personnel', getFrom: $this->updatePersonnel);
        $this->handleRelatedEntity(entity: 'previous_nationality', field: 'previous_nationality_id', fillTo: 'personnel', getFrom: $this->updatePersonnel);
        $this->handleRelatedEntity(entity: 'education_degree', field: 'education_degree_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'title_'.config('app.locale'));
        $this->handleRelatedEntity(entity: 'structure', field: 'structure_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'name');
        $this->handleRelatedEntity(entity: 'position', field: 'position_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'name');
        $this->handleRelatedEntity(entity: 'work_norm', field: 'work_norm_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'name_'.config('app.locale'));
        $this->handleRelatedEntity(entity: 'disability', field: 'disability_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'name', extraOptions: [
            'extra_field' => 'disability_given_date',
            'flag' => 'isDisability',
        ]);
        $this->handleRelatedEntity(entity: 'social_origin', field: 'social_origin_id', fillTo: 'personnel', getFrom: $this->updatePersonnel, titleField: 'name');
    }
}
