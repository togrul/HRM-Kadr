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
            2 => 'personnel.steps.document-step',
            3 => 'personnel.steps.education-step',
            4 => 'personnel.steps.labor-activity-step',
            5 => 'personnel.steps.history-step',
            6 => 'personnel.steps.awards-punishments-step',
            7 => 'personnel.steps.kinship-step',
            8 => 'personnel.steps.misc-step',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function childComponentMap(): array
    {
        return [
            2 => 'personnel.steps.document-step',
            3 => 'personnel.steps.education-step',
            4 => 'personnel.steps.labor-activity-step',
            5 => 'personnel.steps.history-step',
            6 => 'personnel.steps.awards-punishments-step',
            7 => 'personnel.steps.kinship-step',
            8 => 'personnel.steps.misc-step',
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

    public function stepUsesChildComponent(int $step): bool
    {
        return isset($this->childComponentMap()[$step]);
    }

    public function stepChildComponent(int $step): ?string
    {
        return $this->childComponentMap()[$step] ?? null;
    }
}
