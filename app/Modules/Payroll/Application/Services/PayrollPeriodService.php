<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Carbon;

class PayrollPeriodService
{
    /**
     * @param  array<string,mixed>  $extra
     */
    public function createPeriod(int $year, int $month, array $extra = []): PayrollPeriod
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return PayrollPeriod::updateOrCreate(
            ['code' => sprintf('%04d-%02d', $year, $month)],
            [
                'year' => $year,
                'month' => $month,
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
                'currency' => $extra['currency'] ?? 'AZN',
                'status' => $extra['status'] ?? 'open',
                'note' => $extra['note'] ?? null,
            ],
        );
    }

    public function close(PayrollPeriod $period): PayrollPeriod
    {
        $period->update(['status' => 'closed']);

        return $period;
    }

    public function reopen(PayrollPeriod $period): PayrollPeriod
    {
        $period->update(['status' => 'open']);

        return $period;
    }
}
