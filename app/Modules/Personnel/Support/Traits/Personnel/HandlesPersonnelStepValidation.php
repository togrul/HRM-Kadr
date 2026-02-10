<?php

namespace App\Modules\Personnel\Support\Traits\Personnel;

trait HandlesPersonnelStepValidation
{
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
}
