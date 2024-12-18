<?php

namespace App\Livewire\Traits;

use App\Enums\AttitudeMilitaryEnum;
use App\Models\AppealStatus;
use App\Models\Structure;
use Illuminate\Validation\Rule;

trait CandidateCrud
{
    use SelectListTrait;

    public $candidate = [];

    public $searchStructure;

    public $statusId;

    public $statusName;

    public $title;

    public $candidateModel;

    public function rules()
    {
        return [
            'candidate.name' => 'required|string|min:2',
            'candidate.surname' => 'required|string|min:2',
            'candidate.patronymic' => 'required|string|min:2',
            'candidate.structure_id.id' => 'required|int|exists:structures,id',
            'candidate.birthdate' => 'required|date',
            'candidate.gender' => 'required|int',
            'candidate.height' => 'required|int',
            'candidate.knowledge_test' => 'required|int',
            'candidate.physical_fitness_exam' => 'required|int',
            'candidate.attitude_to_military' => ['required', Rule::in(AttitudeMilitaryEnum::values())],
            'candidate.status_id.id' => 'required|int|exists:appeal_statuses,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'candidate.name' => __('Name'),
            'candidate.surname' => __('Surname'),
            'candidate.patronymic' => __('Patronymic'),
            'candidate.structure_id.id' => __('Structure'),
            'candidate.birthdate' => __('Birthdate'),
            'candidate.gender' => __('Gender'),
            'candidate.height' => __('Height'),
            'candidate.knowledge_test' => __('Knowledge test'),
            'candidate.physical_fitness_exam' => __('Physical fitness'),
            'candidate.attitude_to_military' => __('Attitude to military'),
            'candidate.status_id.id' => __('Status'),
        ];
    }

    public function mount()
    {
        $this->statusName = '---';
        if (! empty($this->candidateModel)) {
            $this->fillCandidate();
            $this->title = __('Edit candidate');
        } else {
            $this->title = __('Add candidate');
        }
    }

    public function render()
    {
        $structures = Structure::when(! empty($this->searchStructure), function ($q) {
            $q->where('name', 'LIKE', "%$this->searchStructure%");
        })
            ->ordered()
            ->get();

        $statuses = AppealStatus::where('locale', config('app.locale'))->get();

        $view_name = ! empty($this->candidateModel)
            ? 'livewire.candidates.edit-candidate'
            : 'livewire.candidates.add-candidate';

        return view($view_name, compact('structures', 'statuses'));
    }
}
