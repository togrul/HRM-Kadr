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
use App\Livewire\Traits\PersonnelCrud;
use App\Livewire\Traits\RelationCruds\RelationCrudTrait;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

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

    public $personnelModelData;

    public $personnelModel;

    /** @var array<int> */
    public array $loadedSteps = [];

    public bool $loadedAllSteps = false;

    public ?int $loadedPersonnelId = null;

    /** @var array<string> */
    protected array $relationGroupsLoaded = [];

    public function mount()
    {
        $this->personnelModelData = Personnel::query()
            ->findOrFail($this->personnelModel);

        $this->authorize('update', $this->personnelModelData);
        $this->title = __('Edit personnel');
        $this->step = 1;
        $this->resetStepTrackingFor($this->personnelModelData->getKey());
        $this->loadStepData(1);
    }

    public function confirmPersonnel(): void
    {
        $this->personnelModelData->update(['is_pending' => false]);
        $this->dispatch('addError', __('Personnel was updated successfully!'));
    }

    public function store()
    {
        $this->authorize('update', $this->personnelModelData);
        $currentStep = (int) $this->step;

        $this->loadAllStepData();

        if ($currentStep === 1) {
            $this->validate($this->validationRules()[1]);
        } elseif ($currentStep === 2 && $this->shouldValidateStep(2)) {
            $rules = $this->validationRules()[2] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        } elseif ($currentStep === 3 && $this->shouldValidateStep(3)) {
            $rules = $this->validationRules()[3] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        } elseif ($currentStep === 4 && $this->shouldValidateStep(4)) {
            $rules = $this->validationRules()[4] ?? [];
            if ($rules) {
                $this->validate($rules);
            }
        }

        if (! empty($this->avatar)) {
            $this->personalForm->personnel['photo'] = $this->avatar->store('personnel', 'public');
        }

        $personalPayload = $this->personalForm->toPayload();
        $personnelData = $this->modifyArray(
            $personalPayload['personnel'] ?? [],
            $this->personnelModelData->dateList()
        );
        $personnelExtra = $personalPayload['personnel_extra'] ?? [];

        $documentPayload = $this->documentPayload();
        $documentData = data_get($documentPayload, 'document', []);
        $serviceCardsList = data_get($documentPayload, 'service_cards.list', []);
        $passportsList = data_get($documentPayload, 'passports.list', []);
        $education = $this->educationForm->educationForPersistence();
        $extraEducations = $this->educationForm->extraEducationsForPersistence();

        if (in_array($this->step, [2, 3, 4], true)) {
            $this->completeStep(actionSave: true);
        }

        $laborActivities = $this->laborActivityForm->laborActivitiesForPersistence();
        $ranks = $this->laborActivityForm->ranksForPersistence();

        $militaryServices = $this->historyForm->militaryForPersistence();
        $injuries = $this->historyForm->injuriesForPersistence();
        $captivities = $this->historyForm->captivitiesForPersistence();
        $awards = $this->awardsPunishmentsForm->awardsForPersistence();
        $punishments = $this->awardsPunishmentsForm->punishmentsForPersistence();
        $kinships = $this->kinshipForm->kinshipsForPersistence();
        $languages = $this->miscForm->languagesForPersistence();
        $events = $this->miscForm->eventsForPersistence();
        $degrees = $this->miscForm->degreesForPersistence();
        $elections = $this->miscForm->electionsForPersistence();

        $relationPayloads = [
            'document' => $documentData,
            'service_cards' => $serviceCardsList,
            'passports' => $passportsList,
            'education' => $education,
            'extra_educations' => $extraEducations,
            'labor_activities' => $laborActivities,
            'ranks' => $ranks,
            'military' => $militaryServices,
            'injuries' => $injuries,
            'captivities' => $captivities,
            'awards' => $awards,
            'punishments' => $punishments,
            'kinships' => $kinships,
            'languages' => $languages,
            'events' => $events,
            'degrees' => $degrees,
            'elections' => $elections,
        ];

        DB::transaction(function () use ($personnelData, $relationPayloads, $personnelExtra) {
            $this->personnelModelData->update($personnelData);
            $this->updatePersonnelRelations($relationPayloads);

            if (! empty($personnelExtra)) {
                $this->personnelModelData->update($personnelExtra);
            }
        });

        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }

    public function updatedStep($value): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $this->loadStepData((int) $value);
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

    protected function loadAllStepData(): void
    {
        if ($this->loadedAllSteps) {
            return;
        }

        foreach (range(1, 8) as $step) {
            if (! in_array($step, $this->loadedSteps, true)) {
                $this->loadStepData($step);
            }
        }

        $this->loadedAllSteps = true;
    }

    protected function loadPersonalFormData(): void
    {
        if (isset($this->personalForm)) {
            $this->ensureRelationsLoaded('personal', function () {
                $this->personnelModelData->loadMissing([
                    'nationality',
                    'previousNationality',
                    'educationDegree',
                    'structure',
                    'position',
                    'disability',
                    'workNorm',
                    'socialOrigin',
                ]);
            });

            $this->registerPersonalDropdownLabels();
            $this->personalForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function loadDocumentFormData(): void
    {
        if (isset($this->documentForm)) {
            $this->ensureRelationsLoaded('documents', function () {
                $this->personnelModelData->loadMissing([
                    'idDocuments.nationality',
                    'idDocuments.bornCountry',
                    'idDocuments.bornCity',
                    'cards',
                    'passports',
                ]);
            });

            $this->documentForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function loadEducationFormData(): void
    {
        if (isset($this->educationForm)) {
            $this->ensureRelationsLoaded('education', function () {
                $this->personnelModelData->loadMissing([
                    'education.educationalInstitution',
                    'education.educationForm',
                    'extraEducations.educationalInstitution',
                    'extraEducations.educationForm',
                    'extraEducations.educationType',
                    'extraEducations.documentType',
                ]);
            });

            $this->educationForm->fillFromModel($this->personnelModelData);
            $this->recalculateEducationDurations();
        }
    }

    protected function loadLaborActivityFormData(): void
    {
        if (isset($this->laborActivityForm)) {
            $this->ensureRelationsLoaded('labor', function () {
                $this->personnelModelData->loadMissing([
                    'laborActivities',
                    'ranks',
                ]);
            });

            $this->laborActivityForm->fillFromModel($this->personnelModelData);
            $this->calculateSeniority();
        }
    }

    protected function loadHistoryFormData(): void
    {
        if (isset($this->historyForm)) {
            $this->ensureRelationsLoaded('history', function () {
                $this->personnelModelData->loadMissing([
                    'military',
                    'participations',
                ]);
            });

            $this->historyForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function loadAwardsFormData(): void
    {
        if (isset($this->awardsPunishmentsForm)) {
            $this->ensureRelationsLoaded('awards_punishments', function () {
                $this->personnelModelData->loadMissing([
                    'awards',
                    'punishments',
                ]);
            });

            $this->awardsPunishmentsForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function loadKinshipFormData(): void
    {
        if (isset($this->kinshipForm)) {
            $this->ensureRelationsLoaded('kinships', function () {
                $this->personnelModelData->loadMissing('kinships');
            });

            $this->kinshipForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function loadMiscFormData(): void
    {
        if (isset($this->miscForm)) {
            $this->ensureRelationsLoaded('misc', function () {
                $this->personnelModelData->loadMissing([
                    'foreignLanguages',
                    'degreeAndNames',
                ]);
            });

            $this->miscForm->fillFromModel($this->personnelModelData);
        }
    }

    protected function ensureRelationsLoaded(string $group, callable $loader): void
    {
        if (in_array($group, $this->relationGroupsLoaded, true)) {
            return;
        }

        $loader();
        $this->relationGroupsLoaded[] = $group;
    }

    protected function resetStepTrackingFor(int $personnelId): void
    {
        if ($this->loadedPersonnelId === $personnelId) {
            return;
        }

        $this->loadedPersonnelId = $personnelId;
        $this->loadedSteps = [];
        $this->loadedAllSteps = false;
        $this->relationGroupsLoaded = [];
        $this->resetDropdownLabelCache();
    }

    protected function registerPersonalDropdownLabels(): void
    {
        $personnel = $this->personnelModelData;
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
}
