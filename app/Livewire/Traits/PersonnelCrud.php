<?php

namespace App\Livewire\Traits;

use App\Enums\KnowledgeStatusEnum;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\Validations\PersonnelValidationTrait;
use App\Models\Language;
use App\Models\ScientificDegreeAndName;
use App\Services\CallPersonnelInfo;
use Illuminate\Support\Arr;
use Livewire\Attributes\Isolate;
use Livewire\WithFileUploads;

trait PersonnelCrud
{
    use FillComplexArrayTrait;
    use PersonnelValidationTrait;
    use SelectListTrait;
    use Step1Trait;
    use Step2Trait;
    use Step3Trait;
    use Step4Trait;
    use Step5Trait;
    use Step6Trait;
    use Step7Trait;
    use Step8Trait;
    use WithFileUploads;

    public $title;

    public $step;

    public array $completedSteps;

    public function previousStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    public function exceptArray($arrayKey)
    {
        $filtered = array_filter($this->validationRules()[$this->step], function ($key) use ($arrayKey) {
            return str_starts_with($key, $arrayKey);
        }, ARRAY_FILTER_USE_KEY);

        return Arr::except($this->validationRules()[$this->step], array_keys($filtered));
    }

    public function selectStep($step): void
    {
        if ($this->step == 1) {
            $this->validate($this->validationRules()[$this->step]);
        }
        $this->step = $step;
    }

    protected function completeStep(bool $actionSave = false): void
    {
        if ($actionSave) {
            return;
        }

        $stepName = match ($this->step) {
            1 => 'personnel',
            2 => 'document',
            3 => 'education'
        };

        if ($stepName && count($this->{$stepName}) < 1) {
            $validator = $this->getValidationRulesForStep();

            $this->validate($validator);

            if (! in_array($stepName, $this->completedSteps)) {
                $this->completedSteps[] = $stepName;
            }
        }
    }

    private function getExceptedValidationsByStep(): array
    {
        $exceptedValidations = [];

        $stepConditions = [
            2 => ['service_cards' => $this->service_cards_list, 'passports' => $this->passports_list, 'document' => $this->document],
            3 => ['extra_education' => $this->extra_education_list],
            4 => ['labor_activities' => $this->labor_activities_list],
        ];

        foreach ($stepConditions[$this->step] ?? [] as $field => $list) {
            if (! empty($list)) {
                $exceptedValidations[] = $field;
            }
        }

        return $exceptedValidations;
    }

    private function getValidationRulesForStep(): array
    {
        $exceptedValidations = $this->getExceptedValidationsByStep();

        if (empty($exceptedValidations)) {
            return $this->validationRules()[$this->step] ?? [];
        }

        $specialConditions = array_map(
            fn ($field) => $this->exceptArray($field),
            $exceptedValidations
        );

        return array_intersect_assoc(...$specialConditions);
    }

    public function nextStep(): void
    {
        $this->isAddedRank = false;

        $validator = $this->getValidationRulesForStep();

        if (! empty($validator) && ! in_array($this->step, [5, 6, 7])) {
            $this->validate($validator);
        }

        $this->step++;
    }

    private function getSteps(): array
    {
        return [
            1 => __('Personal Information'),
            2 => __('Cards'),
            3 => __('Education'),
            4 => __('Labor activities'),
            5 => __('Military'),
            6 => __('Awards and punishments'),
            7 => __('Kinships'),
            8 => __('Other'),
        ];
    }

    #[Isolate]
    public function getIsolatedProperty(): array
    {
        return [
            'languageModel' => Language::all(),
            'knowledges' => KnowledgeStatusEnum::values(),
            'degrees' => ScientificDegreeAndName::all(),
        ];
    }

    protected function validateCommon($exclude)
    {
        $validators = array_map(fn ($field) => $this->exceptArray($field), $exclude);
        $this->validate(array_intersect_assoc(...$validators));
    }

    public function render()
    {
        $steps = ['steps' => $this->getSteps()];

        $view_data = resolve(CallPersonnelInfo::class)->getAll($this->isDisability, $this);

        $view_name = ! empty($this->personnelModel)
                    ? 'livewire.personnel.edit-personnel'
                    : 'livewire.personnel.add-personnel';

        return view($view_name, array_merge($steps, array_merge($view_data, $this->isolated)));
    }
}
