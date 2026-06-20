<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Enums\KnowledgeStatusEnum;
use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Models\City;
use App\Models\CountryTranslation;
use App\Models\Structure;
use App\Modules\Personnel\Support\Traits\Personnel\HandlesPersonnelStepValidation;
use App\Modules\Personnel\Support\Traits\Personnel\ManagesPersonnelRelationRows;
use App\Modules\Personnel\Support\Traits\PersonnelDropdownOptions;
use App\Modules\Personnel\Support\Traits\Validations\PersonnelValidationTrait;
use App\Services\CalculateSeniorityService;
use App\Services\EducationDurationService;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

abstract class AbstractPersonnelWizardStep extends Component
{
    use DropdownConstructTrait;
    use FillComplexArrayTrait;
    use HandlesPersonnelStepValidation;
    use ManagesPersonnelRelationRows;
    use NormalizesDropdownPayloads;
    use PersonnelDropdownOptions;
    use PersonnelValidationTrait;

    public int $step = 1;

    public array $state = [];

    public array $calculatedDataEducation = [];

    public array $calculatedDataExtraEducation = [];

    public array $calculatedData = [];

    public array $knowledges = [];

    public array $personnelExtra = [];

    public string $searchNationality = '';
    public string $searchPreviousNationality = '';
    public string $searchEducationDegree = '';
    public string $searchStructure = '';
    public string $searchPosition = '';
    public string $searchLaborStructure = '';
    public string $searchLaborPosition = '';
    public string $searchWorkNorm = '';
    public string $searchDisability = '';
    public string $searchSocialOrigin = '';
    public string $searchDocumentNationality = '';
    public string $searchDocumentBornCountry = '';
    public string $searchDocumentCity = '';
    public string $searchEducationInstitution = '';
    public string $searchEducationForm = '';
    public string $searchExtraEducationInstitution = '';
    public string $searchExtraEducationForm = '';
    public string $searchEducationType = '';
    public string $searchEducationDocumentType = '';
    public string $searchRank = '';
    public string $searchRankReason = '';
    public string $searchMilitaryRank = '';
    public string $searchKinship = '';
    public string $searchLanguage = '';
    public string $searchDegree = '';
    public string $searchDegreeDocumentType = '';
    public string $searchAward = '';
    public string $searchPunishment = '';

    public bool $isSpecialService = false;

    public bool $isAddedRank = false;

    protected bool $bootstrapping = false;

    protected ?CalculateSeniorityService $seniorityServiceInstance = null;

    protected ?EducationDurationService $educationDurationServiceInstance = null;

    public function mount(array $state = []): void
    {
        $this->step = $this->stepNumber();
        $this->knowledges = KnowledgeStatusEnum::values();
        $this->personnelExtra = [
            'participation_in_war' => null,
            'discrediting_information' => null,
        ];
        $this->bootstrapping = true;
        $this->state = $state;
        $this->hydrateFromState($state);
        $this->bootstrapping = false;
    }

    public function updated($name, $value): void
    {
        if ($this->bootstrapping) {
            return;
        }

        if (method_exists($this, 'updatedEducationForm') && str_starts_with((string) $name, 'educationForm.')) {
            $this->updatedEducationForm($value, str_replace('educationForm.', '', (string) $name));
        }

        if (method_exists($this, 'updatedDocumentForm') && str_starts_with((string) $name, 'documentForm.')) {
            $this->updatedDocumentForm($value, str_replace('documentForm.', '', (string) $name));
        }

        if (method_exists($this, 'updatedLaborActivityForm') && str_starts_with((string) $name, 'laborActivityForm.')) {
            $this->updatedLaborActivityForm($value, (string) $name);
        }
    }

    #[On('personnel-crud:request-next')]
    public function requestNext(): void
    {
        $this->validateNavigationStepIfNeeded();

        $this->dispatch(
            'personnel-crud:navigate-approved',
            step: $this->stepNumber(),
            targetStep: $this->stepNumber() + 1,
            payload: $this->stepPayloadForParent()
        );
    }

    #[On('personnel-crud:request-previous')]
    public function requestPrevious(): void
    {
        $this->validateNavigationStepIfNeeded();

        $this->dispatch(
            'personnel-crud:navigate-approved',
            step: $this->stepNumber(),
            targetStep: $this->stepNumber() - 1,
            payload: $this->stepPayloadForParent()
        );
    }

    #[On('personnel-crud:request-select')]
    public function requestSelect(int $targetStep): void
    {
        if ($targetStep === $this->stepNumber()) {
            return;
        }

        $this->validateNavigationStepIfNeeded();

        $this->dispatch(
            'personnel-crud:navigate-approved',
            step: $this->stepNumber(),
            targetStep: $targetStep,
            payload: $this->stepPayloadForParent()
        );
    }

    #[On('personnel-crud:request-save')]
    public function requestSave(): void
    {
        $this->prepareStepForValidation($this->stepNumber());

        if ($this->shouldValidateStep($this->stepNumber())) {
            $validator = $this->getValidationRulesForStep($this->stepNumber());

            if (! empty($validator)) {
                $this->validate($validator);
            }
        }

        $this->dispatch(
            'personnel-crud:save-approved',
            step: $this->stepNumber(),
            payload: $this->stepPayloadForParent()
        );
    }

    protected function activeStepSearchModels(): array
    {
        $map = [];

        foreach ($this->stepSearchKeysForStep() as $key) {
            $map[$key] = $key;
        }

        return $map;
    }

    protected function activeStepSearchPlaceholders(): array
    {
        $placeholders = [];

        foreach ($this->stepSearchKeysForStep() as $key) {
            $placeholders[$key] = __('personnel::common.placeholders.search');
        }

        return $placeholders;
    }

    protected function stepSearchKeysForStep(): array
    {
        return match ($this->stepNumber()) {
            2 => ['searchDocumentNationality', 'searchDocumentBornCountry', 'searchDocumentCity'],
            3 => ['searchEducationInstitution', 'searchEducationForm', 'searchEducationType', 'searchExtraEducationInstitution', 'searchExtraEducationForm', 'searchEducationDocumentType'],
            4 => ['searchLaborStructure', 'searchLaborPosition', 'searchRank', 'searchRankReason'],
            5 => ['searchMilitaryRank'],
            6 => ['searchAward', 'searchPunishment'],
            7 => ['searchKinship'],
            8 => ['searchLanguage', 'searchDegree', 'searchDegreeDocumentType'],
            default => [],
        };
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

    public function updatedLaborActivityFormLaborActivityUseLookup($value): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        if ($value) {
            $this->laborActivityForm->laborActivity['position'] = '';
        } else {
            $this->laborActivityForm->laborActivity['position_id'] = null;
            $this->laborActivityForm->laborActivity['structure_id'] = null;
        }
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

    public function selectLaborStructure(int $structureId): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $structure = Structure::query()
            ->accessible()
            ->find($structureId, ['id', 'name']);

        if (! $structure) {
            return;
        }

        $this->laborActivityForm->laborActivity['structure_id'] = (int) $structure->id;
        $this->laborActivityForm->laborActivity['structure_label'] = (string) $structure->name;
        $this->laborActivityForm->laborActivity['position_id'] = null;
        $this->laborActivityForm->laborActivity['position_label'] = null;
        $this->searchLaborPosition = '';
    }

    public function setStructure($payload, $list = null, $field = null, $key = null, $isCoded = null): void
    {
        if (is_array($payload)) {
            $list = $payload['list'] ?? $list;
            $field = $payload['field'] ?? $field;
            $key = $payload['row'] ?? $key;
            $isCoded = $payload['coded'] ?? $isCoded;
            $payload = $payload['id'] ?? null;
        }

        $structureId = is_numeric($payload) ? (int) $payload : null;

        if (! $structureId || $field !== 'structure_id') {
            return;
        }

        if ($list === 'laborActivityForm') {
            $this->selectLaborStructure($structureId);
        }
    }

    public function exceptArray($arrayKey, ?int $step = null)
    {
        $step ??= $this->stepNumber();

        $filtered = array_filter($this->validationRules()[$step] ?? [], function ($key) use ($arrayKey) {
            return str_starts_with($key, $arrayKey);
        }, ARRAY_FILTER_USE_KEY);

        return Arr::except($this->validationRules()[$step] ?? [], array_keys($filtered));
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
            $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time', 'use_lookup']);
            $list[] = $draft;
        }

        return $list;
    }

    protected function laborActivityDraftHasValues(): bool
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return false;
        }

        $draft = Arr::except($this->laborActivityForm->laborActivity ?? [], ['time', 'use_lookup']);

        return $this->payloadHasValues($draft);
    }

    protected function optionLabelFor(array $options, $id): ?string
    {
        foreach ($options as $option) {
            if ((int) ($option['id'] ?? 0) === (int) $id) {
                return (string) ($option['label'] ?? '');
            }
        }

        return null;
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

    protected function documentFormInstance(): ?DocumentForm
    {
        if (property_exists($this, 'documentForm') && $this->documentForm instanceof DocumentForm) {
            return $this->documentForm;
        }

        return null;
    }

    protected function educationFormInstance(): ?EducationForm
    {
        if (property_exists($this, 'educationForm') && $this->educationForm instanceof EducationForm) {
            return $this->educationForm;
        }

        return null;
    }

    protected function laborActivityFormInstance(): ?LaborActivityForm
    {
        if (property_exists($this, 'laborActivityForm') && $this->laborActivityForm instanceof LaborActivityForm) {
            return $this->laborActivityForm;
        }

        return null;
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

    protected function resolvePersonnelState(): array
    {
        return [];
    }

    abstract protected function hydrateFromState(array $state): void;

    abstract protected function stepPayloadForParent(): array;

    abstract protected function stepNumber(): int;
}
