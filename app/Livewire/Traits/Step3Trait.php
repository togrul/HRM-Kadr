<?php

namespace App\Livewire\Traits;

use App\Services\CalculateSeniorityService;

trait Step3Trait
{
    public $education = [];
    public $extra_education = [];
    public $extra_education_list = [];

    public $institutionId,$institutionName,$searchInstitution;

    public $educationFormId,$educationFormName,$searchEducationForm;

    public $hasExtraEducation;

    public $educationTypeId,$educationTypeName,$searchEducationType;

    public $extraInstitutionId,$extraInstitutionName,$searchExtraInstitution;
    public $extraEducationFormId,$extraEducationFormName,$searchExtraEducationForm;
    public $educationDocumentTypeId,$educationDocumentTypeName,$searchDocumentTyoe;

    public $calculatedDataEducation = [];
    public $calculatedDataExtraEducation = [];

    public function updatedEducation($value,$name)
    {
        if($name == "calculate_as_seniority")
        {
            $this->education['coefficient'] = $value
                            ? cache('settings')['Education coefficient']
                            : null;
        }
    }

    public function updatedExtraEducation($value,$name)
    {
        if($name == "calculate_as_seniority")
        {
            $this->extra_education['coefficient'] = $value
                            ? cache('settings')['Education coefficient']
                            : null;
        }
    }

    public function addEducation()
    {
        $this->validate($this->validationRules()[$this->step]);
        $this->extra_education_list[] = $this->extra_education;

        $this->extra_education = array();
        $this->educationTypeName = $this->extraInstitutionName = $this->educationDocumentTypeName = $this->extraEducationFormName = '---';
        $this->reset(['educationTypeId','extraInstitutionId','educationDocumentTypeId','extraEducationFormId']);
        $this->calculateSeniorityEducation();
    }

    public function forceDeleteData($key)
    {
        unset($this->extra_education_list[$key]);
        $this->calculateSeniorityEducation();
    }

    public function mountStep3Trait() {
        $this->institutionName = $this->educationFormName = $this->extraInstitutionName = $this->educationTypeName = $this->extraEducationFormName = $this->educationDocumentTypeName = '---';
        !empty($this->personnelModel) && $this->fillEducation();
        $this->calculateSeniorityEducation();

    }

    private function calculateSeniorityEducation()
    {
        $this->calculateService = resolve(CalculateSeniorityService::class);
        $this->calculatedDataEducation = !empty($this->education)
                    ? $this->calculateService->calculateEducation($this->education)
                    : [];
        $this->calculatedDataExtraEducation = !empty($this->extra_education_list)
                    ? $this->calculateService->calculateMultiEducation($this->extra_education_list)
                    : [];
    }


    protected function fillEducation()
    {
        if(!empty($this->personnelModelData->education))
        {
            $updateEducation = $this->personnelModelData->education->load(['institution','form'])->toArray();

            if(!empty($updateEducation))
            {
                $this->education = [
                    'education_language' => $updateEducation['education_language'],
                    'specialty' => $updateEducation['specialty'],
                    'admission_year' => $updateEducation['admission_year'],
                    'graduated_year' => $updateEducation['graduated_year'],
                    'profession_by_document' => $updateEducation['profession_by_document'],
                    'diplom_serie' => $updateEducation['diplom_serie'],
                    'diplom_no' => $updateEducation['diplom_no'],
                    'diplom_given_date' => $updateEducation['diplom_given_date'],
                    'coefficient' => $updateEducation['coefficient'],
                    'calculate_as_seniority' => $updateEducation['calculate_as_seniority'] == 1 ? true : false,
                    'is_military' => $updateEducation['is_military'] == 1 ? true : false,
                ];

                if(!empty($updateEducation['educational_institution_id']))
                {
                    $this->education['educational_institution_id'] = [
                        'id' => $updateEducation['institution']['id'],
                        'name' => $updateEducation['institution']['name'],
                    ];
                    $this->institutionId = $updateEducation['institution']['id'];
                    $this->institutionName = $updateEducation['institution']['name'];
                }

                if(!empty($updateEducation['education_form_id']))
                {
                    $this->education['education_form_id'] = [
                        'id' => $updateEducation['form']['id'],
                        'name' => $updateEducation['form']['name_'.config('app.locale')],
                    ];
                    $this->educationFormId = $updateEducation['form']['id'];
                    $this->educationFormName = $updateEducation['form']['name_'.config('app.locale')];
                }
            }
        }

        $updateExtraEducation = $this->personnelModelData
                                ->extraEducations
                                ->load(['type','institution','form','documentType'])
                                ->toArray();

        if(!empty($updateExtraEducation))
        {
            $this->hasExtraEducation = true;
            foreach($updateExtraEducation  as $key => $xtraEdu)
            {
                $this->extra_education_list[] = [
                    'name' => $xtraEdu['name'],
                    'shortname' => $xtraEdu['shortname'],
                    'education_language' => $xtraEdu['education_language'],
                    'education_program_name' => $xtraEdu['education_program_name'],
                    'admission_year' => $xtraEdu['admission_year'],
                    'graduated_year' => $xtraEdu['graduated_year'],
                    'diplom_serie' => $xtraEdu['diplom_serie'],
                    'diplom_no' => $xtraEdu['diplom_no'],
                    'diplom_given_date' => $xtraEdu['diplom_given_date'],
                    'coefficient' => $xtraEdu['coefficient'],
                    'calculate_as_seniority' => $xtraEdu['calculate_as_seniority'] == 1 ? true : false,
                    'is_military' => $xtraEdu['is_military'] == 1 ? true : false,
                ];
                if(!empty($xtraEdu['educational_institution_id']))
                {
                    $this->extra_education_list[$key][ 'educational_institution_id'] = [
                        'id' => $xtraEdu['institution']['id'],
                        'name' => $xtraEdu['institution']['name'],
                    ];
                }

                if(!empty($xtraEdu['education_form_id']))
                {
                    $this->extra_education_list[$key]['education_form_id'] =  [
                        'id' => $xtraEdu['form']['id'],
                        'name' => $xtraEdu['form']['name_'.config('app.locale')],
                    ];
                }

                if(!empty($xtraEdu['education_type_id']))
                {
                    $this->extra_education_list[$key]['education_type_id'] =  [
                        'id' => $xtraEdu['type']['id'],
                        'name' => $xtraEdu['type']['name'],
                    ];
                }

                if(!empty($xtraEdu['education_document_type_id']))
                {
                    $this->extra_education_list[$key]['education_document_type_id'] = [
                        'id' => $xtraEdu['document_type']['id'],
                        'name' => $xtraEdu['document_type']['name'],
                    ];
                }
            }
        }
    }
}
