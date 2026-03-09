<?php

namespace App\Modules\Candidates\Support\Traits;

use App\Concerns\LoadsAppealStatuses;
use App\Enums\AttitudeMilitaryEnum;
use App\Modules\Candidates\Support\CandidateModeResolver;
use App\Models\Structure;
use App\Models\Candidate;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait CandidateCrud
{
    use LoadsAppealStatuses;
    use DropdownConstructTrait;
    use NormalizesDropdownPayloads;

    public $candidate = [];

    public $searchStructure;

    public $title;

    public $candidateModel;

    public ?Candidate $candidateModelData = null;

    public string $candidateMode = CandidateModeResolver::MILITARY;

    public function rules()
    {
        $rules = [
            'candidate.name' => 'required|string|min:2',
            'candidate.surname' => 'required|string|min:2',
            'candidate.patronymic' => 'required|string|min:2',
            'candidate.structure_id' => 'required|int|exists:structures,id',
            'candidate.birthdate' => 'required|date',
            'candidate.gender' => 'required|int',
            'candidate.height' => 'required|int',
            'candidate.knowledge_test' => 'required|int',
            'candidate.status_id' => 'required|int|exists:appeal_statuses,id',
        ];

        if ($this->isMilitaryCandidateMode()) {
            $rules['candidate.physical_fitness_exam'] = 'required|int';
            $rules['candidate.attitude_to_military'] = ['required', Rule::in(AttitudeMilitaryEnum::values())];
        } else {
            $rules['candidate.physical_fitness_exam'] = 'nullable|int';
            $rules['candidate.attitude_to_military'] = ['nullable', Rule::in(AttitudeMilitaryEnum::values())];
        }

        return $rules;
    }

    protected function validationAttributes()
    {
        return [
            'candidate.name' => __('candidates::common.labels.name'),
            'candidate.surname' => __('candidates::common.labels.surname'),
            'candidate.patronymic' => __('candidates::common.labels.patronymic'),
            'candidate.structure_id' => __('candidates::common.labels.structure'),
            'candidate.birthdate' => __('candidates::common.labels.birthdate'),
            'candidate.gender' => __('candidates::common.labels.gender'),
            'candidate.height' => __('candidates::common.labels.height'),
            'candidate.knowledge_test' => __('candidates::common.labels.knowledge_test'),
            'candidate.physical_fitness_exam' => __('candidates::common.labels.physical_fitness'),
            'candidate.attitude_to_military' => __('candidates::common.labels.attitude_to_military'),
            'candidate.status_id' => __('candidates::common.labels.status'),
        ];
    }

    public function mount()
    {
        $this->candidateMode = app(CandidateModeResolver::class)->resolve();

        if (! empty($this->candidateModel)) {
            $this->fillCandidate();
            $this->title = __('candidates::common.titles.edit_candidate') . ' - ' . "<span class='text-teal-500'>{$this->candidateModelData->fullname}</span>";
        } else {
            $this->authorize('create', Candidate::class);
            $this->title = __('candidates::common.titles.add_candidate');
            $this->candidate = [
              'structure_id' => null,
              'status_id' => null
            ];
        }

        $this->normalizeByMode();
    }

    #[Computed(persist: true)]
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
            ? 'candidates::livewire.candidates.edit-candidate'
            : 'candidates::livewire.candidates.add-candidate';

        return view($view_name, compact('statuses'));
    }

    public function isMilitaryCandidateMode(): bool
    {
        return $this->candidateMode === CandidateModeResolver::MILITARY;
    }

    public function candidateModeLabel(): string
    {
        return app(CandidateModeResolver::class)->label($this->candidateMode);
    }

    protected function normalizeByMode(): void
    {
        if ($this->isMilitaryCandidateMode()) {
            return;
        }

        // Keep civilian add/edit forms consistent; military-only fields are hidden.
        $this->candidate['attitude_to_military'] = data_get($this->candidate, 'attitude_to_military');
        $this->candidate['military_service'] = data_get($this->candidate, 'military_service');
        $this->candidate['hhk_date'] = data_get($this->candidate, 'hhk_date');
        $this->candidate['hhk_result'] = data_get($this->candidate, 'hhk_result');
        $this->candidate['useless_info'] = data_get($this->candidate, 'useless_info');
    }
}
