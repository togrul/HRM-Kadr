<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderLog;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Illuminate\Support\Collection;

class VacationCleanupService
{
    public function handle(OrderLog $order, Collection $attributes, string $blade, array $selectedPersonnel): void
    {
        $deletedPersonnel = $this->determineRemovedPersonnel($order, $selectedPersonnel);

        $this->deleteRemovedAttributes($order, $attributes);

        match ($blade) {
            Order::BLADE_VACATION => $this->cleanupVacationRecords($order, $deletedPersonnel),
            Order::BLADE_BUSINESS_TRIP => $this->cleanupBusinessTripRecords($order, $deletedPersonnel),
            default => null,
        };
    }

    protected function determineRemovedPersonnel(OrderLog $order, array $selectedPersonnel): array
    {
        return array_diff(
            $order->personnels->pluck('tabel_no')->all(),
            $selectedPersonnel
        );
    }

    protected function deleteRemovedAttributes(OrderLog $order, Collection $attributes): void
    {
        $currentFullNames = $attributes->pluck('$fullname');

        $order->attributes->each(function ($record) use ($currentFullNames) {
            if (! $currentFullNames->contains($record->attributes['$fullname']['value'])) {
                $record->delete();
            }
        });
    }

    protected function cleanupVacationRecords(OrderLog $order, array $tabelNumbers): void
    {
        if (empty($tabelNumbers)) {
            return;
        }

        PersonnelVacation::whereIn('tabel_no', $tabelNumbers)
            ->where('order_no', $order->order_no)
            ->delete();
    }

    protected function cleanupBusinessTripRecords(OrderLog $order, array $tabelNumbers): void
    {
        if (empty($tabelNumbers)) {
            return;
        }

        PersonnelBusinessTrip::whereIn('tabel_no', $tabelNumbers)
            ->where('order_no', $order->order_no)
            ->delete();
    }
}
