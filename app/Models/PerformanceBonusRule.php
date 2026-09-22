<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * How a cycle's bonus is worked out. `company` pays base × target % × payout ×
 * company multiplier; `order` (military regime) turns the payout into months of salary
 * awarded by an order (əmr).
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
        'reward_months',
        'payout_bands',
        'company_result',
        'company_gate',
        'gate_floor_pct',
        'company_multipliers',
        'cap_pct',
        'fund',
        'scale_to_fund',
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
        'cap_pct' => 'float',
        'fund' => 'float',
        'scale_to_fund' => 'boolean',
    ];

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
