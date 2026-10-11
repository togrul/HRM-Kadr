<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $employee_loan_id
 * @property int|null $payroll_run_id
 * @property string $amount
 * @property string|null $scheduled_amount
 * @property \Illuminate\Support\Carbon $paid_on
 */
class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_loan_id',
        'payroll_run_id',
        'amount',
        'scheduled_amount',
        'paid_on',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    /** Qrafik üzrə tutulmalı olub, ƏM m.176.1 həddinə görə tutulmayan və sonrakı aylara keçən hissə. */
    public function deferredAmount(): float
    {
        return $this->scheduled_amount === null ? 0.0 : max(0.0, round((float) $this->scheduled_amount - (float) $this->amount, 2));
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }
}
