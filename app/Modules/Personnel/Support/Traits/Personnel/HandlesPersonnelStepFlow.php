<?php

namespace App\Modules\Personnel\Support\Traits\Personnel;

use App\Enums\KnowledgeStatusEnum;
use App\Modules\Personnel\Services\PersonnelStepNavigationService;
use App\Modules\Personnel\Services\PersonnelStepState;
use Livewire\Attributes\Computed;

trait HandlesPersonnelStepFlow
{
    protected ?PersonnelStepNavigationService $stepNavigationServiceInstance = null;

    protected ?PersonnelStepState $stepStateServiceInstance = null;

    public function previousStep()
    {
        $next = $this->stepNavigationService()->previous((int) $this->step);

        if ((int) $this->step === $next) {
            return;
        }

        $this->validateNavigationStepIfNeeded();
        $this->step = $next;
        $this->handleStepChanged();
    }

    public function selectStep($step): void
    {
        $step = (int) $step;

        if ((int) $this->step === $step) {
            return;
        }

        $this->validateNavigationStepIfNeeded();

        $this->step = $this->stepNavigationService()->select($step);
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

        $stepName = $this->stepStateService()->completionStepName($step);

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

    public function nextStep(): void
    {
        $this->isAddedRank = false;
        $next = $this->stepNavigationService()->next((int) $this->step);

        if ((int) $this->step === $next) {
            return;
        }

        $this->validateNavigationStepIfNeeded();

        $this->step = $next;
        $this->handleStepChanged();
    }

    protected function getSteps(): array
    {
        return $this->stepNavigationService()->steps();
    }

    public function getActiveStepTemplate(): string
    {
        return $this->stepStateService()->stepTemplate((int) $this->step);
    }

    #[Computed]
    public function activeStepTemplate(): string
    {
        return $this->getActiveStepTemplate();
    }

    #[Computed]
    public function activeStepComponent(): string
    {
        return $this->stepStateService()->stepComponent((int) $this->step);
    }

    #[Computed]
    public function activeStepUsesChildComponent(): bool
    {
        return $this->stepStateService()->stepUsesChildComponent((int) $this->safePropertyValue('step', 1));
    }

    #[Computed]
    public function activeStepChildComponent(): ?string
    {
        return $this->stepStateService()->stepChildComponent((int) $this->safePropertyValue('step', 1));
    }

    #[Computed]
    public function activeStepChildState(): array
    {
        $step = (int) $this->safePropertyValue('step', 1);

        return match ($step) {
            2 => [
                'document' => $this->safePropertyValue('documentForm')?->document ?? [],
                'serviceCards' => $this->safePropertyValue('documentForm')?->serviceCards ?? [],
                'serviceCardsList' => $this->safePropertyValue('documentForm')?->serviceCardsList ?? [],
                'passports' => $this->safePropertyValue('documentForm')?->passports ?? [],
                'passportsList' => $this->safePropertyValue('documentForm')?->passportsList ?? [],
            ],
            3 => [
                'education' => $this->safePropertyValue('educationForm')?->education ?? [],
                'extraEducation' => $this->safePropertyValue('educationForm')?->extraEducation ?? [],
                'extraEducationList' => $this->safePropertyValue('educationForm')?->extraEducationList ?? [],
                'hasExtraEducation' => (bool) ($this->safePropertyValue('educationForm')?->hasExtraEducation ?? false),
            ],
            4 => [
                'laborActivity' => $this->safePropertyValue('laborActivityForm')?->laborActivity ?? [],
                'laborActivityList' => $this->safePropertyValue('laborActivityForm')?->laborActivityList ?? [],
                'rank' => $this->safePropertyValue('laborActivityForm')?->rank ?? [],
                'rankList' => $this->safePropertyValue('laborActivityForm')?->rankList ?? [],
                'isSpecialService' => (bool) $this->safePropertyValue('isSpecialService', false),
            ],
            5 => [
                'military' => $this->safePropertyValue('historyForm')?->military ?? [],
                'militaryList' => $this->safePropertyValue('historyForm')?->militaryList ?? [],
                'injury' => $this->safePropertyValue('historyForm')?->injury ?? [],
                'injuryList' => $this->safePropertyValue('historyForm')?->injuryList ?? [],
                'captivity' => $this->safePropertyValue('historyForm')?->captivity ?? [],
                'captivityList' => $this->safePropertyValue('historyForm')?->captivityList ?? [],
                'personnelExtra' => $this->safePropertyValue('personalForm')?->personnelExtra ?? [],
            ],
            6 => [
                'award' => $this->safePropertyValue('awardsPunishmentsForm')?->award ?? [],
                'awardList' => $this->safePropertyValue('awardsPunishmentsForm')?->awardList ?? [],
                'punishment' => $this->safePropertyValue('awardsPunishmentsForm')?->punishment ?? [],
                'punishmentList' => $this->safePropertyValue('awardsPunishmentsForm')?->punishmentList ?? [],
                'personnelExtra' => $this->safePropertyValue('personalForm')?->personnelExtra ?? [],
            ],
            7 => [
                'kinship' => $this->safePropertyValue('kinshipForm')?->kinship ?? [],
                'kinshipList' => $this->safePropertyValue('kinshipForm')?->kinshipList ?? [],
                'editingKinshipKey' => $this->safePropertyValue('kinshipForm')?->editingKinshipKey,
            ],
            8 => [
                'language' => $this->safePropertyValue('miscForm')?->language ?? [],
                'languageList' => $this->safePropertyValue('miscForm')?->languageList ?? [],
                'event' => $this->safePropertyValue('miscForm')?->event ?? [],
                'eventList' => $this->safePropertyValue('miscForm')?->eventList ?? [],
                'degree' => $this->safePropertyValue('miscForm')?->degree ?? [],
                'degreeList' => $this->safePropertyValue('miscForm')?->degreeList ?? [],
                'election' => $this->safePropertyValue('miscForm')?->election ?? [],
                'electionList' => $this->safePropertyValue('miscForm')?->electionList ?? [],
                'hasElectedElectorals' => (bool) ($this->safePropertyValue('miscForm')?->hasElectedElectorals ?? false),
            ],
            default => [],
        };
    }

    #[Computed]
    public function activeStepPayload(): array
    {
        $step = (int) $this->safePropertyValue('step', 1);

        return array_merge($this->baseStepPayload(), $this->stepFormPayload($step));
    }

    #[Computed]
    public function activeStepSearchModels(): array
    {
        return $this->stepSearchModelMap((int) $this->safePropertyValue('step', 1));
    }

    #[Computed]
    public function activeStepSearchPlaceholders(): array
    {
        return $this->stepSearchPlaceholderDefaults((int) $this->safePropertyValue('step', 1));
    }

    protected function baseStepPayload(): array
    {
        return [
            'step' => (int) $this->safePropertyValue('step', 1),
            'title' => $this->safePropertyValue('title', null),
        ];
    }

    protected function stepFormPayload(int $step): array
    {
        return match ($step) {
            1 => [
                'personalForm' => $this->safePropertyValue('personalForm', null),
                'avatar' => $this->safePropertyValue('avatar', null),
                'personnelModel' => $this->safePropertyValue('personnelModel', null),
            ],
            2 => [
                'documentForm' => $this->safePropertyValue('documentForm', null),
            ],
            3 => [
                'educationForm' => $this->safePropertyValue('educationForm', null),
                'calculatedDataEducation' => $this->safePropertyValue('calculatedDataEducation', []),
                'calculatedDataExtraEducation' => $this->safePropertyValue('calculatedDataExtraEducation', []),
            ],
            4 => [
                'laborActivityForm' => $this->safePropertyValue('laborActivityForm', null),
                'isSpecialService' => (bool) $this->safePropertyValue('isSpecialService', false),
                'calculatedData' => $this->safePropertyValue('calculatedData', []),
            ],
            5 => [
                'historyForm' => $this->safePropertyValue('historyForm', null),
            ],
            6 => [
                'awardsPunishmentsForm' => $this->safePropertyValue('awardsPunishmentsForm', null),
            ],
            7 => [
                'kinshipForm' => $this->safePropertyValue('kinshipForm', null),
            ],
            8 => [
                'miscForm' => $this->safePropertyValue('miscForm', null),
                'knowledges' => $this->safePropertyValue('knowledges', []),
            ],
            default => [],
        };
    }

    protected function stepSearchModelMap(int $step): array
    {
        $keys = $this->stepSearchKeysForStep($step);

        if (! $keys) {
            return [];
        }

        $modelMap = [];
        foreach ($keys as $key) {
            $modelMap[$key] = $key;
        }

        return $modelMap;
    }

    protected function handleStepChanged(): void
    {
        $this->stepNavigationService()->handleStepChanged((int) $this->step, function (int $step): void {
            if (method_exists($this, 'onStepChanged')) {
                $this->onStepChanged($step);
            }
        });
    }

    public function isCurrentStepLoaded(): bool
    {
        $currentStep = (int) $this->step;

        if (! isset($this->loadedSteps) || ! is_array($this->loadedSteps)) {
            return false;
        }

        return $this->stepStateService()->isStepLoaded($this->loadedSteps, $currentStep);
    }

    protected function stepNavigationService(): PersonnelStepNavigationService
    {
        return $this->stepNavigationServiceInstance
            ??= resolve(PersonnelStepNavigationService::class);
    }

    private function safePropertyValue(string $property, mixed $default = null): mixed
    {
        if (! property_exists($this, $property)) {
            return $default;
        }

        try {
            return $this->{$property};
        } catch (\Error) {
            return $default;
        }
    }

    protected function stepSearchPlaceholderDefaults(int $step): array
    {
        $defaults = [];

        foreach ($this->stepSearchKeysForStep($step) as $searchKey) {
            $defaults[$searchKey] = __('personnel::common.placeholders.search');
        }

        return $defaults;
    }

    protected function stepSearchKeysForStep(int $step): array
    {
        return match ($step) {
            1 => ['searchNationality', 'searchPreviousNationality', 'searchSocialOrigin', 'searchEducationDegree', 'searchStructure', 'searchPosition', 'searchWorkNorm', 'searchDisability'],
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

    protected function stepStateService(): PersonnelStepState
    {
        return $this->stepStateServiceInstance
            ??= resolve(PersonnelStepState::class);
    }
}
