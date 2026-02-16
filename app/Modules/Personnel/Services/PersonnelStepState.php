<?php

namespace App\Modules\Personnel\Services;

class PersonnelStepState
{
    /**
     * @return array<int, string>
     */
    public function stepTemplateMap(): array
    {
        return [
            1 => 'includes.step1',
            2 => 'includes.step2',
            3 => 'includes.step3',
            4 => 'includes.step4',
            5 => 'includes.step5',
            6 => 'includes.step6',
            7 => 'includes.step7',
            8 => 'includes.step8',
        ];
    }

    /**
     * @return array<int, bool>
     */
    public function wizardStepSet(): array
    {
        return [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
            7 => true,
            8 => true,
        ];
    }

    public function completionStepName(int $step): ?string
    {
        return match ($step) {
            1 => 'personnel',
            2 => 'document',
            3 => 'education',
            default => null,
        };
    }

    public function shouldLoadLookupData(int $step): bool
    {
        $stepConfig = $this->wizardStepSet();

        return isset($stepConfig[$step]) ? ! $stepConfig[$step] : true;
    }

    /**
     * Step -> relation payload keys mapping.
     *
     * @return array<int, array<int, string>>
     */
    public function relationPayloadMap(): array
    {
        return [
            2 => ['document', 'service_cards', 'passports'],
            3 => ['education', 'extra_educations'],
            4 => ['labor_activities', 'ranks'],
            5 => ['military', 'injuries', 'captivities'],
            6 => ['awards', 'punishments'],
            7 => ['kinships'],
            8 => ['languages', 'events', 'degrees', 'elections'],
        ];
    }

    /**
     * @param  array<int, int|string>  $loadedSteps
     * @return array<int, string>
     */
    public function payloadKeysForLoadedSteps(array $loadedSteps): array
    {
        $map = $this->relationPayloadMap();
        $keys = [];

        foreach ($loadedSteps as $step) {
            $step = (int) $step;
            if (! isset($map[$step])) {
                continue;
            }

            foreach ($map[$step] as $key) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    public function isStepLoaded(array $loadedSteps, int $step): bool
    {
        return in_array($step, $loadedSteps, true);
    }

    public function hasLoadedAnyStep(array $loadedSteps): bool
    {
        return ! empty($loadedSteps);
    }

    public function stepTemplate(int $step): string
    {
        return $this->stepTemplateMap()[$step] ?? $this->stepTemplateMap()[1];
    }

    public function stepComponent(int $step): string
    {
        return $this->stepTemplate($step);
    }
}
