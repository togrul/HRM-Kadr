<?php

namespace App\Modules\Candidates\Support\Traits;

use App\Concerns\LoadsAppealStatuses;
use App\Models\Candidate;
use App\Models\Structure;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Modules\Candidates\Application\Services\CandidateProfileFieldSchemaService;
use App\Modules\Candidates\Support\CandidateModeResolver;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use App\Traits\NormalizesDropdownPayloads;
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
        return array_merge(
            app(CandidateProfileFieldSchemaService::class)->coreRules(),
            app(CandidateProfileFieldSchemaService::class)->packRules($this->candidateWorkflowPack())
        );
    }

    protected function validationAttributes()
    {
        return app(CandidateProfileFieldSchemaService::class)->validationAttributeLabels($this->candidateWorkflowPack());
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
                'status_id' => null,
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

    public function candidateWorkflowPack(): string
    {
        return app(CandidateWorkflowPackResolver::class)->resolve();
    }

    public function candidatePackFieldRows(): array
    {
        return app(CandidateProfileFieldSchemaService::class)->rowsForPack($this->candidateWorkflowPack());
    }

    public function candidateFieldOptions(array $field): array
    {
        return app(CandidateProfileFieldSchemaService::class)->optionsForField($field);
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
