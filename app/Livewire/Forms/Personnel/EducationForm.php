<?php

namespace App\Livewire\Forms\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Arr;
use Livewire\Form;

class EducationForm extends Form
{
    public array $education = [];

    public array $extraEducation = [];

    public array $extraEducationList = [];

    public bool $hasExtraEducation = false;

    public function resetForm(): void
    {
        $this->education = $this->defaultEducation();
        $this->extraEducation = $this->defaultExtraEducation();
        $this->extraEducationList = [];
        $this->hasExtraEducation = false;
    }

    public function resetExtraEducation(): void
    {
        $this->extraEducation = $this->defaultExtraEducation();
    }

    public function fillFromArrays(
        array $education,
        array $extraEducation,
        array $extraEducationList,
        bool $hasExtraEducation
    ): void {
        $this->education = ! empty($education)
            ? array_replace($this->defaultEducation(), $education)
            : $this->defaultEducation();

        $this->extraEducation = ! empty($extraEducation)
            ? array_replace($this->defaultExtraEducation(), $extraEducation)
            : $this->defaultExtraEducation();

        $this->extraEducationList = $extraEducationList;
        $this->hasExtraEducation = $hasExtraEducation;
    }

    public function fillFromModel(?Personnel $personnel): void
    {
        $this->resetForm();

        if (! $personnel) {
            return;
        }

        $personnel->loadMissing([
            'education.educationalInstitution',
            'education.educationForm',
            'extraEducations.educationalInstitution',
            'extraEducations.educationForm',
            'extraEducations.educationType',
            'extraEducations.documentType',
        ]);

        $education = $personnel->education;

        if ($education) {
            $this->education = array_replace(
                $this->defaultEducation(),
                Arr::only($education->toArray(), array_keys($this->defaultEducation()))
            );

            $this->education['educational_institution_id'] = $education->educational_institution_id;
            $this->education['education_form_id'] = $education->education_form_id;
            $this->education['calculate_as_seniority'] = (bool) $this->education['calculate_as_seniority'];
            $this->education['is_military'] = (bool) $this->education['is_military'];
        }

        $this->extraEducationList = $personnel->extraEducations
            ->map(function ($extra) {
                $payload = array_replace(
                    $this->defaultExtraEducation(),
                    Arr::only($extra->toArray(), array_keys($this->defaultExtraEducation()))
                );

                $payload['education_type_id'] = $extra->education_type_id;
                $payload['educational_institution_id'] = $extra->educational_institution_id;
                $payload['education_form_id'] = $extra->education_form_id;
                $payload['education_document_type_id'] = $extra->education_document_type_id;
                $payload['calculate_as_seniority'] = (bool) $payload['calculate_as_seniority'];
                $payload['is_military'] = (bool) $payload['is_military'];

                return $payload;
            })
            ->values()
            ->all();

        $this->hasExtraEducation = ! empty($this->extraEducationList);
    }

    protected function defaultEducation(): array
    {
        return [
            'educational_institution_id' => null,
            'education_form_id' => null,
            'education_language' => null,
            'specialty' => null,
            'admission_year' => null,
            'graduated_year' => null,
            'profession_by_document' => null,
            'diplom_serie' => null,
            'diplom_no' => null,
            'diplom_given_date' => null,
            'coefficient' => null,
            'calculate_as_seniority' => false,
            'is_military' => false,
        ];
    }

    protected function defaultExtraEducation(): array
    {
        return [
            'education_type_id' => null,
            'educational_institution_id' => null,
            'education_form_id' => null,
            'name' => null,
            'shortname' => null,
            'education_language' => null,
            'education_program_name' => null,
            'admission_year' => null,
            'graduated_year' => null,
            'education_document_type_id' => null,
            'diplom_serie' => null,
            'diplom_no' => null,
            'diplom_given_date' => null,
            'coefficient' => null,
            'calculate_as_seniority' => false,
            'is_military' => false,
        ];
    }
}
