<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetroPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tabel_no',
        'source_payroll_run_id',
        'paid_payroll_run_id',
        'amount',
        'paid_on',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];
}
