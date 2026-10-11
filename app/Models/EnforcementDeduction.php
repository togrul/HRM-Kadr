<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kilidlənmiş run-da icra sənədi üzrə faktiki tutulma (tarixçə və icra məmuruna siyahının mənbəyi).
 *
 * @property int $id
 * @property int $enforcement_document_id
 * @property int $payroll_run_id
 * @property int|null $payslip_id
 * @property string $tabel_no
 * @property string $requested_amount
 * @property string $amount
 * @property string $wage_due
 * @property string|null $remaining_after
 * @property \Illuminate\Support\Carbon $paid_on
 */
class EnforcementDeduction extends Model
{
    protected $fillable = [
        'enforcement_document_id', 'payroll_run_id', 'payslip_id', 'tabel_no', 'requested_amount',
        'amount', 'wage_due', 'remaining_after', 'paid_on',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'wage_due' => 'decimal:2',
        'remaining_after' => 'decimal:2',
        'paid_on' => 'date',
    ];

    /** @return BelongsTo<EnforcementDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(EnforcementDocument::class, 'enforcement_document_id');
    }

    /** @return BelongsTo<PayrollRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
