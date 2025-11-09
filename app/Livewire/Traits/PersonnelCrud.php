<?php

namespace App\Livewire\Traits;

use App\Enums\KnowledgeStatusEnum;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\Validations\PersonnelValidationTrait;
use App\Models\City;
use App\Models\Country;
use App\Models\CountryTranslation;
use App\Models\Disability;
use App\Models\EducationDocumentType;
use App\Models\EducationDegree;
use App\Models\EducationForm as EducationFormModel;
use App\Models\EducationType;
use App\Models\EducationalInstitution;
use App\Models\Language;
use App\Models\Position;
use App\Models\Rank;
use App\Models\RankReason;
use App\Models\ScientificDegreeAndName;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\WorkNorm;
use App\Services\CalculateSeniorityService;
use App\Services\CallPersonnelInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\WithFileUploads;

trait PersonnelCrud
{
    use DropdownConstructTrait;
    use FillComplexArrayTrait;
    use PersonnelValidationTrait;
    use SelectListTrait;
    use Step5Trait;
    use Step6Trait;
    use Step7Trait;
    use Step8Trait;
    use WithFileUploads;

    public $title;

    public $step;

    public array $completedSteps;

    public array $personnel = [];

    public array $document = [];

    public array $service_cards = [];

    public array $service_cards_list = [];

    public array $passports = [];

    public array $passports_list = [];

    public array $education = [];

    public array $extra_education = [];

    public array $extra_education_list = [];

    public bool $hasExtraEducation = false;

    public array $calculatedDataEducation = [];

    public array $calculatedDataExtraEducation = [];

    public array $calculatedData = [];

    public $avatar;

    public $isDisability = false;

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

    public $isSpecialService = false;

    public $isAddedRank = false;

    protected int $dropdownCacheMinutes = 10;

    protected array $rankLabelCache = [];

    protected array $rankReasonLabelCache = [];

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

        $stepConditions = [
            2 => [
                'documentForm.document' => $this->document ?? [],
                'documentForm.serviceCards' => $this->service_cards_list ?? [],
                'documentForm.passports' => $this->passports_list ?? [],
            ],
            3 => ['educationForm.extraEducation' => $this->extra_education_list ?? []],
            4 => [
                'laborActivityForm.laborActivity' => property_exists($this, 'laborActivityForm')
                    ? ($this->laborActivityForm->laborActivityList ?? [])
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

        $this->syncArraysFromPersonalForm();
        $this->syncArraysFromDocumentForm();
        $this->syncArraysFromEducationForm();

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
        return in_array((int) $this->step, [5, 6, 7], true);
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
            default => true,
        };
    }

    protected function hasDocumentStepPayload(): bool
    {
        $payloads = [
            $this->document ?? [],
            $this->service_cards ?? [],
            $this->service_cards_list ?? [],
            $this->passports ?? [],
            $this->passports_list ?? [],
        ];

        if (property_exists($this, 'documentForm') && $this->documentForm) {
            $payloads[] = $this->documentForm->document ?? [];
            $payloads[] = $this->documentForm->serviceCards ?? [];
            $payloads[] = $this->documentForm->serviceCardsList ?? [];
            $payloads[] = $this->documentForm->passports ?? [];
            $payloads[] = $this->documentForm->passportsList ?? [];
        }

        foreach ($payloads as $payload) {
            if ($this->payloadHasValues($payload)) {
                return true;
            }
        }

        return false;
    }

    protected function hasEducationStepPayload(): bool
    {
        $payloads = [
            $this->education ?? [],
            $this->extra_education ?? [],
            $this->extra_education_list ?? [],
        ];

        if (property_exists($this, 'educationForm') && $this->educationForm) {
            $payloads[] = $this->educationForm->education ?? [];
            $payloads[] = $this->educationForm->extraEducation ?? [];
            $payloads[] = $this->educationForm->extraEducationList ?? [];
        }

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

    public function nextStep(): void
    {
        $this->isAddedRank = false;

        $this->validateNavigationStepIfNeeded();

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

    protected function hydratePersonalFormFromArrays(): void
    {
        if (! property_exists($this, 'personalForm') || ! $this->personalForm) {
            return;
        }

        $this->personalForm->personnel = array_replace_recursive(
            $this->personalForm->personnel ?? [],
            $this->personnel ?? []
        );

        $this->personalForm->personnelExtra = array_replace_recursive(
            $this->personalForm->personnelExtra ?? [],
            $this->personnel_extra ?? []
        );

        $this->personalForm->hasDisability = (bool) ($this->isDisability ?? false);
        $this->personalForm->avatarPath = $this->personalForm->avatarPath ?? null;
    }

    protected function syncArraysFromPersonalForm(): void
    {
        if (! property_exists($this, 'personalForm') || ! $this->personalForm) {
            return;
        }

        $this->personnel = $this->personalForm->personnel;
        $this->personnel_extra = $this->personalForm->personnelExtra;
        $this->isDisability = $this->personalForm->hasDisability;
    }

    public function updatedPersonalForm($value, $key): void
    {
        $this->syncArraysFromPersonalForm();
    }

    protected function hydrateDocumentFormFromArrays(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->fillFromArrays(
            document: $this->document ?? [],
            serviceCards: $this->service_cards ?? [],
            serviceCardList: $this->service_cards_list ?? [],
            passports: $this->passports ?? [],
            passportList: $this->passports_list ?? []
        );
    }

    protected function syncArraysFromDocumentForm(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->document = $this->documentForm->document;
        $this->service_cards = $this->documentForm->serviceCards;
        $this->service_cards_list = $this->documentForm->serviceCardsList;
        $this->passports = $this->documentForm->passports;
        $this->passports_list = $this->documentForm->passportsList;
    }

    public function updatedDocumentForm($value, $key): void
    {
        $this->syncArraysFromDocumentForm();

        if (is_string($key) && str_contains($key, 'born_country_id')) {
            if (property_exists($this, 'documentForm') && $this->documentForm) {
                $this->documentForm->document['born_city_id'] = null;
            }

            $this->document['born_city_id'] = null;
            $this->searchDocumentCity = '';
        }
    }

    public function addServiceCard(): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->syncArraysFromDocumentForm();
        $this->validate($this->serviceCardRuleSet());

        $this->documentForm->serviceCardsList[] = $this->documentForm->serviceCards;
        $this->documentForm->resetServiceCard();

        $this->syncArraysFromDocumentForm();
    }

    public function removeServiceCard(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        unset($this->documentForm->serviceCardsList[$key]);
        $this->documentForm->serviceCardsList = array_values($this->documentForm->serviceCardsList);

        $this->syncArraysFromDocumentForm();
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

        $this->syncArraysFromDocumentForm();
        $this->validate($this->passportRuleSet());

        $this->documentForm->passportsList[] = $this->documentForm->passports;
        $this->documentForm->resetPassport();

        $this->syncArraysFromDocumentForm();
    }

    public function removePassport(int $key): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        unset($this->documentForm->passportsList[$key]);
        $this->documentForm->passportsList = array_values($this->documentForm->passportsList);

        $this->syncArraysFromDocumentForm();
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
        $this->syncArraysFromDocumentForm();
    }

    protected function hydrateEducationFormFromArrays(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->educationForm->fillFromArrays(
            education: $this->education ?? [],
            extraEducation: $this->extra_education ?? [],
            extraEducationList: $this->extra_education_list ?? [],
            hasExtraEducation: (bool) ($this->hasExtraEducation ?? false)
        );
    }

    protected function syncArraysFromEducationForm(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->education = $this->educationForm->education;
        $this->extra_education = $this->educationForm->extraEducation;
        $this->extra_education_list = $this->educationForm->extraEducationList;
        $this->hasExtraEducation = $this->educationForm->hasExtraEducation;

        $this->recalculateEducationDurations();
    }

    public function updatedEducationForm($value, $key): void
    {
        if (! is_string($key)) {
            $this->syncArraysFromEducationForm();

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

        $this->syncArraysFromEducationForm();
    }

    protected function hydrateLaborActivityFormFromArrays(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->fillFromArrays(
            laborActivity: [],
            laborActivityList: [],
            rank: [],
            rankList: []
        );
    }

    protected function syncArraysFromLaborActivityForm(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->calculateSeniority();
    }

    public function updatedLaborActivityForm($value, $key): void
    {
        $this->syncArraysFromLaborActivityForm();
    }

    public function updatedHasExtraEducation($value): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            $this->hasExtraEducation = (bool) $value;

            return;
        }

        $this->educationForm->hasExtraEducation = (bool) $value;

        if (! $value) {
            $this->educationForm->extraEducationList = [];
            $this->educationForm->resetExtraEducation();
        }

        $this->syncArraysFromEducationForm();
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

        $this->syncArraysFromEducationForm();
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
        $service = resolve(CalculateSeniorityService::class);

        $this->calculatedDataEducation = $this->shouldCalculateEducationDuration($this->education)
            ? $service->calculateEducation($this->education)
            : [];

        $this->calculatedDataExtraEducation = ! empty($this->extra_education_list)
            ? $service->calculateMultiEducation($this->extra_education_list)
            : [];
    }

    protected function shouldCalculateEducationDuration(array $education): bool
    {
        return ! empty($education['admission_year']);
    }

    public function addExtraEducation(): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->educationForm->hasExtraEducation = true;
        $this->hasExtraEducation = true;

        $this->validate($this->extraEducationRuleSet());

        $entry = $this->educationForm->extraEducation;
        $entry['calculate_as_seniority'] = (bool) ($entry['calculate_as_seniority'] ?? false);
        $entry['is_military'] = (bool) ($entry['is_military'] ?? false);
        $entry['coefficient'] = $entry['calculate_as_seniority']
            ? $this->educationCoefficientValue()
            : null;

        $this->educationForm->extraEducationList[] = $entry;
        $this->educationForm->resetExtraEducation();

        $this->syncArraysFromEducationForm();
    }

    public function removeExtraEducation(int $key): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        unset($this->educationForm->extraEducationList[$key]);
        $this->educationForm->extraEducationList = array_values($this->educationForm->extraEducationList);

        $this->syncArraysFromEducationForm();
    }

    public function addLaborActivity(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->syncArraysFromLaborActivityForm();
        $this->validate($this->laborActivityRuleSet());

        $entry = $this->laborActivityForm->laborActivity;
        $entry['is_special_service'] = $this->isSpecialService ? 1 : 0;
        $entry['is_current'] = (bool) ($entry['is_current'] ?? false);

        if ($this->isSpecialService) {
            $time = $entry['time'] ?? '12:00';
            $orderDate = $entry['order_date'] ?? '';
            $entry['order_date'] = trim("{$orderDate} {$time}");
        } else {
            $entry['order_given_by'] = null;
            $entry['order_no'] = null;
            $entry['order_date'] = null;
        }

        unset($entry['time']);

        $this->laborActivityForm->laborActivityList[] = $entry;
        $this->laborActivityForm->resetLaborActivity();

        $this->syncArraysFromLaborActivityForm();
    }

    public function forceDeleteLaborActivity(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        unset($this->laborActivityForm->laborActivityList[$key]);
        $this->laborActivityForm->laborActivityList = array_values($this->laborActivityForm->laborActivityList);

        $this->syncArraysFromLaborActivityForm();
    }

    public function addRank(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->isAddedRank = true;
        $this->syncArraysFromLaborActivityForm();
        $this->validate($this->rankRuleSet());

        $entry = $this->laborActivityForm->rank;
        $this->laborActivityForm->rankList[] = $entry;
        $this->laborActivityForm->resetRank();
        $this->isAddedRank = false;

        $this->syncArraysFromLaborActivityForm();
    }

    public function forceDeleteRank(int $key): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        unset($this->laborActivityForm->rankList[$key]);
        $this->laborActivityForm->rankList = array_values($this->laborActivityForm->rankList);

        $this->syncArraysFromLaborActivityForm();
    }

    protected function calculateSeniority(): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            $this->calculatedData = [];

            return;
        }

        $list = $this->laborActivityForm->laborActivityList ?? [];

        if (empty($list)) {
            $this->calculatedData = [];

            return;
        }

        $calculateService = resolve(CalculateSeniorityService::class);
        $this->calculatedData = $calculateService->calculateMulti($list);
    }

    #[Computed]
    public function nationalityOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchNationality'),
            selectedId: $this->dropdownSelected('nationality_id')
        );
    }

    #[Computed]
    public function previousNationalityOptions(): array
    {
        $search = $this->dropdownSearch('searchPreviousNationality');

        return $this->countryOptions(
            searchTerm: $search,
            selectedId: $this->dropdownSelected('previous_nationality_id'),
            cacheKeySuffix: 'previous'
        );
    }

    #[Computed]
    public function documentNationalityOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchDocumentNationality'),
            selectedId: $this->documentValue('nationality_id'),
            cacheKeySuffix: 'document-nationality'
        );
    }

    #[Computed]
    public function documentBornCountryOptions(): array
    {
        return $this->countryOptions(
            searchTerm: $this->dropdownSearch('searchDocumentBornCountry'),
            selectedId: $this->documentValue('born_country_id'),
            cacheKeySuffix: 'document-born-country'
        );
    }

    #[Computed]
    public function documentCityOptions(): array
    {
        $base = City::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($countryId = $this->documentValue('born_country_id')) {
            $base->where('country_id', $countryId);
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchDocumentCity'),
            selectedId: $this->documentValue('born_city_id'),
            limit: 80
        );
    }

    #[Computed]
    public function educationInstitutionOptions(): array
    {
        return $this->educationInstitutionOptionsFor(
            search: $this->dropdownSearch('searchEducationInstitution'),
            selectedId: $this->educationSelected('educational_institution_id'),
            cacheSuffix: 'primary'
        );
    }

    #[Computed]
    public function extraEducationInstitutionOptions(): array
    {
        return $this->educationInstitutionOptionsFor(
            search: $this->dropdownSearch('searchExtraEducationInstitution'),
            selectedId: $this->educationSelected('educational_institution_id', true),
            cacheSuffix: 'extra'
        );
    }

    protected function educationInstitutionOptionsFor(string $search, $selectedId, string $cacheSuffix): array
    {
        $base = EducationalInstitution::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education:institutions:{$cacheSuffix}",
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

    #[Computed]
    public function educationFormOptions(): array
    {
        return $this->educationFormOptionsFor(
            search: $this->dropdownSearch('searchEducationForm'),
            selectedId: $this->educationSelected('education_form_id'),
            cacheSuffix: 'primary'
        );
    }

    #[Computed]
    public function extraEducationFormOptions(): array
    {
        return $this->educationFormOptionsFor(
            search: $this->dropdownSearch('searchExtraEducationForm'),
            selectedId: $this->educationSelected('education_form_id', true),
            cacheSuffix: 'extra'
        );
    }

    protected function educationFormOptionsFor(string $search, $selectedId, string $cacheSuffix): array
    {
        $locale = app()->getLocale();
        $labelColumn = "name_{$locale}";

        $base = EducationFormModel::query()
            ->select('id', DB::raw("$labelColumn as label"))
            ->orderBy($labelColumn);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education:forms:{$cacheSuffix}:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    #[Computed]
    public function educationTypeOptions(): array
    {
        $base = EducationType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($this->dropdownSearch('searchEducationType') === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:education:types',
                base: $base,
                selectedId: $this->educationSelected('education_type_id', true),
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchEducationType'),
            selectedId: $this->educationSelected('education_type_id', true),
            limit: 60
        );
    }

    #[Computed]
    public function educationDocumentTypeOptions(): array
    {
        $base = EducationDocumentType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($this->dropdownSearch('searchEducationDocumentType') === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:education:document_types',
                base: $base,
                selectedId: $this->educationSelected('education_document_type_id', true),
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchEducationDocumentType'),
            selectedId: $this->educationSelected('education_document_type_id', true),
            limit: 60
        );
    }

    #[Computed]
    public function rankOptions(): array
    {
        return $this->rankOptionsFor(
            search: $this->dropdownSearch('searchRank'),
            selectedId: $this->currentRankSelection('rank_id')
        );
    }

    #[Computed]
    public function rankReasonOptions(): array
    {
        return $this->rankReasonOptionsFor(
            search: $this->dropdownSearch('searchRankReason'),
            selectedId: $this->currentRankSelection('rank_reason_id')
        );
    }

    protected function rankOptionsFor(string $search, $selectedId): array
    {
        $locale = app()->getLocale();
        $labelColumn = "name_{$locale}";

        $base = Rank::query()
            ->select('id', DB::raw("$labelColumn as label"))
            ->where('is_active', true)
            ->orderBy($labelColumn);

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:ranks:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function rankReasonOptionsFor(string $search, $selectedId): array
    {
        $base = RankReason::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:rank_reasons',
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
    #[Computed]
    public function structureOptions(): array
    {
        $base = Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->orderBy('level')
            ->orderBy('code');

        $search = $this->dropdownSearch('searchStructure');
        $selected = $this->dropdownSelected('structure_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:structures',
                base: $base,
                selectedId: $selected,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 80
        );
    }

    #[Computed]
    public function positionOptions(): array
    {
        $base = Position::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch('searchPosition');
        $selected = $this->dropdownSelected('position_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:positions',
                base: $base,
                selectedId: $selected,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 80
        );
    }

    #[Computed]
    public function educationDegreeOptions(): array
    {
        $locale = app()->getLocale();
        $labelColumn = 'title_'.$locale;

        $base = EducationDegree::query()
            ->select('id', DB::raw("$labelColumn as label"));

        $search = $this->dropdownSearch('searchEducationDegree');
        $selected = $this->dropdownSelected('education_degree_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:education_degree:{$locale}",
                base: $base,
                selectedId: $selected,
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 60
        );
    }

    #[Computed]
    public function workNormOptions(): array
    {
        $locale = app()->getLocale();
        $labelColumn = 'name_'.$locale;

        $base = WorkNorm::query()
            ->select('id', DB::raw("$labelColumn as label"));

        $search = $this->dropdownSearch('searchWorkNorm');
        $selected = $this->dropdownSelected('work_norm_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:work_norms:{$locale}",
                base: $base,
                selectedId: $selected,
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function socialOriginOptions(): array
    {
        $base = SocialOrigin::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch('searchSocialOrigin');
        $selected = $this->dropdownSelected('social_origin_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:social_origin',
                base: $base,
                selectedId: $selected,
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function disabilityOptions(): array
    {
        if (! $this->isDisabilityEnabled()) {
            return [];
        }

        $base = Disability::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        $search = $this->dropdownSearch('searchDisability');
        $selected = $this->dropdownSelected('disability_id');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'personnel:disabilities',
                base: $base,
                selectedId: $selected,
                limit: 50
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    protected function dropdownSelected(string $key): int|string|null
    {
        if (property_exists($this, 'personalForm') && $this->personalForm) {
            return data_get($this->personalForm->personnel, $key);
        }

        return data_get($this->personnel ?? [], $key);
    }

    protected function educationSelected(string $key, bool $isExtra = false): int|string|null
    {
        if (property_exists($this, 'educationForm') && $this->educationForm) {
            $source = $isExtra ? $this->educationForm->extraEducation : $this->educationForm->education;

            return data_get($source ?? [], $key);
        }

        $source = $isExtra ? ($this->extra_education ?? []) : ($this->education ?? []);

        return data_get($source, $key);
    }

    protected function currentRankSelection(string $path)
    {
        if (property_exists($this, 'laborActivityForm') && $this->laborActivityForm) {
            return data_get($this->laborActivityForm->rank ?? [], $path);
        }

        return null;
    }

    public function rankLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        $locale = app()->getLocale();
        $cacheKey = "{$locale}:{$id}";

        if (! array_key_exists($cacheKey, $this->rankLabelCache)) {
            $column = "name_{$locale}";
            $this->rankLabelCache[$cacheKey] = Rank::query()->whereKey($id)->value($column);
        }

        return $this->rankLabelCache[$cacheKey];
    }

    public function rankReasonLabel($id): ?string
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, $this->rankReasonLabelCache)) {
            $this->rankReasonLabelCache[$id] = RankReason::query()->whereKey($id)->value('name');
        }

        return $this->rankReasonLabelCache[$id];
    }

    protected function documentValue(string $key): mixed
    {
        if (property_exists($this, 'documentForm') && $this->documentForm) {
            return data_get($this->documentForm->document, $key);
        }

        return data_get($this->document ?? [], $key);
    }

    protected function dropdownSearch(string $property): string
    {
        return trim((string) ($this->{$property} ?? ''));
    }

    protected function isDisabilityEnabled(): bool
    {
        if (property_exists($this, 'personalForm') && $this->personalForm) {
            return (bool) $this->personalForm->hasDisability;
        }

        return (bool) ($this->isDisability ?? false);
    }

    protected function countryOptions(string $searchTerm, $selectedId, string $cacheKeySuffix = 'current'): array
    {
        $locale = app()->getLocale();

        $base = Country::query()
            ->select('countries.id', DB::raw('ct.title as label'))
            ->join('country_translations as ct', function ($join) use ($locale) {
                $join->on('ct.country_id', '=', 'countries.id')
                    ->where('ct.locale', $locale);
            })
            ->orderBy('ct.title');

        if ($searchTerm === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "personnel:country:{$cacheKeySuffix}:{$locale}",
                base: $base,
                selectedId: $selectedId,
                limit: 80
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'ct.title',
            searchTerm: $searchTerm,
            selectedId: $selectedId,
            limit: 80
        );
    }

    protected function cachedOptionsWithSelected(string $cacheKey, Builder $base, $selectedId, int $limit = 50): array
    {
        $options = cache()->remember(
            $cacheKey,
            now()->addMinutes($this->dropdownCacheMinutes),
            function () use ($base, $limit) {
                $query = clone $base;
                $query->limit($limit);

                return $this->toOptions($query);
            }
        );

        return $this->appendSelectedOption($options, $base, $selectedId);
    }

    protected function appendSelectedOption(array $options, Builder $base, $selectedId): array
    {
        if (empty($selectedId)) {
            return $options;
        }

        $hasSelected = collect($options)->first(
            fn ($option) => (int) $option['id'] === (int) $selectedId
        );

        if ($hasSelected) {
            return $options;
        }

        $selectedRow = (clone $base)
            ->where($base->getModel()->getQualifiedKeyName(), $selectedId)
            ->first();

        if ($selectedRow) {
            $options[] = [
                'id' => (int) data_get($selectedRow, 'id'),
                'label' => (string) data_get($selectedRow, 'label'),
            ];
        }

        return collect($options)
            ->unique('id')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function render()
    {
        $steps = ['steps' => $this->getSteps()];

        $view_data = $this->shouldLoadLookupData()
            ? resolve(CallPersonnelInfo::class)->getAll($this->isDisability, $this)
            : [];

        $view_name = ! empty($this->personnelModel)
                    ? 'livewire.personnel.edit-personnel'
                    : 'livewire.personnel.add-personnel';

        return view($view_name, array_merge($steps, array_merge($view_data, $this->isolated)));
    }

    protected function shouldLoadLookupData(): bool
    {
        return ! in_array((int) ($this->step ?? 0), [1, 2, 3], true);
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
}
