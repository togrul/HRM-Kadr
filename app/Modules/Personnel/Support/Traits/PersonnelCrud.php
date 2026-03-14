<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Models\City;
use App\Models\CountryTranslation;
use App\Models\Structure;
use App\Modules\Personnel\Support\Traits\DispatchesPersonnelUiEvents;
use App\Modules\Personnel\Support\Traits\Personnel\HandlesPersonnelStepFlow;
use App\Modules\Personnel\Support\Traits\Personnel\HandlesPersonnelStepValidation;
use App\Modules\Personnel\Support\Traits\Personnel\ManagesPersonnelRelationRows;
use App\Modules\Personnel\Support\Traits\Validations\PersonnelValidationTrait;
use App\Services\CalculateSeniorityService;
use App\Services\EducationDurationService;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

trait PersonnelCrud
{
    use DispatchesPersonnelUiEvents;
    use DropdownConstructTrait;
    use FillComplexArrayTrait;
    use HandlesPersonnelStepValidation;
    use HandlesPersonnelStepFlow;
    use ManagesPersonnelRelationRows;
    use NormalizesDropdownPayloads;
    use PersonnelDropdownOptions;
    use PersonnelValidationTrait;
    use WithFileUploads;

    public $title;

    public $step;

    public array $completedSteps;

    public array $calculatedDataEducation = [];

    public array $calculatedDataExtraEducation = [];

    public array $calculatedData = [];

    public array $isolated = [];

    public $avatar;

    public $searchNationality = '';

    public $searchPreviousNationality = '';

    public $searchEducationDegree = '';

    public $searchStructure = '';

    public $searchPosition = '';

    public $searchLaborStructure = '';

    public $searchLaborPosition = '';

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

    #[On('personnel-crud:navigate-approved')]
    public function handleChildNavigationApproved(int $step, int $targetStep, array $payload = []): void
    {
        if ((int) $this->step !== $step || ! $this->activeStepUsesChildComponent()) {
            return;
        }

        $this->syncChildStepPayload($step, $payload);
        $this->step = $this->stepNavigationService()->select($targetStep);
        $this->handleStepChanged();
    }

    #[On('personnel-crud:save-approved')]
    public function handleChildSaveApproved(int $step, array $payload = []): void
    {
        if ((int) $this->step !== $step || ! $this->activeStepUsesChildComponent()) {
            return;
        }

        $this->syncChildStepPayload($step, $payload);
        $this->storeFromChildValidation();
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => 'image|max:2048',
        ]);
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
        $step ??= (int) $this->step;

        $filtered = array_filter($this->validationRules()[$step] ?? [], function ($key) use ($arrayKey) {
            return str_starts_with($key, $arrayKey);
        }, ARRAY_FILTER_USE_KEY);

        return Arr::except($this->validationRules()[$step] ?? [], array_keys($filtered));
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
        $personnelContext = $this->personnelViewContext();

        $view_name = ! empty($this->personnelModel)
                    ? 'personnel::livewire.personnel.edit-personnel'
                    : 'personnel::livewire.personnel.add-personnel';

        return view($view_name, array_merge($steps, array_merge($this->isolated, $personnelContext)));
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

    protected function personnelViewContext(): array
    {
        if (! method_exists($this, 'personnelModelDataInstance')) {
            return [
                'personnelIsPending' => false,
                'personnelPhotoUrl' => null,
            ];
        }

        $personnel = $this->personnelModelDataInstance();

        return [
            'personnelIsPending' => (bool) ($personnel->is_pending ?? false),
            'personnelPhotoUrl' => ! empty($personnel->photo)
                ? \Illuminate\Support\Facades\Storage::url($personnel->photo)
                : null,
        ];
    }

    protected function syncChildStepPayload(int $step, array $payload): void
    {
        match ($step) {
            2 => $this->syncDocumentStepPayload($payload),
            3 => $this->syncEducationStepPayload($payload),
            4 => $this->syncLaborStepPayload($payload),
            5 => $this->syncHistoryStepPayload($payload),
            6 => $this->syncAwardsStepPayload($payload),
            7 => $this->syncKinshipStepPayload($payload),
            8 => $this->syncMiscStepPayload($payload),
            default => null,
        };
    }

    protected function syncDocumentStepPayload(array $payload): void
    {
        if (! property_exists($this, 'documentForm') || ! $this->documentForm) {
            return;
        }

        $this->documentForm->fillFromArrays(
            $payload['document'] ?? [],
            $payload['serviceCards'] ?? [],
            $payload['serviceCardsList'] ?? [],
            $payload['passports'] ?? [],
            $payload['passportsList'] ?? []
        );
    }

    protected function syncEducationStepPayload(array $payload): void
    {
        if (! property_exists($this, 'educationForm') || ! $this->educationForm) {
            return;
        }

        $this->educationForm->education = array_replace($this->educationForm->education ?? [], $payload['education'] ?? []);
        $this->educationForm->extraEducation = array_replace($this->educationForm->extraEducation ?? [], $payload['extraEducation'] ?? []);
        $this->educationForm->extraEducationList = $payload['extraEducationList'] ?? [];
        $this->educationForm->hasExtraEducation = (bool) ($payload['hasExtraEducation'] ?? false);
        $this->recalculateEducationDurations();
    }

    protected function syncLaborStepPayload(array $payload): void
    {
        if (! property_exists($this, 'laborActivityForm') || ! $this->laborActivityForm) {
            return;
        }

        $this->laborActivityForm->fillFromArrays(
            $payload['laborActivity'] ?? [],
            $payload['laborActivityList'] ?? [],
            $payload['rank'] ?? [],
            $payload['rankList'] ?? []
        );
        $this->isSpecialService = (bool) ($payload['isSpecialService'] ?? false);
        $this->calculateSeniority();
    }

    protected function syncHistoryStepPayload(array $payload): void
    {
        if (! property_exists($this, 'historyForm') || ! $this->historyForm) {
            return;
        }

        $this->historyForm->fillFromArrays(
            $payload['military'] ?? [],
            $payload['militaryList'] ?? [],
            $payload['injury'] ?? [],
            $payload['injuryList'] ?? [],
            $payload['captivity'] ?? [],
            $payload['captivityList'] ?? []
        );

        if (property_exists($this, 'personalForm') && $this->personalForm) {
            $this->personalForm->personnelExtra = array_replace(
                $this->personalForm->personnelExtra ?? [],
                $payload['personnelExtra'] ?? []
            );
        }
    }

    protected function syncAwardsStepPayload(array $payload): void
    {
        if (! property_exists($this, 'awardsPunishmentsForm') || ! $this->awardsPunishmentsForm) {
            return;
        }

        $this->awardsPunishmentsForm->fillFromArrays(
            $payload['award'] ?? [],
            $payload['awardList'] ?? [],
            $payload['punishment'] ?? [],
            $payload['punishmentList'] ?? []
        );

        if (property_exists($this, 'personalForm') && $this->personalForm) {
            $this->personalForm->personnelExtra = array_replace(
                $this->personalForm->personnelExtra ?? [],
                $payload['personnelExtra'] ?? []
            );
        }
    }

    protected function syncKinshipStepPayload(array $payload): void
    {
        if (! property_exists($this, 'kinshipForm') || ! $this->kinshipForm) {
            return;
        }

        $this->kinshipForm->fillFromArrays(
            $payload['kinship'] ?? [],
            $payload['kinshipList'] ?? [],
            $payload['editingKinshipKey'] ?? null
        );
    }

    protected function syncMiscStepPayload(array $payload): void
    {
        if (! property_exists($this, 'miscForm') || ! $this->miscForm) {
            return;
        }

        $this->miscForm->fillFromArrays(
            $payload['language'] ?? [],
            $payload['languageList'] ?? [],
            $payload['event'] ?? [],
            $payload['eventList'] ?? [],
            $payload['degree'] ?? [],
            $payload['degreeList'] ?? [],
            $payload['election'] ?? [],
            $payload['electionList'] ?? [],
            (bool) ($payload['hasElectedElectorals'] ?? false)
        );
    }

    protected function validatePrimaryStepForPersist(): void
    {
        $stepOneRules = $this->validationRules()[1] ?? [];

        if (! empty($stepOneRules)) {
            $this->validate($stepOneRules);
        }
    }

    abstract protected function storeFromChildValidation(): void;
}
