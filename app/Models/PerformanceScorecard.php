<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceScorecard extends Model
{
    use LogsActivity;

    public const STATUSES = ['draft', 'active', 'manager_review', 'closed'];

    /** Phase-1 workflow (spec §14): draft → active → manager_review → closed, HR may return a review. */
    public const TRANSITIONS = [
        'activate' => ['from' => 'draft', 'to' => 'active'],
        'submit' => ['from' => 'active', 'to' => 'manager_review'],
        'return' => ['from' => 'manager_review', 'to' => 'active'],
        'close' => ['from' => 'manager_review', 'to' => 'closed'],
    ];

    protected $fillable = [
        'performance_cycle_id',
        'personnel_id',
        'position_id',
        'performance_kpi_template_id',
        'manager_personnel_id',
        'status',
        'valid_from',
        'valid_to',
        'prorata_factor',
        'kpi_weight_share',
        'competency_weight_share',
        'kpi_score',
        'competency_score',
        'final_score',
        'calibrated_score',
        'rating_category',
        'snapshot',
        'locked_at',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'prorata_factor' => 'decimal:4',
        'kpi_weight_share' => 'decimal:2',
        'competency_weight_share' => 'decimal:2',
        'kpi_score' => 'decimal:4',
        'competency_score' => 'decimal:4',
        'final_score' => 'decimal:4',
        'calibrated_score' => 'decimal:4',
        'snapshot' => 'array',
        'locked_at' => 'datetime',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'manager_personnel_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiTemplate::class, 'performance_kpi_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceScorecardItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isLocked(): bool
    {
        return $this->status === 'closed';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
