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
    public function activeStepPayload(): array
    {
        $step = (int) $this->safePropertyValue('step', 1);

        $payload = array_merge($this->baseStepPayload(), $this->stepFormPayload($step));

        $payload['stepSearchModels'] = $this->stepSearchModelMap($step);
        $payload['stepSearchPlaceholders'] = $this->stepSearchPlaceholderDefaults($step);

        return $payload;
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
                'personnelModelData' => method_exists($this, 'personnelModelDataInstance')
                    ? $this->personnelModelDataInstance()
                    : $this->safePropertyValue('personnelModelData', null),
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

    protected function shouldLoadLookupData(): bool
    {
        return $this->stepStateService()->shouldLoadLookupData($this->step);
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
            $defaults[$searchKey] = __('Search...');
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
