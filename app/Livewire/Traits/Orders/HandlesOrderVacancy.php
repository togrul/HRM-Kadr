<?php

namespace App\Livewire\Traits\Orders;

use App\Services\CheckVacancyService;
use App\Services\Orders\VacancyDiffService;
use App\Models\Order;

trait HandlesOrderVacancy
{
    protected array $vacancy_list = [];

    protected function resolveVacancyData(array $bladeData): array
    {
        $message = '';
        $vacancyList = [];

        if (empty($bladeData) || ! $this->isCandidateOrder()) {
            return [$vacancyList, $message];
        }

        $vacancyCandidates = match ($this->selectedBlade) {
            Order::BLADE_DEFAULT => $this->componentForms,
            Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP => $this->selectedPersonnel->rows,
        };

        $diff = (new VacancyDiffService)->diff($vacancyCandidates, $this->originalComponents);

        if ($this->selectedBlade === Order::BLADE_DEFAULT) {
            $normalized = $this->normalizeVacancyEntries($diff);

            if (! empty($normalized)) {
                $this->vacancy_list = (new CheckVacancyService)->handle($normalized);
                $message = $this->vacancy_list['message'] ?? '';
                $vacancyList = $normalized;
            }
        } else {
            $vacancyList = $diff;
        }

        return [$vacancyList, $message];
    }
}
