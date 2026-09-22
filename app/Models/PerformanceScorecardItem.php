<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id')->withTrashed();
    }

    public function kpiVersion(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiVersion::class, 'performance_kpi_version_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(PerformanceGoal::class, 'performance_goal_id');
    }

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
}
