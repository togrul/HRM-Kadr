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
        return max(1, $this->normalize($currentStep) - 1);
    }

    public function next(int $currentStep): int
    {
        return min($this->maxStep(), $this->normalize($currentStep) + 1);
    }

    public function select(int $step): int
    {
        return $this->normalize($step);
    }

    public function handleStepChanged(int $step, callable $callback): void
    {
        $callback($this->normalize($step));
    }

    public function maxStep(): int
    {
        return max(array_keys($this->steps()));
    }

    public function normalize(int $step): int
    {
        return min($this->maxStep(), max(1, $step));
    }
}
