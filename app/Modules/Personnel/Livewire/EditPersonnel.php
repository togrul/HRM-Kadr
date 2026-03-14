<?php

namespace App\Modules\Personnel\Livewire;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Modules\Personnel\Services\PersonnelFormAssembler;
use App\Modules\Personnel\Support\Traits\PersonnelCrud;
use App\Modules\Personnel\Support\Traits\RelationCruds\RelationCrudTrait;
use App\Modules\Personnel\Services\PersonnelPersistenceService;
use App\Models\Personnel;
use App\Services\PersonnelPendingApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class EditPersonnel extends Component
{
    use AuthorizesRequests;
    use PersonnelCrud;
    use RelationCrudTrait;

    public PersonalInformationForm $personalForm;
    public DocumentForm $documentForm;
    public EducationForm $educationForm;
    public LaborActivityForm $laborActivityForm;
    public ServiceHistoryForm $historyForm;
    public AwardsPunishmentsForm $awardsPunishmentsForm;
    public KinshipForm $kinshipForm;
    public MiscellaneousForm $miscForm;

    public $updatePersonnel;

    protected ?Personnel $personnelModelData = null;

    public $personnelModel;

    /** @var array<int> */
    public array $loadedSteps = [];

    public ?int $loadedPersonnelId = null;

    /** @var array<string> */
    protected array $relationGroupsLoaded = [];

    public function mount()
    {
        $personnel = $this->personnelModelDataInstance();

        $this->authorize('update', $personnel);
        $this->title = __('personnel::common.titles.edit_personnel');
        $this->step = 1;
        $this->resetStepTrackingFor($personnel->getKey());
        $this->loadStepData((int) $this->step);
    }

    protected function ensureCurrentStepDataLoaded(): void
    {
        $this->loadStepData((int) $this->step);
    }

    public function confirmPersonnel(): void
    {
        app(PersonnelPendingApprovalService::class)->approve($this->personnelModelDataInstance());
        $this->dispatch('addError', __('personnel::common.messages.personnel_updated'));
    }

    public function store()
    {
        $personnel = $this->personnelModelDataInstance();
        $this->ensureCurrentStepDataLoaded();
        $this->authorize('update', $personnel);
        $this->validateCurrentStepForSave();

        if (! empty($this->avatar)) {
            $this->personalForm->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }

        $assembled = app(PersonnelFormAssembler::class)->buildForStore(
            personalForm: $this->personalForm,
            documentForm: $this->documentForm,
            educationForm: $this->educationForm,
            laborActivityForm: $this->laborActivityForm,
            historyForm: $this->historyForm,
            awardsPunishmentsForm: $this->awardsPunishmentsForm,
            kinshipForm: $this->kinshipForm,
            miscForm: $this->miscForm,
            dateFields: $personnel->dateList(),
            dateNormalizer: fn (array $payload, array $dates): array => $this->modifyArray($payload, $dates)
        );

        if (in_array($this->step, [2, 3, 4], true)) {
            $this->completeStep(actionSave: true);
        }

        $relationPayloads = app(PersonnelPersistenceService::class)->payloadsForLoadedSteps(
            payloads: $assembled['relation_payloads'],
            loadedSteps: $this->loadedSteps
        );

        DB::transaction(function () use ($assembled, $relationPayloads) {
            $personnel = $this->personnelModelDataInstance();
            $personnel->update($assembled['personnel_data']);
            $this->updatePersonnelRelations($relationPayloads);

            if (! empty($assembled['personnel_extra'])) {
                $personnel->update($assembled['personnel_extra']);
            }
        });

        $this->dispatchPersonnelStored(__('personnel::common.messages.personnel_updated'));
        $this->dispatchModalCloseEvent();
    }

    protected function onStepChanged(int $step): void
    {
        $this->loadStepData($step);
    }

    protected function loadStepData(int $step): void
    {
        if ($step < 1 || $step > 8 || in_array($step, $this->loadedSteps, true)) {
            return;
        }

        match ($step) {
            1 => $this->loadPersonalFormData(),
            2 => $this->loadDocumentFormData(),
            3 => $this->loadEducationFormData(),
            4 => $this->loadLaborActivityFormData(),
            5 => $this->loadHistoryFormData(),
            6 => $this->loadAwardsFormData(),
            7 => $this->loadKinshipFormData(),
            8 => $this->loadMiscFormData(),
            default => null,
        };

        $this->loadedSteps[] = $step;
    }

    protected function loadPersonalFormData(): void
    {
        if (isset($this->personalForm)) {
            $personnel = $this->personnelModelDataInstance();
            $locale = app()->getLocale();
            $educationDegreeColumn = "title_{$locale}";
            $workNormColumn = "name_{$locale}";

            $this->ensureRelationsLoaded('personal', function () use ($educationDegreeColumn, $workNormColumn) {
                $this->personnelModelDataInstance()->loadMissing([
                    'nationality:country_id,locale,title',
                    'previousNationality:country_id,locale,title',
                    "educationDegree:id,{$educationDegreeColumn}",
                    'structure:id,name,parent_id',
                    'position:id,name',
                    'disability:id,name',
                    "workNorm:id,{$workNormColumn}",
                    'socialOrigin:id,name',
                ]);
            });

            $this->registerPersonalDropdownLabels();
            $this->personalForm->fillFromModel($personnel, false);
        }
    }

    protected function loadDocumentFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'documentForm',
            group: 'documents',
            relations: [
                'idDocuments.nationality',
                'idDocuments.bornCountry',
                'idDocuments.bornCity',
                'cards',
                'passports',
            ],
            loader: fn () => $this->documentForm->fillFromModel($this->personnelModelDataInstance())
        );
    }

    protected function loadEducationFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'educationForm',
            group: 'education',
            relations: [
                'education.educationalInstitution',
                'education.educationForm',
                'extraEducations.educationalInstitution',
                'extraEducations.educationForm',
                'extraEducations.educationType',
                'extraEducations.documentType',
            ],
            loader: function (): void {
                $this->educationForm->fillFromModel($this->personnelModelDataInstance());
                $this->recalculateEducationDurations();
            }
        );
    }

    protected function loadLaborActivityFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'laborActivityForm',
            group: 'labor',
            relations: [
                'laborActivities',
                'latestDisposal',
                'currentWork',
                'structure' => fn ($query) => $query->withRecursive('parent', false),
                'ranks',
            ],
            loader: function (): void {
                $this->laborActivityForm->fillFromModel($this->personnelModelDataInstance());
                $this->calculateSeniority();
            }
        );
    }

    protected function loadHistoryFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'historyForm',
            group: 'history',
            relations: [
                'military.rank',
                'injuries',
                'captives',
            ],
            loader: fn () => $this->historyForm->fillFromModel($this->personnelModelDataInstance())
        );
    }

    protected function loadAwardsFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'awardsPunishmentsForm',
            group: 'awards_punishments',
            relations: [
                'awards.award',
                'punishments.punishment',
            ],
            loader: fn () => $this->awardsPunishmentsForm->fillFromModel($this->personnelModelDataInstance())
        );
    }

    protected function loadKinshipFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'kinshipForm',
            group: 'kinships',
            relations: ['kinships.kinship'],
            loader: fn () => $this->kinshipForm->fillFromModel($this->personnelModelDataInstance())
        );
    }

    protected function loadMiscFormData(): void
    {
        $this->loadFormStepData(
            formProperty: 'miscForm',
            group: 'misc',
            relations: [
                'foreignLanguages.language',
                'participations',
                'degreeAndNames.degreeAndName',
                'degreeAndNames.documentType',
                'elections',
            ],
            loader: fn () => $this->miscForm->fillFromModel($this->personnelModelDataInstance())
        );
    }

    protected function ensureRelationsLoaded(string $group, callable $loader): void
    {
        if (in_array($group, $this->relationGroupsLoaded, true)) {
            return;
        }

        $loader();
        $this->relationGroupsLoaded[] = $group;
    }

    protected function loadFormStepData(string $formProperty, string $group, array $relations, callable $loader): void
    {
        if (! isset($this->{$formProperty})) {
            return;
        }

        $this->ensureRelationsLoaded($group, function () use ($relations): void {
            $this->personnelModelDataInstance()->loadMissing($relations);
        });

        $loader();
    }

    protected function resetStepTrackingFor(int $personnelId): void
    {
        if ($this->loadedPersonnelId === $personnelId) {
            return;
        }

        $this->loadedPersonnelId = $personnelId;
        $this->loadedSteps = [];
        $this->relationGroupsLoaded = [];
        $this->resetDropdownLabelCache();
    }

    protected function registerPersonalDropdownLabels(): void
    {
        $personnel = $this->personnelModelDataInstance();
        $locale = app()->getLocale();

        $this->registerDropdownLabel('countries', $personnel->nationality_id, optional($personnel->nationality)->title);
        $this->registerDropdownLabel('countries', $personnel->previous_nationality_id, optional($personnel->previousNationality)->title);
        $this->registerDropdownLabel('structures', optional($personnel->structure)->id, optional($personnel->structure)->name);
        $this->registerDropdownLabel('positions', optional($personnel->position)->id, optional($personnel->position)->name);
        $this->registerDropdownLabel('disabilities', optional($personnel->disability)->id, optional($personnel->disability)->name);
        $this->registerDropdownLabel('social_origins', optional($personnel->socialOrigin)->id, optional($personnel->socialOrigin)->name);

        $workNormLabel = optional($personnel->workNorm)->{"name_{$locale}"} ?? null;
        $this->registerDropdownLabel('work_norms', optional($personnel->workNorm)->id, $workNormLabel);

        $educationDegreeLabel = optional($personnel->educationDegree)->{"title_{$locale}"} ?? null;
        $this->registerDropdownLabel('education_degrees', optional($personnel->educationDegree)->id, $educationDegreeLabel);
    }

    protected function personnelModelDataInstance(): Personnel
    {
        $modelId = (int) $this->personnelModel;

        if ($modelId <= 0) {
            throw new ModelNotFoundException('Invalid personnel model id.');
        }

        if ($this->personnelModelData && (int) $this->personnelModelData->getKey() === $modelId) {
            return $this->personnelModelData;
        }

        $this->personnelModelData = Personnel::query()->findOrFail($modelId);

        return $this->personnelModelData;
    }
}
