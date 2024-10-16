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

    public function selectStep($step)
    {
        if ($this->step == 1) {
            $this->validate($this->validationRules()[$this->step]);
        }
        $this->step = $step;
    }

    protected function completeStep()
    {
        $stepName = match ($this->step) {
            1 => 'personnel',
            2 => 'document',
            3 => 'education'
        };
        if (count($this->{$stepName}) > 0) {
            $validator = ! empty($this->extra_education_list)
                            ? $this->exceptArray('extra_education')
                            : $this->validationRules()[$this->step];
            $this->validate($validator);
            ! in_array($stepName, $this->completedSteps) && $this->completedSteps[] = $stepName;
        }
    }

    public function nextStep()
    {
        $this->isAddedRank = false;
        if (
            ($this->step == 3 && ! empty($this->extra_education_list)) ||
            ($this->step == 4 && ! empty($this->labor_activities_list))
        ) {
            $exceptValidation = match ($this->step) {
                3 => 'extra_education',
                4 => 'labor_activities',
                default => ''
            };
            $validator = $this->exceptArray($exceptValidation);
            ! empty($validator) && $this->validate($validator);
        } else {
            $this->validate($this->validationRules()[$this->step]);
        }

        $this->step++;

    }

    private function getSteps()
    {
        return [
            1 => __('Personal Information'),
            2 => __('ID document'),
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
