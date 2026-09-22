<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_scorecard_id
 * @property float|string $delta
 * @property string $reason
 * @property int|null $adjusted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceCalibrationAdjustment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_id',
        'delta',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'delta' => 'decimal:4',
    ];

    /** @return BelongsTo<PerformanceScorecard, $this> */
    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    /** @return BelongsTo<User, $this> */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
