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
            1 => __('Personal Information'),
            2 => __('Cards'),
            3 => __('Education'),
            4 => __('Labor activities'),
            5 => __('Military'),
            6 => __('Awards and punishments'),
            7 => __('Kinships'),
            8 => __('Other'),
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
