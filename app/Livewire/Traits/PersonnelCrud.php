<?php

namespace App\Livewire\Traits;

use App\Enums\KnowledgeStatusEnum;
use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\Validations\PersonnelValidationTrait;
use App\Models\City;
use App\Models\CountryTranslation;
use App\Services\CalculateSeniorityService;
use App\Services\EducationDurationService;
use App\Services\CallPersonnelInfo;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Isolate;
use Livewire\WithFileUploads;

trait PersonnelCrud
{
    use DropdownConstructTrait;
    use FillComplexArrayTrait;
    use PersonnelDropdownOptions;
    use PersonnelValidationTrait;
    use NormalizesDropdownPayloads;
    use WithFileUploads;

    public $title;

    public $step;

    public array $completedSteps;

    public array $calculatedDataEducation = [];

    public array $calculatedDataExtraEducation = [];

    public array $calculatedData = [];

    public $avatar;

    public $searchNationality = '';

    public $searchPreviousNationality = '';

    public $searchEducationDegree = '';

    public $searchStructure = '';

    public $searchPosition = '';

    public $searchWorkNorm = '';

    public $searchDisability = '';

    public $searchSocialOrigin = '';

    public $searchDocumentNationality = '';

    public $searchDocumentBornCountry = '';

    public $searchDocumentCity = '';

    public $searchEducationInstitution = '';

    public $searchEducationForm = '';

    public $searchExtraEducationInstitution = '';

    public $searchExtraEducationForm = '';

    public $searchEducationType = '';

    public $searchEducationDocumentType = '';

    public $searchRank = '';

    public $searchRankReason = '';

    public $searchMilitaryRank = '';

    public $searchKinship = '';

    public $searchLanguage = '';

    public $searchDegree = '';

    public $searchDegreeDocumentType = '';

    public $searchAward = '';

    public $searchPunishment = '';

    public $isSpecialService = false;

    public $isAddedRank = false;

    protected ?CalculateSeniorityService $seniorityServiceInstance = null;

    protected ?EducationDurationService $educationDurationServiceInstance = null;

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => 'image|max:2048',
        ]);
    }

    public function previousStep()
    {
        $this->validateNavigationStepIfNeeded();

        $this->step = max(1, $this->step - 1);
        $this->handleStepChanged();
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
        $this->validateNavigationStepIfNeeded();

        $this->step = $step;
        $this->handleStepChanged();
    }

    protected function completeStep(bool $actionSave = false): void
    {
        if ($actionSave) {
            return;
        }

        $step = (int) $this->step;

        if (! $this->shouldValidateStep($step)) {
            return;
        }

        $stepName = match ($step) {
            1 => 'personnel',
            2 => 'document',
            3 => 'education'
        };

        if (! $stepName) {
            return;
        }

        $validator = $this->getValidationRulesForStep();

        if (! empty($validator)) {
            $this->validate($validator);
        }

        if (! in_array($stepName, $this->completedSteps)) {
            $this->completedSteps[] = $stepName;
        }
    }

    private function getExceptedValidationsByStep(): array
    {
        $exceptedValidations = [];

        $documentPayload = $this->documentPayload();

        $stepConditions = [
            2 => [
                'documentForm.document' => data_get($documentPayload, 'document', []),
                'documentForm.serviceCards' => data_get($documentPayload, 'service_cards.list', []),
                'documentForm.passports' => data_get($documentPayload, 'passports.list', []),
            ],
            3 => [
                'educationForm.extraEducation' => (property_exists($this, 'educationForm') && $this->educationForm)
                    ? ($this->educationForm->extraEducationList ?? [])
                    : [],
            ],
            4 => [
                'laborActivityForm.laborActivity' => property_exists($this, 'laborActivityForm')
                    ? ($this->laborActivityForm->laborActivityList ?? [])
                    : [],
            ],
            5 => [
                'historyForm.military' => property_exists($this, 'historyForm')
                    ? ($this->historyForm->militaryList ?? [])
                    : [],
                'historyForm.injury' => property_exists($this, 'historyForm')
                    ? ($this->historyForm->injuryList ?? [])
                    : [],
                'historyForm.captivity' => property_exists($this, 'historyForm')
                    ? ($this->historyForm->captivityList ?? [])
                    : [],
            ],
        ];

        foreach ($stepConditions[$this->step] ?? [] as $field => $payload) {
            $hasValue = match ($field) {
                'documentForm.document' => $this->payloadHasValues($payload),
                default => ! empty($payload),
            };

            if ($hasValue) {
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

        if (count($specialConditions) === 1) {
            return $specialConditions[0];
        }

        return array_intersect_assoc(...$specialConditions);
    }

    protected function validateNavigationStepIfNeeded(): void
    {
        if ($this->shouldSkipNavigationValidation()) {
            return;
        }

        $this->recalculateEducationDurations();
        $this->calculateSeniority();

        if (! $this->shouldValidateCurrentStep()) {
            return;
        }

        $validator = $this->getValidationRulesForStep();

        if (! empty($validator)) {
            $this->validate($validator);
        }
    }

    protected function shouldSkipNavigationValidation(): bool
    {
        return false;
    }

    protected function shouldValidateCurrentStep(): bool
    {
        return $this->shouldValidateStep((int) $this->step);
    }

    protected function shouldValidateStep(int $step): bool
    {
        return match ($step) {
            2 => $this->hasDocumentStepPayload(),
            3 => $this->hasEducationStepPayload(),
            4 => $this->hasLaborStepPayload(),
            5 => $this->historyFormHasDraft(),
            6 => $this->awardsFormHasDraft(),
            7 => $this->kinshipFormHasDraft(),
            8 => $this->miscFormHasDraft(),
            default => true,
        };
    }

    protected function hasDocumentStepPayload(): bool
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return false;
        }

        $payloads = [
            $this->documentForm->document ?? [],
            $this->documentForm->serviceCards ?? [],
            $this->documentForm->serviceCardsList ?? [],
            $this->documentForm->passports ?? [],
            $this->documentForm->passportsList ?? [],
        ];

        foreach ($payloads as $payload) {
            if ($this->payloadHasValues($payload)) {
                return true;
            }
        }

        return false;
    }

    protected function hasEducationStepPayload(): bool
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return false;
        }

        $payloads = [
            $this->educationForm->education ?? [],
            $this->educationForm->extraEducation ?? [],
            $this->educationForm->extraEducationList ?? [],
        ];

        foreach ($payloads as $payload) {
            if ($this->payloadHasValues($payload)) {
                return true;
            }
        }

        return false;
    }

    protected function hasLaborStepPayload(): bool
    {
        $payloads = [];

        if (property_exists($this, 'laborActivityForm') && $this->laborActivityForm) {
            $payloads[] = $this->laborActivityForm->laborActivity ?? [];
            $payloads[] = $this->laborActivityForm->laborActivityList ?? [];
            $payloads[] = $this->laborActivityForm->rank ?? [];
            $payloads[] = $this->laborActivityForm->rankList ?? [];
        }

        foreach ($payloads as $payload) {
            if ($this->payloadHasValues($payload)) {
                return true;
            }
        }

        return false;
    }

    protected function historyFormHasDraft(): bool
    {
        if (! property_exists($this, 'historyForm') || ! $this->historyForm) {
            return false;
        }

        $drafts = [
            $this->historyForm->military ?? [],
            $this->historyForm->injury ?? [],
            $this->historyForm->captivity ?? [],
        ];

        foreach ($drafts as $draft) {
            if ($this->payloadHasValues($draft)) {
                return true;
            }
        }

        return false;
    }

    protected function awardsFormHasDraft(): bool
    {
        if (! property_exists($this, 'awardsPunishmentsForm') || ! $this->awardsPunishmentsForm) {
            return false;
        }

        return $this->payloadHasValues($this->awardsPunishmentsForm->award ?? [])
            || $this->payloadHasValues($this->awardsPunishmentsForm->punishment ?? []);
    }

    protected function kinshipFormHasDraft(): bool
    {
        if (! property_exists($this, 'kinshipForm') || ! $this->kinshipForm) {
            return false;
        }

        return $this->payloadHasValues($this->kinshipForm->kinship ?? []);
    }

    protected function miscFormHasDraft(): bool
    {
        if (! property_exists($this, 'miscForm') || ! $this->miscForm) {
            return false;
        }

        $drafts = [
            $this->miscForm->language ?? [],
            $this->miscForm->event ?? [],
            $this->miscForm->degree ?? [],
        ];

        if ($this->miscForm->hasElectedElectorals ?? false) {
            $drafts[] = $this->miscForm->election ?? [];
        }

        foreach ($drafts as $draft) {
            if ($this->payloadHasValues($draft)) {
                return true;
            }
        }

        return false;
    }

    public function nextStep(): void
    {
        $this->isAddedRank = false;

        $this->validateNavigationStepIfNeeded();

        $this->step++;
        $this->handleStepChanged();
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
            'knowledges' => KnowledgeStatusEnum::values(),
        ];
    }

    protected function validateCommon($exclude)
    {
        $validators = array_map(fn ($field) => $this->exceptArray($field), $exclude);

        if (empty($validators)) {
            return;
        }

        $rules = count($validators) === 1
            ? $validators[0]
            : array_intersect_assoc(...$validators);

        if (empty($rules)) {
            return;
        }

        $this->validate($rules);
    }

    public function updatedDocumentForm($value, $key): void
    {
        if (is_string($key) && str_contains($key, 'born_country_id')) {
            if (property_exists($this, 'documentForm') && $this->documentForm) {
                $this->documentForm->document['born_city_id'] = null;
            }

            $this->searchDocumentCity = '';
        }
    }

    public function addServiceCard(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->validate($this->serviceCardRuleSet());

        $this->documentForm->addServiceCardEntry();
    }

    public function removeServiceCard(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->removeServiceCardEntry($key);
    }

    public function forceDeleteServiceCard(int $key): void
    {
        $this->removeServiceCard($key);
    }

    public function addPassport(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->validate($this->passportRuleSet());

        $this->documentForm->addPassportEntry();
    }

    public function removePassport(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->removePassportEntry($key);
    }

    public function forceDeletePassport(int $key): void
    {
        $this->removePassport($key);
    }

    public function getDataByPin(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $pin = trim((string) $this->documentValue('pin'));

        if ($pin === '') {
            return;
        }

        $payload = ['pin' => $pin];

        if ($pin === '1071F12') {
            $nationality = Cache::rememberForever('nationality:azerbaijan', fn () => CountryTranslation::select('country_id', 'title')
                ->where('title', 'LIKE', '%Azərbaycan%')
                ->first());

            $city = Cache::rememberForever('city:baki', fn () => City::select('id', 'name')
                ->where('name', 'LIKE', '%Bakı%')
                ->first());

            $payload = [
                'pin' => $pin,
                'nationality_id' => data_get($nationality, 'country_id'),
                'series' => 'AA',
                'number' => '052142',
                'born_country_id' => data_get($nationality, 'country_id'),
                'born_city_id' => data_get($city, 'id'),
                'birthplace' => 'Bayil',
                'registered_address' => 'Bakixanov Sakit Qocayev',
                'is_married' => true,
                'military_duty' => 'h/m',
                'blood_group' => '2+',
                'eye_color' => 'qara',
                'height' => 173,
                'document_issued_authority' => 'ASAN 2',
                'document_issued_date' => '2020-05-25',
            ];
        }

        $this->documentForm->mergeDocument($payload);
    }

    protected function documentPayload(): array
    {
        if (property_exists($this, 'documentForm') && $this->documentForm) {
            return $this->documentForm->toPayload();
        }

        return [
            'document' => [],
            'service_cards' => ['current' => [], 'list' => []],
            'passports' => ['current' => [], 'list' => []],
        ];
    }

    public function updatedEducationForm($value, $key): void
    {
        if (! is_string($key)) {
            $this->recalculateEducationDurations();

            return;
        }

        if (str_contains($key, 'education.calculate_as_seniority')) {
            $this->setEducationCoefficient((bool) $value, false);

            return;
        }

        if (str_contains($key, 'extraEducation.calculate_as_seniority')) {
            $this->setEducationCoefficient((bool) $value, true);

            return;
        }

        $this->recalculateEducationDurations();
    }

    public function updatedLaborActivityForm($value, $key): void
    {
        if (is_string($key)
            && ! str_contains($key, 'laborActivityList')
            && ! str_contains($key, 'laborActivityForm.laborActivity.')
        ) {
            return;
        }

        $this->calculateSeniority();
    }

    public function updatedEducationFormHasExtraEducation($value): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $value = (bool) $value;

        if ($value) {
            $this->educationForm->hasExtraEducation = true;
        } else {
            $this->educationForm->disableExtraEducation();
        }

        $this->recalculateEducationDurations();
    }

    protected function setEducationCoefficient(bool $enabled, bool $forExtra): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $field = $forExtra ? 'extraEducation' : 'education';
        $this->educationForm->{$field}['coefficient'] = $enabled
            ? $this->educationCoefficientValue()
            : null;

        $this->recalculateEducationDurations();
    }

    protected function educationCoefficientValue(): ?float
    {
        $settings = cache('settings');

        return isset($settings['Education coefficient'])
            ? (float) $settings['Education coefficient']
            : null;
    }

    protected function recalculateEducationDurations(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            $this->calculatedDataEducation = [];
            $this->calculatedDataExtraEducation = [];

            return;
        }

        $education = $this->educationForm->education ?? [];
        $extraEducationList = $this->educationForm->extraEducationList ?? [];
        $service = $this->educationDurationService();

        $this->calculatedDataEducation = $service->education($education);
        $this->calculatedDataExtraEducation = $service->extraEducations($extraEducationList);
    }

    public function addExtraEducation(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->validate($this->extraEducationRuleSet());

        $this->educationForm->addExtraEducationEntry(
            $this->educationCoefficientValue()
        );

        $this->recalculateEducationDurations();
    }

    public function removeExtraEducation(int $key): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->educationForm->removeExtraEducationEntry($key);

        $this->recalculateEducationDurations();
    }

    public function addLaborActivity(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->validate($this->laborActivityRuleSet());

        $this->laborActivityForm->addLaborActivityEntry((bool) $this->isSpecialService);

        $this->calculateSeniority();
    }

    public function forceDeleteLaborActivity(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->removeLaborActivityEntry($key);

        $this->calculateSeniority();
    }

    public function addRank(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->isAddedRank = true;
        $this->validate($this->rankRuleSet());

        $this->laborActivityForm->addRankEntry();
        $this->isAddedRank = false;
    }

    public function forceDeleteRank(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->removeRankEntry($key);
    }

    protected function calculateSeniority(?array $list = null): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            $this->calculatedData = [];

            return;
        }

        $list ??= $this->laborActivitiesWithDraft();

        if (empty($list)) {
            $this->calculatedData = [];

            return;
        }

        $this->calculatedData = $this->seniorityService()->calculateMulti($list);
    }

    protected function laborActivitiesWithDraft(): array
    {
        $list = $this->laborActivityForm->laborActivityList ?? [];

        if ($this->laborActivityDraftHasValues()) {
            $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time']);
            $list[] = $draft;
        }

        return $list;
    }

    protected function laborActivityDraftHasValues(): bool
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return false;
        }

        $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time']);

        return $this->payloadHasValues($draft);
    }

    public function addMilitary(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.injury'),
            $this->exceptArray('historyForm.captivity'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addMilitaryEntry();
    }

    public function forceDeleteMilitary(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeMilitaryEntry($key);
    }

    public function addInjury(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.military'),
            $this->exceptArray('historyForm.captivity'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addInjuryEntry();
    }

    public function forceDeleteInjury(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeInjuryEntry($key);
    }

    public function addCaptivity(): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->intersectValidators([
            $this->exceptArray('historyForm.military'),
            $this->exceptArray('historyForm.injury'),
        ]);

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addCaptivityEntry();
    }

    public function forceDeleteCaptivity(int $key): void
    {
        $form = $this->historyFormInstance();

        if (! $form) {
            return;
        }

        $form->removeCaptivityEntry($key);
    }

    public function addAward(): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->awardRuleSet();

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addAwardEntry();
    }

    public function forceDeleteAward(int $key): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $form->removeAwardEntry($key);
    }

    public function addPunishment(): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $rules = $this->punishmentRuleSet();

        if (! empty($rules)) {
            $this->validate($rules);
        }

        $form->addPunishmentEntry();
    }

    public function forceDeletePunishment(int $key): void
    {
        $form = $this->awardsPunishmentsFormInstance();

        if (! $form) {
            return;
        }

        $form->removePunishmentEntry($key);
    }

    public function addKinship(): void
    {
        $form = $this->kinshipFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->getKinshipRules());

        $form->addKinshipEntry(
            $this->kinshipLabel(data_get($form->kinship, 'kinship_id'))
        );
    }

    public function forceDeleteKinship(int $key): void
    {
        $form = $this->kinshipFormInstance();

        if (! $form) {
            return;
        }

        $form->removeKinshipEntry($key);
    }

    public function addLanguage(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->languageRuleSet());

        $form->addLanguageEntry(
            $this->languageLabel(data_get($form->language, 'language_id'))
        );
    }

    public function forceDeleteLanguage(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeLanguageEntry($key);
    }

    public function addEvent(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->eventRuleSet());

        $form->addEventEntry();
    }

    public function forceDeleteEvent(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeEventEntry($key);
    }

    public function addDegree(): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $this->validate($this->degreeRuleSet());

        $form->addDegreeEntry(
            $this->scientificDegreeLabel(data_get($form->degree, 'degree_and_name_id')),
            $this->educationDocumentLabel(data_get($form->degree, 'edu_doc_type_id'))
        );
    }

    public function forceDeleteDegree(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeDegreeEntry($key);
    }

    public function addElection(): void
    {
        $form = $this->miscFormInstance();

        $this->validate($this->electionRuleSet());

        $form->addElectionEntry();
    }

    public function forceDeleteElection(int $key): void
    {
        $form = $this->miscFormInstance();

        if (! $form) {
            return;
        }

        $form->removeElectionEntry($key);
    }


    protected function seniorityService(): CalculateSeniorityService
    {
        return $this->seniorityServiceInstance
            ??= resolve(CalculateSeniorityService::class);
    }

    protected function educationDurationService(): EducationDurationService
    {
        return $this->educationDurationServiceInstance
            ??= resolve(EducationDurationService::class);
    }

    public function render()
    {
        $steps = ['steps' => $this->getSteps()];

        $view_data = $this->shouldLoadLookupData()
            ? resolve(CallPersonnelInfo::class)->getAll($this->personalFormHasDisability(), $this)
            : [];

        $view_name = ! empty($this->personnelModel)
                    ? 'personnel::livewire.personnel.edit-personnel'
                    : 'personnel::livewire.personnel.add-personnel';

        return view($view_name, array_merge($steps, array_merge($view_data, $this->isolated)));
    }

    protected function shouldLoadLookupData(): bool
    {
        $step = $this->step;

        if (is_null($step) || (is_numeric($step) && (int) $step <= 0)) {
            return false;
        }

        return ! in_array((int) $step, [1, 2, 3, 4, 5, 6, 7, 8], true);
    }

    protected function handleStepChanged(): void
    {
        if (method_exists($this, 'onStepChanged')) {
            $this->onStepChanged((int) $this->step);
        }
    }

    protected function personalFormHasDisability(): bool
    {
        return property_exists($this, 'personalForm')
            && $this->personalForm
            && (bool) $this->personalForm->hasDisability;
    }

    protected function payloadHasValues($data): bool
    {
        if (is_null($data)) {
            return false;
        }

        if (! is_array($data)) {
            return $this->isScalarValueFilled($data);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->payloadHasValues($value)) {
                    return true;
                }

                continue;
            }

            if ($this->isScalarValueFilled($value)) {
                return true;
            }
        }

        return false;
    }

    protected function isScalarValueFilled($value): bool
    {
        if (is_bool($value)) {
            return $value === true;
        }

        return $value !== null && $value !== '';
    }

    protected function awardsPunishmentsFormInstance(): ?AwardsPunishmentsForm
    {
        if (property_exists($this, 'awardsPunishmentsForm') && $this->awardsPunishmentsForm instanceof AwardsPunishmentsForm) {
            return $this->awardsPunishmentsForm;
        }

        return null;
    }

    protected function kinshipFormInstance(): ?KinshipForm
    {
        if (property_exists($this, 'kinshipForm') && $this->kinshipForm instanceof KinshipForm) {
            return $this->kinshipForm;
        }

        return null;
    }

    protected function miscFormInstance(): ?MiscellaneousForm
    {
        if (property_exists($this, 'miscForm') && $this->miscForm instanceof MiscellaneousForm) {
            return $this->miscForm;
        }

        return null;
    }

    protected function historyFormInstance(): ?ServiceHistoryForm
    {
      if (property_exists($this, 'historyForm') && $this->historyForm instanceof ServiceHistoryForm) {
            return $this->historyForm;
      }

        return null;
    }

    /**
     * @param  array<int, array>  $validators
     */
    protected function intersectValidators(array $validators): array
    {
        $filtered = array_values(array_filter($validators, fn ($rules) => ! empty($rules)));

        if (empty($filtered)) {
            return [];
        }

        return array_reduce(
            $filtered,
            fn ($carry, $rules) => $carry === null ? $rules : array_intersect_assoc($carry, $rules),
            null
        ) ?? [];
    }
}
