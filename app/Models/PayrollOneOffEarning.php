<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
