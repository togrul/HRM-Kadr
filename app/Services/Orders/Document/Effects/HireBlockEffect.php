<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\PersonnelTabelNoGeneratorService;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Activates employment when a hiring order is approved: stamps the join date, marks
 * the person active and resolves a permanent tabel number.
 *
 * tabel resolution reuses PersonnelTabelNoGeneratorService::resolveForApprovedPersonnel,
 * which only mints a new code for someone who lacks a permanent one — an existing
 * employee keeps theirs, so this is safe to run on real records.
 */
class HireBlockEffect implements BlockOrderEffect
{
    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly PersonnelTabelNoGeneratorService $tabelNo,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $joinDate = $this->dates->parse($fields['start_date'] ?? null);
        if (! $joinDate) {
            return;
        }

        $date = $joinDate->format('Y-m-d');

        $personnel->forceFill([
            'join_work_date' => $date,
            'is_pending' => false,
            'tabel_no' => $this->tabelNo->resolveForApprovedPersonnel($personnel, $date),
        ])->save();
    }
}
