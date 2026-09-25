<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $tabel_no
 * @property string $code
 * @property string $name
 * @property float $amount
 * @property int $pay_year
 * @property int $pay_month
 * @property bool $taxable
 * @property bool $affects_social
 * @property string $source_key
 * @property int|null $paid_payroll_run_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayrollOneOffEarning extends Model
{
    protected $fillable = [
        'tabel_no',
        'code',
        'name',
        'amount',
        'pay_year',
        'pay_month',
        'taxable',
        'affects_social',
        'source_key',
        'paid_payroll_run_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'taxable' => 'boolean',
        'affects_social' => 'boolean',
    ];
}
