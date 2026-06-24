<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceGoal extends Model
{
    use LogsActivity;

    public const TYPES = ['objective', 'kpi', 'goal'];

    public const STATUSES = ['draft', 'active', 'at_risk', 'done', 'cancelled'];

    protected $fillable = [
        'performance_cycle_id',
        'personnel_id',
        'parent_goal_id',
        'goal_type',
        'title',
        'description',
        'weight_percent',
        'unit',
        'target_value',
        'current_value',
        'status',
        'due_date',
        'created_by',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_goal_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_goal_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(PerformanceGoalCheckin::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Own progress from the measurable target (key-results roll up separately in the service).
     */
    public function getProgressPctAttribute(): int
    {
        if ($this->status === 'done') {
            return 100;
        }

        $target = (float) $this->target_value;
        if ($target <= 0) {
            return 0;
        }

        return (int) min(100, max(0, round((float) $this->current_value / $target * 100)));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_goal')
            ->logFillable()
            ->logOnlyDirty();
    }
}
