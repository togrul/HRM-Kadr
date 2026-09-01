<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A payslip computed by the finance system.
 *
 * Totals only — gross, deductions, net. The mechanism behind them (tax brackets,
 * rates, average-earnings base) stays on that side, where the legal engine is.
 * What an employee needs here is the result.
 *
 * @property string $tabel_no
 * @property int $year
 * @property int $month
 * @property string $employee_name
 * @property float|string $gross
 * @property float|string $total_deductions
 * @property float|string $net
 * @property string $currency
 */
class FinancePayslip extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
