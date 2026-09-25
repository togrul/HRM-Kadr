<?php

namespace App\Models;

use App\Support\Database\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * How a cycle's bonus is worked out. `company` pays base × target % × payout ×
 * company multiplier; `order` (military regime) turns the payout into months of salary
 * awarded by an order (əmr).
 *
 * @property int $id
 * @property int $performance_cycle_id
 * @property string $mode
 * @property float $target_pct
 * @property array|null $position_targets
 * @property float $reward_months
 * @property array $payout_bands
 * @property float|null $company_result
 * @property float $company_gate
 * @property float $gate_floor_pct
 * @property array $company_multipliers
 * @property array|null $unit_results
 * @property float $cap_pct
 * @property float|null $fund
 * @property bool $scale_to_fund
 * @property bool $pay_in_probation
 * @property string $currency
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceBonusRule extends Model
{
    use LogsActivity;

    public const MODES = ['company', 'order'];

    /** Spec §7 default payout matrix: final score from → payout %. */
    public const DEFAULT_PAYOUT_BANDS = [[0, 0], [80, 50], [90, 80], [100, 100], [110, 120]];

    /** Company result from → multiplier, applied once the gate is passed. */
    public const DEFAULT_COMPANY_MULTIPLIERS = [[85, 0.8], [95, 1.0], [105, 1.1]];

    protected $fillable = [
        'performance_cycle_id',
        'mode',
        'target_pct',
        'position_targets',
        'reward_months',
        'payout_bands',
        'company_result',
        'company_gate',
        'gate_floor_pct',
        'company_multipliers',
        'unit_results',
        'cap_pct',
        'fund',
        'scale_to_fund',
        'pay_in_probation',
        'currency',
        'updated_by',
    ];

    protected $casts = [
        'target_pct' => 'float',
        'reward_months' => 'float',
        'payout_bands' => 'array',
        'company_result' => 'float',
        'company_gate' => 'float',
        'gate_floor_pct' => 'float',
        'company_multipliers' => 'array',
        'unit_results' => 'array',
        'position_targets' => 'array',
        'cap_pct' => 'float',
        'fund' => EncryptedFloat::class,
        'scale_to_fund' => 'boolean',
        'pay_in_probation' => 'boolean',
    ];

    /** @return BelongsTo<PerformanceCycle, $this> */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_bonus')
            ->logFillable()
            ->logOnlyDirty();
    }
}
