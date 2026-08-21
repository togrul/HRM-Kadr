<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Support\Carbon;

/**
 * Ends employment: sets the personnel's leave_work_date to the termination date
 * and ends the employee's active compensation (payroll stops).
 */
class TerminationEffect implements OrderEffect
{
    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly CompensationService $compensation,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $date = $this->dates->parse($fields['date'] ?? null);
        if (! $date) {
            return;
        }

        $personnel->forceFill(['leave_work_date' => $date->format('Y-m-d')])->save();

        $this->compensation->endActiveForTermination($personnel->tabel_no, Carbon::parse($date->format('Y-m-d')));
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        // Re-instate the employee: clear the termination date and re-activate the ended compensation.
        $personnel->forceFill(['leave_work_date' => null])->save();

        $this->compensation->reactivateAfterTermination($personnel->tabel_no);
    }
}
