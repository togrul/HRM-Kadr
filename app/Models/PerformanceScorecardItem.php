<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_scorecard_id
 * @property int $performance_kpi_id
 * @property int|null $performance_kpi_version_id
 * @property int|null $performance_goal_id
 * @property float|string $weight
 * @property float|string|null $target
 * @property float|string|null $original_target
 * @property float|string|null $forecast
 * @property float|string|null $forecast_achievement
 * @property \Illuminate\Support\Carbon|null $red_notified_at
 * @property float|string|null $range_min
 * @property float|string|null $range_max
 * @property float|string|null $threshold
 * @property float|string|null $stretch
 * @property float|string|null $cap
 * @property bool $target_editable
 * @property int $sort_order
 * @property float|string|null $actual
 * @property float|string|null $achievement
 * @property float|string|null $score
 * @property string|null $comment_employee
 * @property string|null $comment_manager
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceScorecardItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_id',
        'performance_kpi_id',
        'performance_kpi_version_id',
        'performance_goal_id',
        'weight',
        'target',
        'original_target',
        'forecast',
        'forecast_achievement',
        'red_notified_at',
        'range_min',
        'range_max',
        'threshold',
        'stretch',
        'cap',
        'target_editable',
        'sort_order',
        'actual',
        'achievement',
        'score',
        'comment_employee',
        'comment_manager',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'target' => 'decimal:4',
        'original_target' => 'decimal:4',
        'forecast' => 'decimal:4',
        'forecast_achievement' => 'decimal:4',
        'red_notified_at' => 'datetime',
        'range_min' => 'decimal:4',
        'range_max' => 'decimal:4',
        'threshold' => 'decimal:4',
        'stretch' => 'decimal:4',
        'cap' => 'decimal:4',
        'target_editable' => 'boolean',
        'actual' => 'decimal:4',
        'achievement' => 'decimal:4',
        'score' => 'decimal:4',
    ];

    /** @return BelongsTo<PerformanceScorecard, $this> */
    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    /** @return BelongsTo<PerformanceKpi, $this> */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id')->withTrashed();
    }

    /** @return BelongsTo<PerformanceKpiVersion, $this> */
    public function kpiVersion(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiVersion::class, 'performance_kpi_version_id');
    }

    /** @return BelongsTo<PerformanceGoal, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(PerformanceGoal::class, 'performance_goal_id');
    }

    /** @return HasMany<PerformanceKpiActual, $this> */
    public function actuals(): HasMany
    {
        return $this->hasMany(PerformanceKpiActual::class)->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }

    /** @return HasMany<PerformanceScorecardChangeRequest, $this> */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(PerformanceScorecardChangeRequest::class)->latest('id');
    }
}
