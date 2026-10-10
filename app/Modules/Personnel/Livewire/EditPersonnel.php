<?php

namespace App\Modules\Personnel\Livewire;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use App\Livewire\Forms\Personnel\DocumentForm;
use App\Livewire\Forms\Personnel\EducationForm;
use App\Livewire\Forms\Personnel\KinshipForm;
use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Livewire\Forms\Personnel\MiscellaneousForm;
use App\Livewire\Forms\Personnel\PersonalInformationForm;
use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use App\Models\Personnel;
use App\Modules\Orders\Contracts\OrderDrafter;
use App\Modules\Personnel\Application\Services\PersonnelChangeGuard;
use App\Modules\Personnel\Application\Services\PersonnelFieldGroupRegistry;
use App\Modules\Personnel\Contracts\PersonnelChangeMode;
use App\Modules\Personnel\Services\PersonnelFormAssembler;
use App\Modules\Personnel\Services\PersonnelPersistenceService;
use App\Modules\Personnel\Support\Traits\PersonnelCrud;
use App\Modules\Personnel\Support\Traits\RelationCruds\RelationCrudTrait;
use App\Services\PersonnelPendingApprovalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
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

    #[Locked]
    public $personnelModel;

    /** Hide the built-in horizontal stepper when the host page supplies step navigation. */
    public bool $chromeless = false;

    /** @var array<int> */
    public array $loadedSteps = [];

    public ?int $loadedPersonnelId = null;

    /** @var array<string> */
    protected array $relationGroupsLoaded = [];

    /** Jurnal rejimli sahələr dəyişəndə tələb olunan səbəb (dəyişiklik siyasəti). */
    public string $changeReason = '';

    /**
     * Sənəd/ailə kimi əlaqə qruplarının yüklənmə anındakı izi: saxlamada müqayisə edilir.
     * Kilidlidir — klient onu dəyişə bilmir.
     *
     * @var array<string, array{hash: string, counts: array<string, int>}>
     */
    #[Locked]
    public array $relationBaselines = [];

    public function mount(?int $step = null): void
    {
        $personnel = $this->personnelModelDataInstance();

        $this->authorize('update', $personnel);
        $this->title = __('personnel::common.titles.edit_personnel');
        $this->step = $step !== null ? $this->stepNavigationService()->select($step) : 1;
        $this->resetStepTrackingFor($personnel->getKey());
        $this->loadStepData((int) $this->step);
    }

    protected function ensureCurrentStepDataLoaded(): void
    {
        $this->loadStepData((int) $this->step);
    }

    public function confirmPersonnel(): void
    {
        $this->authorize('confirmation-general');

        app(PersonnelPendingApprovalService::class)->approve($this->personnelModelDataInstance());
        $this->dispatch('personnelAdded', __('personnel::common.messages.personnel_approved'));
    }

    public function store(): void
    {
        $personnel = $this->personnelModelDataInstance();
        $this->ensureCurrentStepDataLoaded();
        $this->authorize('update', $personnel);
        $this->validateCurrentStepForSave();
        $this->persistPersonnel();
    }

    protected function storeFromChildValidation(): void
    {
        $personnel = $this->personnelModelDataInstance();
        $this->ensureCurrentStepDataLoaded();
        $this->authorize('update', $personnel);
        $this->validatePrimaryStepForPersist();
        $this->persistPersonnel();
    }

    protected function persistPersonnel(): void
    {
        $personnel = $this->personnelModelDataInstance();
        $journal = $this->enforceChangePolicy($personnel);

        if (! empty($this->avatar)) {
            $this->personalForm->personnel['photo'] = $this->avatar->store('personnel', \App\Support\Uploads\PrivateFiles::DISK);
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

        $guard = $this->changeGuard();
        $save = function () use ($assembled, $relationPayloads, $guard, $journal): void {
            DB::transaction(function () use ($assembled, $relationPayloads, $guard, $journal) {
                $personnel = $this->personnelModelDataInstance();
                $personnel->update($guard->withoutGuarded($personnel, $assembled['personnel_data']));
                $this->updatePersonnelRelations($relationPayloads);

                if (! empty($assembled['personnel_extra'])) {
                    $personnel->update($assembled['personnel_extra']);
                }

                foreach ($journal['relations'] as $group => $changes) {
                    $guard->recordJournal($group, (string) $journal['reason'], $changes, $personnel, ['source' => 'personnel_form']);
                }
            });
        };

        $journal['reason'] !== null ? $guard->withReason($journal['reason'], $save) : $save();

        $this->changeReason = '';
        $this->refreshRelationBaselines();

        $this->dispatchPersonnelStored(__('personnel::common.messages.personnel_updated'));
        $this->dispatchModalCloseEvent();
    }

    /**
     * Dəyişiklik siyasəti formun saxlama axınında: `order` rejimli sahələrdə fərqli dəyər
     * sahə xətası ilə qaytarılır (Livewire vəziyyəti əl ilə dəyişdirilsə belə heç nə
     * yazılmır), `journal` rejimli dəyişiklik isə ən azı 5 simvolluq səbəb tələb edir.
     * Model səviyyəsindəki yoxlama (PersonnelObserver) bundan asılı olmayaraq işləyir.
     *
     * @return array{reason: string|null, relations: array<string, array<string, array{old: int, new: int}>>}
     *
     * @throws ValidationException
     */
    protected function enforceChangePolicy(Personnel $personnel): array
    {
        $guard = $this->changeGuard();
        $registry = app(PersonnelFieldGroupRegistry::class);
        $errors = [];

        if (in_array(1, $this->loadedSteps, true)) {
            foreach ($guard->changedInPayload($personnel, $this->submittedPersonalColumns()) as $attribute) {
                $errors['personalForm.personnel.'.$attribute] = $registry->orderMessage((string) $guard->groupOf($attribute));
            }

            if (! empty($this->avatar) && $guard->modeFor(PersonnelFieldGroupRegistry::PHOTO_NOTES) === PersonnelChangeMode::Order) {
                $errors['avatar'] = $registry->orderMessage(PersonnelFieldGroupRegistry::PHOTO_NOTES);
            }
        }

        $relationChanges = $this->changedRelationGroups();
        foreach ($relationChanges as $group => $counts) {
            if ($guard->modeFor($group) === PersonnelChangeMode::Order) {
                $errors['changePolicy.'.$group] = $registry->orderMessage($group);
            }
        }

        $journalGroups = $this->pendingJournalGroups($relationChanges);
        $reason = trim($this->changeReason);
        if ($journalGroups !== [] && mb_strlen($reason) < PersonnelChangeMode::MIN_REASON_LENGTH) {
            $errors['changeReason'] = $guard->reasonRequiredMessage();
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $journalRelations = array_filter(
            $relationChanges,
            fn (string $group): bool => $guard->modeFor($group) === PersonnelChangeMode::Journal,
            ARRAY_FILTER_USE_KEY,
        );

        return [
            'reason' => $journalGroups !== [] ? $reason : null,
            'relations' => $journalRelations,
        ];
    }

    /**
     * Saxlanmamış dəyişikliklər arasında jurnal rejimli qruplar (səbəb sahəsini göstərmək
     * və saxlamada tələb etmək üçün).
     *
     * @param  array<string, array<string, array{old: int, new: int}>>|null  $relationChanges
     * @return list<string>
     */
    protected function pendingJournalGroups(?array $relationChanges = null): array
    {
        $guard = $this->changeGuard();
        $personnel = $this->personnelModelDataInstance();
        $groups = [];

        if (in_array(1, $this->loadedSteps, true)) {
            foreach ($guard->changedInPayload($personnel, $this->submittedPersonalColumns(), [PersonnelChangeMode::Journal]) as $attribute) {
                $groups[] = (string) $guard->groupOf($attribute);
            }

            if (! empty($this->avatar) && $guard->modeFor(PersonnelFieldGroupRegistry::PHOTO_NOTES) === PersonnelChangeMode::Journal) {
                $groups[] = PersonnelFieldGroupRegistry::PHOTO_NOTES;
            }
        }

        foreach (array_keys($relationChanges ?? $this->changedRelationGroups()) as $group) {
            if ($guard->modeFor($group) === PersonnelChangeMode::Journal) {
                $groups[] = $group;
            }
        }

        return array_values(array_unique($groups));
    }

    /**
     * Formun aşağısındakı səbəb sahəsi: jurnal rejimli sahə dəyişəndə görünür.
     *
     * @return list<string> dəyişən jurnal qruplarının adları
     */
    #[Computed]
    public function journalGroupLabels(): array
    {
        $registry = app(PersonnelFieldGroupRegistry::class);

        return array_map(fn (string $group): string => $registry->label($group), $this->pendingJournalGroups());
    }

    /**
     * Məhdud rejimli `personnels` sütunları: sütun → rejim, qrup və «Əmr yarat» keçidi.
     * Formada `order` sahələri kilidlənir və «(əmrlə)» nişanı alır.
     *
     * @return array<string, array{mode: string, group: string, label: string, hint: string, order_url: string|null}>
     */
    #[Computed]
    public function fieldPolicies(): array
    {
        $guard = $this->changeGuard();
        $registry = app(PersonnelFieldGroupRegistry::class);
        $policies = [];

        foreach ($registry->columnMap() as $attribute => $group) {
            $mode = $guard->modeFor($group);
            if (! $mode->isRestricted()) {
                continue;
            }

            $policies[$attribute] = [
                'mode' => $mode->value,
                'group' => $group,
                'label' => $registry->label($group),
                'hint' => $group === PersonnelFieldGroupRegistry::ASSIGNMENT
                    ? __('personnel::common.hints.assignment_order_only')
                    : __('personnel::change_policy.hints.order_only', ['group' => $registry->label($group)]),
                'order_url' => $mode === PersonnelChangeMode::Order ? $this->orderUrlFor($group) : null,
            ];
        }

        return $policies;
    }

    /**
     * Bağlı struktur/vəzifə sahələrinin yanındakı "Köçürmə əmri yarat" keçidi.
     */
    #[Computed]
    public function transferOrderUrl(): ?string
    {
        return $this->orderUrlFor(PersonnelFieldGroupRegistry::ASSIGNMENT);
    }

    /**
     * Qrupu yaza bilən əmr növü ilə açılan əmrlər siyahısı. İcazə, modul və ya şablon
     * yoxdursa keçid göstərilmir.
     */
    protected function orderUrlFor(string $group): ?string
    {
        $effect = app(PersonnelFieldGroupRegistry::class)->orderEffect($group);

        if ($effect === null || ! (auth()->user()?->can('add-orders') ?? false) || ! Route::has('orders') || ! app()->bound(OrderDrafter::class)) {
            return null;
        }

        $preset = array_key_first(app(OrderDrafter::class)->personnelTemplates($effect));

        return $preset === null ? null : route('orders', ['create' => 1, 'preset' => (string) $preset]);
    }

    /**
     * Yüklənmiş və məhdud rejimli əlaqə qruplarından (sənədlər, ailə) dəyişənlər:
     * qrup → əlaqə → [köhnə, yeni] sətir sayı.
     *
     * @return array<string, array<string, array{old: int, new: int}>>
     */
    protected function changedRelationGroups(): array
    {
        $guard = $this->changeGuard();
        $registry = app(PersonnelFieldGroupRegistry::class);
        $snapshot = null;
        $changed = [];

        foreach ($this->relationBaselines as $group => $baseline) {
            if (! $guard->modeFor($group)->isRestricted()) {
                continue;
            }

            $snapshot ??= $this->relationPayloadSnapshot();
            $current = $this->relationFingerprint($registry->relations($group), $snapshot);
            if ($current['hash'] === $baseline['hash']) {
                continue;
            }

            foreach ($current['counts'] as $relation => $count) {
                $changed[$group][$relation] = ['old' => (int) ($baseline['counts'][$relation] ?? 0), 'new' => $count];
            }
        }

        return $changed;
    }

    /** Addım yüklənəndə həmin addımın əlaqə qruplarının izini saxlayır. */
    protected function rememberRelationBaselines(int $step): void
    {
        $registry = app(PersonnelFieldGroupRegistry::class);
        $snapshot = null;

        foreach ($registry->keys() as $group) {
            if ($registry->wizardStep($group) !== $step || $registry->relations($group) === []) {
                continue;
            }

            $snapshot ??= $this->relationPayloadSnapshot();
            $this->relationBaselines[$group] = $this->relationFingerprint($registry->relations($group), $snapshot);
        }
    }

    protected function refreshRelationBaselines(): void
    {
        $registry = app(PersonnelFieldGroupRegistry::class);
        $snapshot = null;

        foreach (array_keys($this->relationBaselines) as $group) {
            $snapshot ??= $this->relationPayloadSnapshot();
            $this->relationBaselines[$group] = $this->relationFingerprint($registry->relations($group), $snapshot);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function relationPayloadSnapshot(): array
    {
        return app(PersonnelFormAssembler::class)->buildForStore(
            personalForm: $this->personalForm,
            documentForm: $this->documentForm,
            educationForm: $this->educationForm,
            laborActivityForm: $this->laborActivityForm,
            historyForm: $this->historyForm,
            awardsPunishmentsForm: $this->awardsPunishmentsForm,
            kinshipForm: $this->kinshipForm,
            miscForm: $this->miscForm,
            dateFields: [],
            dateNormalizer: fn (array $payload): array => $payload,
        )['relation_payloads'];
    }

    /**
     * Əlaqə payload-larının müqayisə izi: boş dəyərlər və tiplər (int / "1") eyniləşdirilir.
     *
     * @param  list<string>  $relations
     * @param  array<string, mixed>  $snapshot
     * @return array{hash: string, counts: array<string, int>}
     */
    protected function relationFingerprint(array $relations, array $snapshot): array
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (is_array($value)) {
                $value = array_map($normalize, $value);
                $value = array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== []);
                if (! array_is_list($value)) {
                    ksort($value);
                }

                return $value;
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return $value === null || $value === '' || ! is_scalar($value) ? null : (string) $value;
        };

        $data = [];
        $counts = [];
        foreach ($relations as $relation) {
            $payload = $snapshot[$relation] ?? [];
            $data[$relation] = $normalize($payload);
            $counts[$relation] = is_array($payload) && array_is_list($payload) ? count($payload) : (int) ($normalize($payload) !== []);
        }

        return ['hash' => md5((string) json_encode($data)), 'counts' => $counts];
    }

    /**
     * Formun yazdığı şəxsi sütunlar: xitam tarixi forma tərəfindən heç vaxt yazılmır
     * (PersonnelFormAssembler onu atır), ona görə siyasət yoxlamasına da düşmür.
     *
     * @return array<string, mixed>
     */
    protected function submittedPersonalColumns(): array
    {
        return Arr::except((array) $this->personalForm->personnel, PersonnelFormAssembler::TERMINATION_MANAGED_FIELDS);
    }

    protected function changeGuard(): PersonnelChangeGuard
    {
        return app(PersonnelChangeGuard::class);
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
        $this->rememberRelationBaselines($step);
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
                'structure',
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
        $this->relationBaselines = [];
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
