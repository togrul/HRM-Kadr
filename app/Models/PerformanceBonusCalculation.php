<?php

namespace App\Models;

use App\Support\Database\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's bonus for one card, with every factor kept so the employee can see how
 * the amount was reached. `exported` (payroll) and `ordered` (əmr) rows are final.
 *
 * @property int $id
 * @property int $performance_scorecard_id
 * @property int $performance_cycle_id
 * @property int $personnel_id
 * @property string $mode
 * @property float|null $score
 * @property float|null $base_salary
 * @property float $period_months
 * @property float $target_pct
 * @property float $payout_pct
 * @property float $company_mult
 * @property float $unit_mult
 * @property float $prorata
 * @property float $scale_factor
 * @property float|null $amount
 * @property string $currency
 * @property string $status
 * @property int|null $order_log_id
 * @property \Illuminate\Support\Carbon|null $exported_at
 * @property string|null $export_batch
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
        'unit_mult',
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
        'base_salary' => EncryptedFloat::class,
        'period_months' => 'float',
        'target_pct' => 'float',
        'payout_pct' => 'float',
        'company_mult' => 'float',
        'unit_mult' => 'float',
        'prorata' => 'float',
        'scale_factor' => 'float',
        'amount' => EncryptedFloat::class,
        'exported_at' => 'datetime',
    ];

    /** @return BelongsTo<PerformanceScorecard, $this> */
    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    /** @return BelongsTo<Personnel, $this> */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    /** @return BelongsTo<OrderLog, $this> */
    public function orderLog(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'order_log_id');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['exported', 'ordered'], true);
    }
}
