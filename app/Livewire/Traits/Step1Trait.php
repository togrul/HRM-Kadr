<?php

namespace App\Livewire\Traits;

trait Step1Trait
{
    public $personnel = [];
    public $nationalityName,$nationalityId;

    public $previousNationalityName,$previousNationalityId;

    public $searchNationality,$searchPreviousNationality;

    public $educationDegreeId,$educationDegreeName,$searchEducationDegree;

    public $structureId,$structureName,$searchStructure;

    public $positionId,$positionName,$searchPosition;

    public $workNormId,$workNormName,$searchWorkNorm;

    public $disabilityId,$disabilityName,$searchDisability;

    public $socialOriginId,$socialOriginName,$searchSocialOrigin;

    public $isDisability;
    public $avatar;

    public function updatedAvatar()
    {
        $this->validate([
            'avatar' => 'image|max:2048',
        ]);
    }

    public function mountStep1Trait() {
        $this->isDisability = false;
        $this->personnel = [
            'has_changed_initials' => false,
            'has_changed_nationality' => false
        ];
        $this->nationalityName = $this->previousNationalityName = $this->structureName = $this->educationDegreeName = $this->positionName = $this->workNormName = $this->disabilityName = $this->socialOriginName = '---';

        !empty($this->personnelModel) && $this->fillPersonnel();

    }

    protected function fillPersonnel()
    {
        $this->updatePersonnel = $this->personnelModelData->toArray();
        $this->personnel = [
            'tabel_no' => $this->updatePersonnel['tabel_no'],
            'surname' => $this->updatePersonnel['surname'],
            'name' => $this->updatePersonnel['name'],
            'patronymic' => $this->updatePersonnel['patronymic'],
            'photo' => $this->updatePersonnel['photo'],
            'has_changed_initials' => $this->updatePersonnel['has_changed_initials'],
            'previous_surname' => $this->updatePersonnel['previous_surname'],
            'previous_name' => $this->updatePersonnel['previous_name'],
            'previous_patronymic' => $this->updatePersonnel['previous_patronymic'],
            'initials_changed_date' => $this->updatePersonnel['initials_changed_date'],
            'initials_change_reason' => $this->updatePersonnel['initials_change_reason'],
            'birthdate' => $this->updatePersonnel['birthdate'],
            'gender' => $this->updatePersonnel['gender'],
            'mobile' => $this->updatePersonnel['mobile'],
            'phone' => $this->updatePersonnel['phone'],
            'email' => $this->updatePersonnel['email'],
            'has_changed_nationality' => $this->updatePersonnel['has_changed_nationality'],
            'nationality_changed_date' => $this->updatePersonnel['nationality_changed_date'],
            'nationality_change_reason' => $this->updatePersonnel['nationality_change_reason'],
            'pin' => $this->updatePersonnel['pin'],
            'residental_address' => $this->updatePersonnel['residental_address'],
            'registered_address' => $this->updatePersonnel['registered_address'],
            'join_work_date' => $this->updatePersonnel['join_work_date'],
            'leave_work_date' => $this->updatePersonnel['leave_work_date'],
            'extra_important_information' => $this->updatePersonnel['extra_important_information'],
            'computer_knowledge' => $this->updatePersonnel['computer_knowledge'],
            'scientific_works_inventions' => $this->updatePersonnel['scientific_works_inventions'],
        ];

        $this->personnel_extra = [
            'participation_in_war' => $this->updatePersonnel['participation_in_war'],
            'discrediting_information' => $this->updatePersonnel['discrediting_information'],
        ];

        if(!empty($this->updatePersonnel['nationality_id']))
        {
            $this->personnel['nationality_id'] = [
                'id' => $this->updatePersonnel['nationality']['id'],
                'title' => $this->updatePersonnel['nationality']['title'],
            ];
            $this->nationalityId = $this->updatePersonnel['nationality']['id'];
            $this->nationalityName = $this->updatePersonnel['nationality']['title'];
        }
        if(!empty($this->updatePersonnel['previous_nationality_id']))
        {
            $this->personnel['previous_nationality_id'] = [
                'id' => $this->updatePersonnel['previous_nationality']['id'],
                'title' => $this->updatePersonnel['previous_nationality']['title'],
            ];
            $this->previousNationalityId = $this->updatePersonnel['previous_nationality']['id'];
            $this->previousNationalityName = $this->updatePersonnel['previous_nationality']['title'];
        }
        if(!empty($this->updatePersonnel['education_degree_id']))
        {
            $this->personnel['education_degree_id'] = [
                'id' => $this->updatePersonnel['education_degree']['id'],
                'title' => $this->updatePersonnel['education_degree']['title_'.config('app.locale')],
            ];
            $this->educationDegreeId = $this->updatePersonnel['education_degree']['id'];
            $this->educationDegreeName = $this->updatePersonnel['education_degree']['title_'.config('app.locale')];
        }
        if(!empty($this->updatePersonnel['structure_id']))
        {
            $this->personnel['structure_id'] = [
                'id' => $this->updatePersonnel['structure']['id'],
                'name' => $this->updatePersonnel['structure']['name'],
            ];
            $this->structureId = $this->updatePersonnel['structure']['id'];
            $this->structureName = $this->updatePersonnel['structure']['name'];
        }
        if(!empty($this->updatePersonnel['position_id']))
        {
            $this->personnel['position_id'] = [
                'id' => $this->updatePersonnel['position']['id'],
                'name' => $this->updatePersonnel['position']['name'],
            ];
            $this->positionId = $this->updatePersonnel['position']['id'];
            $this->positionName = $this->updatePersonnel['position']['name'];
        }
        if(!empty($this->updatePersonnel['work_norm_id']))
        {
            $this->personnel['work_norm_id'] = [
                'id' => $this->updatePersonnel['work_norm']['id'],
                'name' => $this->updatePersonnel['work_norm']['name_'.config('app.locale')],
            ];
            $this->workNormId = $this->updatePersonnel['work_norm']['id'];
            $this->workNormName = $this->updatePersonnel['work_norm']['name_'.config('app.locale')];
        }
        if(!empty($this->updatePersonnel['disability_id']))
        {
            $this->personnel['disability_id'] = [
                'id' => $this->updatePersonnel['disability']['id'],
                'name' => $this->updatePersonnel['disability']['name'],
            ];
            $this->disabilityId = $this->updatePersonnel['disability']['id'];
            $this->disabilityName = $this->updatePersonnel['disability']['name'];
            $this->personnel['disability_given_date'] = $this->updatePersonnel['disability_given_date'];
            $this->isDisability = true;
        }

        if(!empty($this->updatePersonnel['social_origin_id']))
        {
            $this->personnel['social_origin_id'] = [
                'id' => $this->updatePersonnel['social_origin']['id'],
                'name' => $this->updatePersonnel['social_origin']['name'],
            ];
            $this->socialOriginId = $this->updatePersonnel['social_origin']['id'];
            $this->socialOriginName = $this->updatePersonnel['social_origin']['name'];
        }
    }

}
