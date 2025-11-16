<?php

namespace App\Services\Orders;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\ImportCandidateToPersonnel;

class OrderPersonnelPersister
{
    public function __construct(
        protected ImportCandidateToPersonnel $importCandidateToPersonnel
    ) {
    }

    public function attachAssignments(OrderLog $orderLog, array $tabelNumbers, array $componentIds): void
    {
        $payload = $this->mapPersonnelComponents($tabelNumbers, $componentIds);

        if (! empty($payload)) {
            $orderLog->personnels()->attach($payload);
        }
    }

    public function syncAssignments(OrderLog $orderLog, array $tabelNumbers, array $componentIds): void
    {
        $payload = $this->mapPersonnelComponents($tabelNumbers, $componentIds);

        if (! empty($payload)) {
            $orderLog->personnels()->sync($payload);
        }
    }

    public function attachFromVacancies(OrderLog $orderLog, array $vacancyList, bool $isCandidateOrder, int $statusId): void
    {
        if (empty($vacancyList)) {
            return;
        }

        $componentIds = collect($vacancyList)->pluck('component_id')->toArray();

        if (empty($componentIds)) {
            return;
        }

        $tabelNumbers = $isCandidateOrder
            ? $this->importCandidateToPersonnel->handle($vacancyList, $statusId)
            : $this->resolvePersonnelTabelNumbers($vacancyList);

        $this->attachAssignments($orderLog, $tabelNumbers, $componentIds);
    }

    protected function resolvePersonnelTabelNumbers(array $vacancyList): array
    {
        $personnelIds = collect($vacancyList)->pluck('personnel_id')->filter()->all();

        if (empty($personnelIds)) {
            return [];
        }

        return Personnel::find($personnelIds)->pluck('tabel_no')->toArray();
    }

    protected function mapPersonnelComponents(array $tabelNumbers, array $componentIds): array
    {
        if (empty($tabelNumbers) || empty($componentIds)) {
            return [];
        }

        $componentArray = array_pad($componentIds, count($tabelNumbers), end($componentIds));

        $combined = @array_combine($tabelNumbers, $componentArray);

        if ($combined === false) {
            return [];
        }

        return array_map(
            fn ($componentId) => ['component_id' => $componentId],
            $combined
        );
    }
}
