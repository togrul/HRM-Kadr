<?php

namespace App\Livewire\Traits;

use App\Services\CalculateSeniorityService;

trait Step3Trait
{
    public $education = [];

    public $extra_education = [];

    public $extra_education_list = [];

    public $institutionId;

    public $institutionName;

    public $searchInstitution;

    public $educationFormId;

    public $educationFormName;

    public $searchEducationForm;

    public $hasExtraEducation;

    public $educationTypeId;

    public $educationTypeName;

    public $searchEducationType;

    public $extraInstitutionId;

    public $extraInstitutionName;

    public $searchExtraInstitution;

    public $extraEducationFormId;

    public $extraEducationFormName;

    public $searchExtraEducationForm;

    public $educationDocumentTypeId;

    public $educationDocumentTypeName;

    public $searchDocumentTyoe;

    public $calculatedDataEducation = [];

    public $calculatedDataExtraEducation = [];

    public function updatedEducation($value, $name)
    {
        if ($name == 'calculate_as_seniority') {
            $this->updateCoefficient($this->education, $value);
        }
    }

    public function updatedExtraEducation($value, $name)
    {
        if ($name == 'calculate_as_seniority') {
            $this->updateCoefficient($this->extra_education, $value);

        }
    }

    private function updateCoefficient(&$educationType, $value)
    {
        $educationType['coefficient'] = $value
            ? cache('settings')['Education coefficient']
            : null;
    }

    public function addEducation()
    {
        $this->validate($this->validationRules()[$this->step]);
        $this->extra_education['is_military'] ??= false;
        $this->extra_education_list[] = $this->extra_education;

        $this->extra_education = [];
        $this->educationTypeName = $this->extraInstitutionName = $this->educationDocumentTypeName = $this->extraEducationFormName = '---';
        $this->reset(['educationTypeId', 'extraInstitutionId', 'educationDocumentTypeId', 'extraEducationFormId']);
        $this->calculateSeniorityEducation();
    }

    public function forceDeleteData($key)
    {
        unset($this->extra_education_list[$key]);
        $this->calculateSeniorityEducation();
    }

    public function mountStep3Trait()
    {
        $this->institutionName = $this->educationFormName = $this->extraInstitutionName = $this->educationTypeName = $this->extraEducationFormName = $this->educationDocumentTypeName = '---';
        ! empty($this->personnelModel) && $this->fillEducation();
        $this->calculateSeniorityEducation();

    }

    private function calculateSeniorityEducation(): void
    {
        $this->calculateService = new CalculateSeniorityService;
        $this->calculatedDataEducation = ! empty($this->education)
                    ? $this->calculateService->calculateEducation($this->education)
                    : [];
        $this->calculatedDataExtraEducation = ! empty($this->extra_education_list)
                    ? $this->calculateService->calculateMultiEducation($this->extra_education_list)
                    : [];
    }

    protected function fillEducation()
    {
        if (! empty($this->personnelModelData->education)) {
            $updateEducation = $this->personnelModelData->education->load(['institution', 'form'])->toArray();

            if (! empty($updateEducation)) {
                $this->education = $this->mapAttributes(
                    attributes: [
                        'education_language', 'specialty', 'admission_year', 'graduated_year',
                        'profession_by_document', 'diplom_serie', 'diplom_no', 'diplom_given_date',
                        'coefficient', 'calculate_as_seniority', 'is_military',
                    ],
                    getFrom: $updateEducation,
                    booleanColumns: ['calculate_as_seniority', 'is_military']
                );

                $this->handleRelatedEntity(entity: 'institution', field: 'educational_institution_id', fillTo: 'education', getFrom: $updateEducation, titleField: 'name');
                $this->handleRelatedEntity(entity: 'form', field: 'education_form_id', fillTo: 'education', getFrom: $updateEducation, titleField: 'name_'.config('app.locale'), differentSelectInput: 'educationForm');
            }
        }

        $updateExtraEducation = $this->personnelModelData
            ->extraEducations
            ->load(['type', 'institution', 'form', 'documentType'])
            ->toArray();

        if (! empty($updateExtraEducation)) {
            $this->hasExtraEducation = true;
            foreach ($updateExtraEducation as $key => $xtraEdu) {
                $this->extra_education_list[] = $this->mapAttributes(
                    attributes: [
                        'name', 'shortname', 'education_language', 'education_program_name', 'admission_year', 'graduated_year',
                        'diplom_serie', 'diplom_no', 'diplom_given_date', 'coefficient', 'calculate_as_seniority', 'is_military',
                    ],
                    getFrom: $xtraEdu,
                    booleanColumns: ['calculate_as_seniority', 'is_military']
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'institution',
                    field: 'educational_institution_id',
                    key: $key,
                    fillTo: 'extra_education_list',
                    getFrom: $xtraEdu,
                    titleField: 'name'
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'form',
                    field: 'education_form_id',
                    key: $key,
                    fillTo: 'extra_education_list',
                    getFrom: $xtraEdu,
                    titleField: 'name',
                    hasLocale: true
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'type',
                    field: 'education_type_id',
                    key: $key,
                    fillTo: 'extra_education_list',
                    getFrom: $xtraEdu,
                    titleField: 'name',
                );

                $this->handleRelatedEntitiesMultiDimensional(
                    entity: 'document_type',
                    field: 'education_document_type_id',
                    key: $key,
                    fillTo: 'extra_education_list',
                    getFrom: $xtraEdu,
                    titleField: 'name',
                );
            }
        }
    }
}
