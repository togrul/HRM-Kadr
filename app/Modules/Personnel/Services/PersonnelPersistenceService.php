<?php

namespace App\Modules\Personnel\Services;

use Illuminate\Support\Arr;

class PersonnelPersistenceService
{
    public function __construct(
        protected PersonnelStepState $stepState
    ) {}

    /**
     * Keep only relation payload keys that belong to loaded steps.
     *
     * @param  array<string, mixed>  $payloads
     * @param  array<int, int|string>  $loadedSteps
     * @return array<string, mixed>
     */
    public function payloadsForLoadedSteps(array $payloads, array $loadedSteps): array
    {
        $allowedKeys = $this->stepState->payloadKeysForLoadedSteps($loadedSteps);

        if (empty($allowedKeys)) {
            return [];
        }

        return Arr::only($payloads, $allowedKeys);
    }
}

