<?php

namespace App\Modules\Personnel\Services;

class PersonnelStepNavigationService
{
    /**
     * @return array<int, string>
     */
    public function steps(): array
    {
        return [
            1 => __('personnel::common.steps.personal_information'),
            2 => __('personnel::common.steps.cards'),
            3 => __('personnel::common.steps.education'),
            4 => __('personnel::common.steps.labor_activities'),
            5 => __('personnel::common.steps.military'),
            6 => __('personnel::common.steps.awards_and_punishments'),
            7 => __('personnel::common.steps.kinships'),
            8 => __('personnel::common.steps.other'),
        ];
    }

    public function previous(int $currentStep): int
    {
        return max(1, $currentStep - 1);
    }

    public function next(int $currentStep): int
    {
        return $currentStep + 1;
    }

    public function select(int $step): int
    {
        return $step;
    }

    public function handleStepChanged(int $step, callable $callback): void
    {
        $callback($step);
    }
}
