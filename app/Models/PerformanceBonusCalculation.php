<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's bonus for one card, with every factor kept so the employee can see how
 * the amount was reached. `exported` (payroll) and `ordered` (əmr) rows are final.
 */
class PerformanceBonusCalculation extends Model
{
    protected $fillable = [
        'performance_scorecard_id',
        'performance_cycle_id',
        'personnel_id',
        'mode',
        'score',
        'base_salary',
        'period_months',
        'target_pct',
        'payout_pct',
        'company_mult',
        'prorata',
        'scale_factor',
        'amount',
        'currency',
        'status',
        'order_log_id',
        'exported_at',
        'export_batch',
    ];

    protected $casts = [
        'score' => 'float',
        'base_salary' => 'float',
        'period_months' => 'float',
        'target_pct' => 'float',
        'payout_pct' => 'float',
        'company_mult' => 'float',
        'prorata' => 'float',
        'scale_factor' => 'float',
        'amount' => 'float',
        'exported_at' => 'datetime',
    ];

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    public function orderLog(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'order_log_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['exported', 'ordered'], true);
    }
}
