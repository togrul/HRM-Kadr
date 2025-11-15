<?php

namespace App\Livewire\Traits;

use App\Concerns\LoadsAppealStatuses;
use App\Enums\AttitudeMilitaryEnum;
use App\Models\Structure;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait CandidateCrud
{
    use LoadsAppealStatuses;
    use SelectListTrait;
    use DropdownConstructTrait;

    public $candidate = [];

    public $searchStructure;

    public $title;

    public $candidateModel;

    public function rules()
    {
        return [
            'candidate.name' => 'required|string|min:2',
            'candidate.surname' => 'required|string|min:2',
            'candidate.patronymic' => 'required|string|min:2',
            'candidate.structure_id' => 'required|int|exists:structures,id',
            'candidate.birthdate' => 'required|date',
            'candidate.gender' => 'required|int',
            'candidate.height' => 'required|int',
            'candidate.knowledge_test' => 'required|int',
            'candidate.physical_fitness_exam' => 'required|int',
            'candidate.attitude_to_military' => ['required', Rule::in(AttitudeMilitaryEnum::values())],
            'candidate.status_id' => 'required|int|exists:appeal_statuses,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'candidate.name' => __('Name'),
            'candidate.surname' => __('Surname'),
            'candidate.patronymic' => __('Patronymic'),
            'candidate.structure_id' => __('Structure'),
            'candidate.birthdate' => __('Birthdate'),
            'candidate.gender' => __('Gender'),
            'candidate.height' => __('Height'),
            'candidate.knowledge_test' => __('Knowledge test'),
            'candidate.physical_fitness_exam' => __('Physical fitness'),
            'candidate.attitude_to_military' => __('Attitude to military'),
            'candidate.status_id' => __('Status'),
        ];
    }

    public function mount()
    {
        if (! empty($this->candidateModel)) {
            $this->authorize('edit-candidates', $this->candidateModelData);
            $this->fillCandidate();
            $this->title = __('Edit candidate') . ' - ' . "<span class='text-teal-500'>{$this->candidateModelData->fullname}</span>";
        } else {
            $this->authorize('add-candidates');
            $this->title = __('Add candidate');
            $this->candidate = [
              'structure_id' => null,
              'status_id' => null
            ];
        }
    }

    #[Computed]
    public function structureOptions(): array
    {
        return $this->structureOptionsFor(
            search: $this->dropdownSearch('searchStructure'),
            selectedId: data_get($this->candidate, 'structure_id')
        );
    }

    protected function structureOptionsFor(string $search, $selectedId): array
    {
        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->orderBy('level')
            ->orderBy('code');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'candidate:structures',
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    public function render()
    {
        $statuses = $this->appealStatuses();

        $view_name = ! empty($this->candidateModel)
            ? 'livewire.candidates.edit-candidate'
            : 'livewire.candidates.add-candidate';

        return view($view_name, compact('statuses'));
    }
}
